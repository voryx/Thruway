<?php

namespace Thruway\Role;

use Thruway\Call;
use Thruway\Common\Utils;
use Thruway\Event\LeaveRealmEvent;
use Thruway\Event\MessageEvent;
use Thruway\Logging\Logger;
use Thruway\Message\CallMessage;
use Thruway\Message\CancelMessage;
use Thruway\Message\ErrorMessage;
use Thruway\Message\Message;
use Thruway\Message\RegisterMessage;
use Thruway\Message\UnregisterMessage;
use Thruway\Message\WelcomeMessage;
use Thruway\Message\YieldMessage;
use Thruway\Module\RealmModuleInterface;
use Thruway\Procedure;
use Thruway\Registration;
use Thruway\Session;

/**
 * Class Dealer
 *
 * @package Thruway\Role
 */
class Dealer implements RealmModuleInterface
{
    /**
     * @var Procedure[]
     */
    private $procedures = [];

    /**
     * @var Call[]
     */
    private $callInvocationIndex = [];

    /**
     * @var Call[]
     */
    private $callRequestIndex = [];

    /**
     * @var Call[]
     */
    private $callCancelIndex = [];

    /**
     * @var Call[]
     */
    private $callInterruptIndex = [];

    /**
     * @var \SplObjectStorage
     */
    private $registrationsBySession;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->registrationsBySession = new \SplObjectStorage();
    }

    /** @return array */
    public function getSubscribedRealmEvents()
    {
        return [
            'CallMessageEvent'        => ['handleCallMessage', 10],
            'CancelMessageEvent'      => ['handleCancelMessage', 10],
            'RegisterMessageEvent'    => ['handleRegisterMessage', 10],
            'UnregisterMessageEvent'  => ['handleUnregisterMessage', 10],
            'YieldMessageEvent'       => ['handleYieldMessage', 10],
            'ErrorMessageEvent'       => ['handleErrorMessage', 10],
            'LeaveRealm'              => ['handleLeaveRealm', 10],
            'SendWelcomeMessageEvent' => ['handleSendWelcomeMessage', 20]
        ];
    }

    /**
     * @param \Thruway\Event\MessageEvent $event
     */
    public function handleCallMessage(MessageEvent $event)
    {
        $this->processCall($event->session, $event->message);
    }

    /**
     * @param \Thruway\Event\MessageEvent $event
     */
    public function handleCancelMessage(MessageEvent $event)
    {
        $this->processCancel($event->session, $event->message);
    }

    /**
     * @param \Thruway\Event\MessageEvent $event
     */
    public function handleRegisterMessage(MessageEvent $event)
    {
        $this->processRegister($event->session, $event->message);
    }

    /**
     * @param \Thruway\Event\MessageEvent $event
     */
    public function handleUnregisterMessage(MessageEvent $event)
    {
        $this->processUnregister($event->session, $event->message);
    }

    /**
     * @param \Thruway\Event\MessageEvent $event
     */
    public function handleYieldMessage(MessageEvent $event)
    {
        $this->processYield($event->session, $event->message);
    }

    /**
     * @param \Thruway\Event\MessageEvent $event
     */
    public function handleErrorMessage(MessageEvent $event)
    {
        if ($this->handlesMessage($event->message)) {
            $this->processError($event->session, $event->message);
        }
    }

    /**
     * @param \Thruway\Event\LeaveRealmEvent $event
     */
    public function handleLeaveRealm(LeaveRealmEvent $event)
    {
        $this->leave($event->session);
    }

    /**
     * @param \Thruway\Event\MessageEvent $event
     */
    public function handleSendWelcomeMessage(MessageEvent $event)
    {
        /** @var WelcomeMessage $welcomeMessage */
        $welcomeMessage = $event->message;

        //Tell the welcome message what features we support
        $welcomeMessage->addFeatures('dealer', $this->getFeatures());

    }

    /**
     * @return \stdClass
     */
    public function getFeatures()
    {
        $features = new \stdClass();

        $features->caller_identification    = true;
        $features->progressive_call_results = true;

        return $features;
    }

    /**
     * process RegisterMessage
     *
     * @param \Thruway\Session $session
     * @param \Thruway\Message\RegisterMessage $msg
     */
    private function processRegister(Session $session, RegisterMessage $msg)
    {
        // check for valid URI
        if (!Utils::uriIsValid($msg->getProcedureName())) {
            $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg, 'wamp.error.invalid_uri'));

            return;
        }

        //Check to see if the procedure is already registered
        /** @var Procedure $procedure */
        if (isset($this->procedures[$msg->getProcedureName()])) {
            $procedure = $this->procedures[$msg->getProcedureName()];
        } else {
            $procedure                                  = new Procedure($msg->getProcedureName());
            $this->procedures[$msg->getProcedureName()] = $procedure;
        }

        if ($procedure->processRegister($session, $msg)) {
            // registration succeeded
            // make sure we have the registration in the collection
            // of registrations for this session
            if (!$this->registrationsBySession->contains($session)) {
                $this->registrationsBySession->attach($session, []);
            }
            $registrationsForThisSession = $this->registrationsBySession[$session];

            if (!in_array($procedure, $registrationsForThisSession, true)) {
                $registrationsForThisSession[] = $procedure;
                
                $this->registrationsBySession[$session] = $registrationsForThisSession;
            }
        }
    }

    /**
     * process UnregisterMessage
     *
     * @param \Thruway\Session $session
     * @param \Thruway\Message\UnregisterMessage $msg
     * @throws \Exception
     */
    private function processUnregister(Session $session, UnregisterMessage $msg)
    {
        $registration = $this->getRegistrationById($msg->getRegistrationId());

        if ($registration && $this->procedures[$registration->getProcedureName()]) {
            $procedure = $this->procedures[$registration->getProcedureName()];
            if ($procedure) {
                if ($procedure->processUnregister($session, $msg)) {
                    // Unregistration was successful - remove from this sessions
                    // list of registrations
                    if ($this->registrationsBySession->contains($session) &&
                        in_array($procedure, $this->registrationsBySession[$session], true)
                    ) {
                        $registrationsInSession = $this->registrationsBySession[$session];
                        array_splice($registrationsInSession, array_search($procedure, $registrationsInSession, true), 1);
                    }
                }
            }

            return;
        }

        // apparently we didn't find anything to unregister
        $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg, 'wamp.error.no_such_procedure'));
    }

    /**
     * Process call
     *
     * @param \Thruway\Session $session
     * @param \Thruway\Message\CallMessage $msg
     * @return boolean
     */
    private function processCall(Session $session, CallMessage $msg)
    {
        if (!Utils::uriIsValid($msg->getProcedureName())) {
            $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg, 'wamp.error.invalid_uri'));

            return;
        }

        if (!isset($this->procedures[$msg->getProcedureName()])) {
            $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg, 'wamp.error.no_such_procedure'));

            return;
        }

        /* @var $procedure \Thruway\Procedure */
        $procedure = $this->procedures[$msg->getProcedureName()];

        $call = new Call($session, $msg, $procedure);

        $this->callInvocationIndex[$call->getInvocationRequestId()] = $call;
        $this->callRequestIndex[$msg->getRequestId()]               = $call;

        $keepIndex = $procedure->processCall($session, $call);

        if (!$keepIndex) {
            $this->removeCall($call);
        }
    }

    /**
     * process YieldMessage
     *
     * @param \Thruway\Session $session
     * @param \Thruway\Message\YieldMessage $msg
     */
    private function processYield(Session $session, YieldMessage $msg)
    {

        /* @var $call Call */
        $call = isset($this->callInvocationIndex[$msg->getRequestId()]) ? $this->callInvocationIndex[$msg->getRequestId()] : null;

        if (!$call) {
            $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg));
            Logger::error($this, "Was expecting a call");

            return;
        }

        $keepIndex = $call->processYield($session, $msg);

        if (!$keepIndex) {
            $this->removeCall($call);
        }

        /* @var $procedure \Thruway\Procedure */
        $procedure = isset($this->procedures[$call->getCallMessage()->getUri()]) ? $this->procedures[$call->getCallMessage()->getUri()] : null;
        if ($procedure && $procedure->getAllowMultipleRegistrations()) {
            $procedure->processQueue();
        }

        //Process queues on other registrations if we can take more requests
        if ($session->getPendingCallCount() == 0 && $this->registrationsBySession->contains($session)) {
            $this->processQueue($session);
        }

    }

    /**
     * @param Session $session
     */
    private function processQueue(Session $session)
    {
        $registrationsForThisSession = $this->registrationsBySession[$session];
        /** @var Registration $registration */
        foreach ($registrationsForThisSession as $registration) {
            if ($registration->getAllowMultipleRegistrations()) {

                // find the procedure for this registration
                /** @var $procedure Procedure */
                $procedure = $this->procedures[$registration->getProcedureName()];
                $procedure->processQueue();
            }
        }
    }

    /**
     * process ErrorMessage
     *
     * @param \Thruway\Session $session
     * @param \Thruway\Message\ErrorMessage $msg
     */
    private function processError(Session $session, ErrorMessage $msg)
    {
        switch ($msg->getErrorMsgCode()) {
            case Message::MSG_INVOCATION:
                $this->processInvocationError($session, $msg);
                break;
            case Message::MSG_INTERRUPT:
                $this->processInterruptError($session, $msg);
                break;
        }
    }

    /**
     * @param Session $session
     * @param CancelMessage $msg
     */
    private function processCancel(Session $session, CancelMessage $msg)
    {
        $call = $this->getCallByRequestId($msg->getRequestId());

        if ($call && $call->getCallerSession() !== $session) {
            Logger::warning($this, 'Attempt to cancel call by non-owner');

            return;
        }

        if (!$call) {
            $errorMsg = ErrorMessage::createErrorMessageFromMessage($msg);
            $errorMsg->setErrorURI('wamp.error.no_such_call');
            $session->sendMessage($errorMsg);
            Logger::error($this, 'wamp.error.no_such_call');

            return;
        }

        if ($call->getInterruptMessage()) {
            $errorMsg = ErrorMessage::createErrorMessageFromMessage($msg);
            $errorMsg->setErrorURI('wamp.error.canceling');
            Logger::warning($this, 'There was an attempt to cancel a message that is already in the process of being canceled');

            return;
        }
        $removeCall = $call->processCancel($session, $msg);
        if ($call->getInterruptMessage()) {
            $this->callInterruptIndex[$call->getInterruptMessage()->getRequestId()] = $call;
        }

        if ($removeCall || $call->isProgressive()) {
            $this->removeCall($call);
        }
    }

    /**
     * Process InvocationError
     *
     * @param \Thruway\Session $session
     * @param \Thruway\Message\ErrorMessage $msg
     */
    private function processInvocationError(Session $session, ErrorMessage $msg)
    {
        //$call = $this->getCallByRequestId($msg->getRequestId());
        $call = $this->callInvocationIndex[$msg->getRequestId()];

        if (!$call) {
            $errorMsg = ErrorMessage::createErrorMessageFromMessage($msg);
            Logger::error($this, 'No call for invocation error message: ' . $msg->getRequestId());

            // TODO: do we send a message back to the callee?
            $errorMsg->setErrorURI('wamp.error.no_such_procedure');
            $session->sendMessage($errorMsg);

            return;
        }

        if ($call->getCalleeSession() !== $session) {
            Logger::error($this, 'Attempted Invocation Error from session that does not own the call');
            return;
        }

        $call->getRegistration()->removeCall($call);

        $this->removeCall($call);

        $errorMsg = ErrorMessage::createErrorMessageFromMessage($call->getCallMessage());

        $errorMsg->setErrorURI($msg->getErrorURI());
        $errorMsg->setArguments($msg->getArguments());
        $errorMsg->setArgumentsKw($msg->getArgumentsKw());

        // not sure if this detail should pass through
        $errorMsg->setDetails($msg->getDetails());

        $call->getCallerSession()->sendMessage($errorMsg);
    }

    /**
     * @param Session $session
     * @param ErrorMessage $msg
     */
    private function processInterruptError(Session $session, ErrorMessage $msg)
    {
        $call = isset($this->callInterruptIndex[$msg->getRequestId()]) ? $this->callInterruptIndex[$msg->getRequestId()] : null;

        if (!$call) {
            Logger::warning($this, 'Interrupt error with no corresponding interrupt index');

            return;
        }

        $errorMsgToCaller = ErrorMessage::createErrorMessageFromMessage($call->getCancelMessage());
        $errorMsgToCaller->setErrorURI($msg->getErrorURI());

        $callerSession = $call->getCallerSession();

        $callerSession->sendMessage($errorMsgToCaller);

        $call->getRegistration()->removeCall($call);
        $this->removeCall($call);
    }

    /**
     * This removes all references to calls so they can be GCed
     *
     * @param Call $call
     */
    protected function removeCall(Call $call)
    {
        $call->getProcedure()->removeCall($call);
        unset($this->callInvocationIndex[$call->getInvocationRequestId()]);
        unset($this->callRequestIndex[$call->getCallMessage()->getRequestId()]);
        if ($call->getCancelMessage()) {
            unset($this->callCancelIndex[$call->getCancelMessage()->getRequestId()]);
        }
        if ($call->getInterruptMessage()) {
            unset($this->callInterruptIndex[$call->getInterruptMessage()->getRequestId()]);
        }
    }

    /**
     * Get Call by requestID
     *
     * @param int $requestId
     * @return \Thruway\Call|boolean
     */
    public function getCallByRequestId($requestId)
    {
        $call = isset($this->callRequestIndex[$requestId]) ? $this->callRequestIndex[$requestId] : false;

        return $call;
    }

    /**
     * @return \Thruway\Procedure[]
     */
    public function getProcedures()
    {
        return $this->procedures;
    }

    /**
     * @param $id
     * @return bool|Registration
     */
    public function getRegistrationById($id)
    {
        // we are going to assume that the registration only exists in one spot
        /** @var Procedure $procedure */
        foreach ($this->procedures as $procedure) {
            /** @var Registration $registration */
            $registration = $procedure->getRegistrationById($id);

            if ($registration) {
                return $registration;
            }
        }

        return false;
    }

    /**
     * Returns true if this role handles this message.
     *
     * @param \Thruway\Message\Message $msg
     * @return boolean
     */
    public function handlesMessage(Message $msg)
    {
        $handledMsgCodes = [
            Message::MSG_CALL,
            Message::MSG_CANCEL,
            Message::MSG_REGISTER,
            Message::MSG_UNREGISTER,
            Message::MSG_YIELD,
            Message::MSG_INTERRUPT
        ];

        if (in_array($msg->getMsgCode(), $handledMsgCodes)) {
            return true;
        } elseif ($msg instanceof ErrorMessage && $msg->getErrorMsgCode() === Message::MSG_INVOCATION) {
            return true;
        } elseif ($msg instanceof ErrorMessage && $msg->getErrorMsgCode() === Message::MSG_INTERRUPT) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * process leave session
     *
     * @param \Thruway\Session $session
     */
    public function leave(Session $session)
    {
        /* @var $procedure \Thruway\Procedure */
        foreach ($this->procedures as $procedure) {
            $procedure->leave($session);
        }

        foreach ($this->callInvocationIndex as $call) {
            if ($session->getSessionId() === $call->getCallerSession()->getSessionId()) {
                $cancelMsg = new CancelMessage($call->getCallMessage()->getRequestId(), (object)[]);
                $this->processCancel($session, $cancelMsg);
            }
        }

        // remove the list of registrations
        if ($this->registrationsBySession->contains($session)) {
            $this->registrationsBySession->detach($session);
        }
    }

    /**
     * Get list registrations
     *
     * todo: this may be used by testing
     *
     * @return array
     */
    public function managerGetRegistrations()
    {
        $theRegistrations = [];

        /* @var $procedure \Thruway\Procedure */
        foreach ($this->procedures as $procedure) {
            /* @var $registration \Thruway\Registration */
            foreach ($procedure->getRegistrations() as $registration) {
                $theRegistrations[] = [
                    'id'         => $registration->getId(),
                    'name'       => $registration->getProcedureName(),
                    'session'    => $registration->getSession()->getSessionId(),
                    'statistics' => $registration->getStatistics()
                ];
            }
        }

        return [$theRegistrations];
    }
}
