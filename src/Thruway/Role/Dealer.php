<?php

namespace Thruway\Role;


use Thruway\AbstractSession;
use Thruway\Call;
use Thruway\Manager\ManagerDummy;
use Thruway\Manager\ManagerInterface;
use Thruway\Message\CallMessage;
use Thruway\Message\ErrorMessage;
use Thruway\Message\InvocationMessage;
use Thruway\Message\Message;
use Thruway\Message\RegisteredMessage;
use Thruway\Message\RegisterMessage;
use Thruway\Message\ResultMessage;
use Thruway\Message\UnregisteredMessage;
use Thruway\Message\UnregisterMessage;
use Thruway\Message\YieldMessage;
use Thruway\Registration;
use Thruway\Session;

/**
 * Class Dealer
 *
 * @package Thruway\Role
 */
class Dealer extends AbstractRole
{

    /**
     * @var \SplObjectStorage
     */
    private $registrations;

    /**
     * @var \SplObjectStorage
     */
    private $calls;

    /**
     * @var \Thruway\Manager\ManagerInterface
     */
    private $manager;

    /**
     * Constructor
     *
     * @param \Thruway\Manager\ManagerInterface $manager
     */
    function __construct(ManagerInterface $manager = null)
    {
        $this->registrations = new \SplObjectStorage();
        $this->calls         = new \SplObjectStorage();

        if ($manager === null) {
            $manager = new ManagerDummy();
        }
        $this->setManager($manager);


    }

