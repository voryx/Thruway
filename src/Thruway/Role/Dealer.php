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
use Thruway\Message\RegisterMessage;
use Thruway\Message\ResultMessage;
use Thruway\Message\UnregisteredMessage;
use Thruway\Message\UnregisterMessage;
use Thruway\Message\YieldMessage;
use Thruway\Procedure;
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
     * @var array
     */
    private $procedures;

    /**
     * @var \Thruway\Manager\ManagerInterface
     */
    private $manager;

    /**
     * Constructor
     *
     * @param \Thruway\Manager\ManagerInterface $manager
     */
    public function __construct(ManagerInterface $manager = null)
    {
        $this->procedures    = [];
        $manager             = $manager === null ? $manager : new ManagerDummy();

        $this->setManager($manager);
    }

    /**
     * process message
     *
     * @param \Thruway\AbstractSession $session
     * @param \Thruway\Message\Message $msg
     * @return mixed|void
     */
    public function onMessage(AbstractSession $session, Message $msg)
    {

        if ($msg instanceof RegisterMessage):
            $this->processRegister($session, $msg);
        elseif ($msg instanceof UnregisterMessage):
            $this->processUnregister($session, $msg);
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
        // check for valid URI
        if (!static::uriIsValid($msg->getProcedureName())) {
            $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg, 'wamp.error.invalid_uri'));
            return;
        }

        //Check to see if the procedure is already registered
        /** @var Procedure $procedure */
        if (isset($this->procedures[$msg->getProcedureName()])) {
            $procedure = $this->procedures[$msg->getProcedureName()];
        } else {
            $procedure =  new Procedure($msg->getProcedureName());
            $this->procedures[$msg->getProcedureName()] = $procedure;
        }

        $procedure->processRegister($session, $msg);
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
        // we are going to assume that the registration only exists in one spot
        /** @var Procedure $procedure */
        foreach ($this->procedures as $procedure) {
            /** @var Registration $registration */
            $registration = $procedure->getRegistrationById($msg->getRegistrationId());

            if ($registration) {
                $procedure->processUnregister($session, $msg);
                return;
            }
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
        if (!static::uriIsValid($msg->getProcedureName())) {
            $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg, 'wamp.error.invalid_uri'));
            return;
        }

        if (!isset($this->procedures[$msg->getProcedureName()])) {
            $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg, 'wamp.error.no_such_procedure'));
            return;
        }

        /** @var Procedure $procedure */
        $procedure = $this->procedures[$msg->getProcedureName()];

        $procedure->processCall($session, $msg);
    }

    /**
     * process YieldMessage
     *
     * @param \Thruway\Session $session
     * @param \Thruway\Message\YieldMessage $msg
     */
    private function processYield(Session $session, YieldMessage $msg)
    {
        /** @var Procedure $procedure */
        foreach ($this->procedures as $procedure) {
            $call = $procedure->getCallByRequestId($msg->getRequestId());
            if ($call) {
                $call->processYield($session, $msg);

                return;
            }
        }

        // TODO: This is an error - can I return a yield error?


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

        $call->getRegistration()->removeCall($call);

        $errorMsg = ErrorMessage::createErrorMessageFromMessage($call->getCallMessage());

        $errorMsg->setErrorURI($msg->getErrorURI());
        $errorMsg->setArguments($msg->getArguments());
        $errorMsg->setArgumentsKw($msg->getArgumentsKw());

        $call->getCallerSession()->sendMessage($errorMsg);
    }

    /**
     * Get Call by requestID
     *
     * @param int $requestId
     * @return \Thruway\Call|boolean
     */
    public function getCallByRequestId($requestId)
    {
        /** @var Procedure $procedure */
        foreach ($this->procedures as $procedure) {
            $call = $procedure->getCallByRequestId($requestId);
            if ($call) return $call;
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
        /** @var Procedure $procedure */
        foreach($this->procedures as $procedure) {
            $procedure->leave($session);
        }
    }

    /**
     * Set manager
     * 
     * @param \Thruway\Manager\ManagerInterface $manager
     */
    public function setManager($manager)
    {
        $this->manager = $manager;


    }

    /**
     * Get manager
     * 
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

        /** @var Procedure $procedure */
        foreach ($this->procedures as $procedure) {
            /* @var $registration \Thruway\Registration */
            foreach ($procedure->getRegistrations() as $registration) {
                $theRegistrations[] = [
                    "id" => $registration->getId(),
                    "name" => $registration->getProcedureName(),
                    "session" => $registration->getSession()->getSessionId()
                ];
            }
        }

        return [$theRegistrations];
    }

}
