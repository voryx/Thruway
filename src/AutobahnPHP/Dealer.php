<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/7/14
 * Time: 12:02 PM
 */

namespace AutobahnPHP;


use AutobahnPHP\Message\CallMessage;
use AutobahnPHP\Message\CancelMessage;
use AutobahnPHP\Message\ErrorMessage;
use AutobahnPHP\Message\InvocationMessage;
use AutobahnPHP\Message\Message;
use AutobahnPHP\Message\RegisteredMessage;
use AutobahnPHP\Message\RegisterMessage;
use AutobahnPHP\Message\ResultMessage;
use AutobahnPHP\Message\UnregisteredMessage;
use AutobahnPHP\Message\UnregisterMessage;
use AutobahnPHP\Message\YieldMessage;

class Dealer extends AbstractRole
{

    /**
     * @var \SplObjectStorage
     */
    private $registrations;

    private $calls;

    function __construct()
    {
        $this->registrations = new \SplObjectStorage();
        $this->calls = new \SplObjectStorage();
    }

    /**
     * @param Session $session
     * @param Message $msg
     */
    public function onMessage(Session $session, Message $msg)
    {
        switch ($msg) {
            case ($msg instanceof RegisterMessage):
                $replyMsg = $this->processRegister($session, $msg);
                $session->sendMessage($replyMsg);
                break;
            case ($msg instanceof UnregisterMessage):
                $replyMsg = $this->processUnregister($session, $msg);
                $session->sendMessage($replyMsg);
                break;
            case ($msg instanceof YieldMessage):
                $this->processYield($session, $msg);
                break;
            case ($msg instanceof CallMessage):
                $this->processCall($session, $msg);
                break;
            case ($msg instanceof ErrorMessage):
                break;
            case ($msg instanceof CancelMessage): //Advanced
                break;
            default:
                $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg));

        }


    }

    /**
     * @param Session $session
     * @param RegisterMessage $msg
     * @return $this|RegisteredMessage
     */
    public function processRegister(Session $session, RegisterMessage $msg)
    {
        //Check to see if the procedure is already registered
        /* @registration Registration */

        $registration = $this->getRegistrationByProcedureName($msg->getProcedureName());

        if ($registration) {
            $errorMsg = ErrorMessage::createErrorMessageFromMessage($msg);

            echo 'Already Registered: ' . $registration->getProcedureName();

            return $errorMsg->setErrorURI('wamp.error.procedure_already_exists');
        }


        $registration = new Registration($session, $msg->getProcedureName());
        $this->registrations->attach($registration);

        echo 'Registered: ' . $registration->getProcedureName();

        return new RegisteredMessage($msg->getRequestId(), $registration->getId());
    }

    public function processUnregister(Session $session, UnregisterMessage $msg)
    {
        //find the procedure by request id
        $this->registrations->rewind();
        while ($this->registrations->valid()) {
            $registration = $this->registrations->current();
            if ($registration->getId() == $msg->getRegistrationId()) {
                $this->registrations->next();
                echo 'Unegistered: ' . $registration->getProcedureName();
                $this->registrations->detach($registration);

                return new UnregisteredMessage($msg->getRequestId());
            }
        }

        $errorMsg = ErrorMessage::createErrorMessageFromMessage($msg);
        echo 'No registration: ' . $msg->getRegistrationId();

        return $errorMsg->setErrorURI('wamp.error.no_such_registration');

    }

    public function processCall(Session $session, CallMessage $msg)
    {

        $registration = $this->getRegistrationByProcedureName($msg->getProcedureName());
        if (!$registration) {
            $errorMsg = ErrorMessage::createErrorMessageFromMessage($msg);
            echo 'No registration: ' . $msg->getRegistrationId();

            $errorMsg->setErrorURI('wamp.error.no_such_registration');
            $session->sendMessage($errorMsg);

            return false;
        }

        $invocationMessage = InvocationMessage::createMessageFrom($msg, $registration);

        $call = new Call($msg, $session, $invocationMessage, $registration->getSession());

        $this->calls->attach($call);

        $registration->getSession()->sendMessage($invocationMessage);

    }

    public function processYield(Session $session, YieldMessage $msg)
    {
        $call = $this->getCallByRequestId($msg->getRequestId());

        if (!$call) {
            $errorMsg = ErrorMessage::createErrorMessageFromMessage($msg);
            echo 'No call for request: ' . $msg->getRequestId();

            $errorMsg->setErrorURI('wamp.error.no_such_procedure');
            $session->sendMessage($errorMsg);

            return false;
        }

        $details = new \stdClass();

        $this->calls->detach($call);

        $resultMessage = new ResultMessage(
            $call->getCallMessage()->getRequestId(),
            $details,
            $msg->getArguments(),
            $msg->getArgumentsKw()
        );

        $call->getCallerSession()->sendMessage($resultMessage);
    }

    public function processError(Session $session, ErrorMessage $msg)
    {
        //@todo
    }

    public function getRegistrationByProcedureName($procedureName)
    {
        /* @var $registration \AutobahnPHP\Registration */
        foreach ($this->registrations as $registration) {
            if ($registration->getProcedureName() == $procedureName) {
                return $registration;
            }
        }

        return false;
    }

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

    public function handlesMessage(Message $msg)
    {
        $handledMessages = array(
            Message::MSG_CALL,
            Message::MSG_ERROR,
            Message::MSG_CANCEL,
            Message::MSG_REGISTER,
            Message::MSG_UNREGISTER,
            Message::MSG_YIELD
        );

        return in_array($msg->getMsgCode(), $handledMessages);
    }


}