    /**
     * process message
     *
     * @param \Thruway\Session $session
     * @param \Thruway\Message\Message $msg
     * @return mixed|void
     */
    public function onMessage(AbstractSession $session, Message $msg)
    {

        if ($msg instanceof RegisterMessage):
            $this->processRegister($session, $msg);
        elseif ($msg instanceof UnregisterMessage):
            $replyMsg = $this->processUnregister($session, $msg);
            $session->sendMessage($replyMsg);
        elseif ($msg instanceof YieldMessage):
            $this->processYield($session, $msg);
        elseif ($msg instanceof CallMessage):
            $this->processCall($session, $msg);
        elseif ($msg instanceof ErrorMessage):
            $this->processError($session, $msg);
        //elseif ($msg instanceof CancelMessage): //Advanced

        else:
            $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg));
        endif;

    }

    /**
     * process RegisterMessage
     *
     * @param \Thruway\Session $session
     * @param \Thruway\Message\RegisterMessage $msg
     */
    private function processRegister(Session $session, RegisterMessage $msg)
    {
        //Check to see if the procedure is already registered
        /* @var $registration \Thruway\Registration */
        $registration = $this->getRegistrationByProcedureName($msg->getProcedureName());

        if ($registration) {
            $errorMsg = ErrorMessage::createErrorMessageFromMessage($msg);

            $this->manager->error('Already Registered: ' . $registration->getProcedureName());

            $errorMsg->setErrorURI('wamp.error.procedure_already_exists');

            $options = $msg->getOptions();
            if (isset($options['replace_orphaned_session']) && $options['replace_orphaned_session'] == "yes") {
                $this->getManager()->debug("Pinging existing registrant");
                $registration->getSession()->ping(5)
                    ->then(function ($res) use ($registration, $session, $errorMsg) {
                            // the ping came back - send procedure_already_exists
                            $session->sendMessage($errorMsg);
                        },
                        function ($r) use ($registration, $session, $msg) {
                            $this->manager->debug("Removing session " . $registration->getSession()->getSessionId() . " because it didn't respond to ping.");
                            // bring down the exiting session because the
                            // ping timed out
                            $deadSession = $registration->getSession();

                            $deadSession->shutdown();

                            // complete this registration now
                            $this->completeRegistration($session, $msg);
                        });
            } else {
                $session->sendMessage($errorMsg);
            }
        } else {
            $this->completeRegistration($session, $msg);
        }


    }

    /**
     * process complete registration
     *
     * @param \Thruway\Session $session
     * @param \Thruway\Message\RegisterMessage $msg
     */
    public function completeRegistration(Session $session, RegisterMessage $msg)
    {
        $registration = new Registration($session, $msg->getProcedureName());

        $options = (array)$msg->getOptions();
        if (isset($options['discloseCaller']) && $options['discloseCaller'] === true) {
            $registration->setDiscloseCaller(true);
        }

        $this->registrations->attach($registration);

        $this->manager->debug('Registered: ' . $registration->getProcedureName());

        $session->sendMessage(new RegisteredMessage($msg->getRequestId(), $registration->getId()));
    }

    /**
     * process UnregisterMessage
     *
     * @param \Thruway\Session $session
     * @param \Thruway\Message\UnregisterMessage $msg
     * @throws \Exception
     * @return \Thruway\Message\UnregisteredMessage|\Thruway\Message\ErrorMessage
     */
    private function processUnregister(Session $session, UnregisterMessage $msg)
    {
        //find the procedure by registration id
        $this->registrations->rewind();
        while ($this->registrations->valid()) {
            $registration = $this->registrations->current();
            if ($registration->getId() == $msg->getRegistrationId()) {
                $this->registrations->next();


                // make sure the session is the correct session
                if ($registration->getSession() !== $session) {
                    $errorMsg = ErrorMessage::createErrorMessageFromMessage($msg);
                    $errorMsg->setErrorURI("wamp.error.no_such_registration");
                    $this->manager->warning("Tried to unregister a procedure that belongs to a different session.");

                    return $errorMsg;
                }

                $this->manager->debug('Unegistered: ' . $registration->getProcedureName());
                $this->registrations->detach($registration);

                return new UnregisteredMessage($msg->getRequestId());
            } else {
                $this->registrations->next();
            }
        }

        $errorMsg = ErrorMessage::createErrorMessageFromMessage($msg);
        $this->manager->error('No registration: ' . $msg->getRegistrationId());

        return $errorMsg->setErrorURI('wamp.error.no_such_registration');

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

        $registration = $this->getRegistrationByProcedureName($msg->getProcedureName());
        if (!$registration) {
            $errorMsg = ErrorMessage::createErrorMessageFromMessage($msg);
            $this->manager->error('No registration for call message: ' . $msg->getProcedureName());

            $errorMsg->setErrorURI('wamp.error.no_such_registration');
            $session->sendMessage($errorMsg);

            return false;
        }

        $invocationMessage = InvocationMessage::createMessageFrom($msg, $registration);

        $details = [];
        if ($registration->getDiscloseCaller() === true && $session->getAuthenticationDetails()) {
            $details = [
                "caller"     => $session->getSessionId(),
                "authid"     => $session->getAuthenticationDetails()->getAuthId(),
                //"authrole" => $session->getAuthenticationDetails()->getAuthRole(),
                "authmethod" => $session->getAuthenticationDetails()->getAuthMethod(),
            ];
        }

        // TODO: check to see if callee supports progressive call
        $callOptions   = $msg->getOptions();
        $isProgressive = false;
        if (is_array($callOptions) && isset($callOptions['receive_progress']) && $callOptions['receive_progress']) {
            $details       = array_merge($details, ["receive_progress" => true]);
            $isProgressive = true;
        }

        // if nothing was added to details - change ot stdClass so it will serialize correctly
        if (count($details) == 0) {
            $details = new \stdClass();
        }
        $invocationMessage->setDetails($details);

        $call = new Call($msg, $session, $invocationMessage, $registration->getSession());

        $call->setIsProgressive($isProgressive);

        $this->calls->attach($call);

        $registration->getSession()->sendMessage($invocationMessage);

    }

    /**
     * process YieldMessage
     *
     * @param \Thruway\Session $session
     * @param \Thruway\Message\YieldMessage $msg
     * @return boolean|void
     */
    private function processYield(Session $session, YieldMessage $msg)
    {
        $call = $this->getCallByRequestId($msg->getRequestId());

        if (!$call) {
            $errorMsg = ErrorMessage::createErrorMessageFromMessage($msg);
            $this->manager->error('No call for yield message: ' . $msg->getRequestId());

            $errorMsg->setErrorURI('wamp.error.no_such_procedure');
            $session->sendMessage($errorMsg);

            return false;
        }

        $details = new \stdClass();

        $yieldOptions = $msg->getOptions();
        if (is_array($yieldOptions) && isset($yieldOptions['progress']) && $yieldOptions['progress']) {
            if ($call->isProgressive()) {
                $details = ["progress" => true];
            } else {
                // not sure what to do here - just going to drop progress
                // if we are getting progress messages that the caller didn't ask for
            }
        } else {
            $this->calls->detach($call);

        }

        $resultMessage = new ResultMessage(
            $call->getCallMessage()->getRequestId(),
            $details,
            $msg->getArguments(),
            $msg->getArgumentsKw()
        );

        $call->getCallerSession()->sendMessage($resultMessage);
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
        }
    }

    /**
     * Process InvocationError
     *
     * @param \Thruway\Session $session
     * @param \Thruway\Message\ErrorMessage $msg
     * @return boolean|void
     */
    private function processInvocationError(Session $session, ErrorMessage $msg)
    {
        $call = $this->getCallByRequestId($msg->getRequestId());

        if (!$call) {
            $errorMsg = ErrorMessage::createErrorMessageFromMessage($msg);
            $this->manager->error('No call for invocation error message: ' . $msg->getRequestId());

            // TODO: do we send a message back to the callee?
            $errorMsg->setErrorURI('wamp.error.no_such_procedure');
            $session->sendMessage($errorMsg);

            return false;
        }

        $this->calls->detach($call);

        $errorMsg = ErrorMessage::createErrorMessageFromMessage($call->getCallMessage());

        $errorMsg->setErrorURI($msg->getErrorURI());
        $errorMsg->setArguments($msg->getArguments());
        $errorMsg->setArgumentsKw($msg->getArgumentsKw());

        $call->getCallerSession()->sendMessage($errorMsg);
    }

    /**
     * Get registration by procedureName
     *
     * @param string $procedureName
     * @return \Thruway\Registration|boolean
     */
    public function getRegistrationByProcedureName($procedureName)
    {
        /* @var $registration \Thruway\Registration */
        foreach ($this->registrations as $registration) {
            if ($registration->getProcedureName() == $procedureName) {
                return $registration;
            }
        }

        return false;
    }

    /**
     * Get Call by requestID
     *
     * @param $requestId
     * @return \Thruway\Call|boolean
     */
    public function getCallByRequestId($requestId)
    {
        /* @var $call Call */
        foreach ($this->calls as $call) {
            if ($call->getInvocationMessage()->getRequestId() == $requestId) {
                return $call;
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
            Message::MSG_YIELD
        ];

        if (in_array($msg->getMsgCode(), $handledMsgCodes)) {
            return true;
        } elseif ($msg instanceof ErrorMessage && $msg->getErrorMsgCode() == Message::MSG_INVOCATION) {
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
        $this->registrations->rewind();
        while ($this->registrations->valid()) {
            /* @var $registration Registration */
            $registration = $this->registrations->current();
            $this->registrations->next();
            if ($registration->getSession() == $session) {
                $this->manager->debug("Leaving and unegistering: {$registration->getProcedureName()}");
                $this->registrations->detach($registration);
            }
        }
    }

    /**
     * @param \Thruway\Manager\ManagerInterface $manager
     */
    public function setManager($manager)
    {
        $this->manager = $manager;


    }

//    public function startManager() {
//        $this->manager->addCallable("dealer.get_registrations", array($this, "managerGetRegistrations"));
//    }

    /**
     * @return \Thruway\Manager\ManagerInterface
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * Get list registrations
     *
     * @return array
     */
    public function managerGetRegistrations()
    {
        $theRegistrations = [];

        /* @var $registration \Thruway\Registration */
        foreach ($this->registrations as $registration) {
            $theRegistrations[] = [
                "id"      => $registration->getId(),
                "name"    => $registration->getProcedureName(),
                "session" => $registration->getSession()->getSessionId()
            ];
        }

        return [$theRegistrations];
    }

}
