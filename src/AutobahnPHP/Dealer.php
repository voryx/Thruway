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
use AutobahnPHP\Message\Message;
use AutobahnPHP\Message\RegisteredMessage;
use AutobahnPHP\Message\RegisterMessage;
use AutobahnPHP\Message\UnregisterMessage;
use AutobahnPHP\Message\YieldMessage;

class Dealer extends AbstractRole
{

    /**
     * @var \SplObjectStorage
     */
    private $registrations;

    function __construct()
    {
        $this->registrations = new \SplObjectStorage();
    }

    /**
     * @param Session $session
     * @param Message $msg
     */
    public function onMessage(Session $session, Message $msg)
    {
        switch ($msg) {
            case ($msg instanceof RegisterMessage):
                $replyMsg = $this->register($session, $msg);
                break;
            case ($msg instanceof UnregisterMessage):
                $replyMsg = $this->unregister($session, $msg);
                break;
            case ($msg instanceof YieldMessage):
                break;
            case ($msg instanceof CallMessage):
                break;
            case ($msg instanceof ErrorMessage):
                break;
            case ($msg instanceof CancelMessage): //Advanced
                break;
        }

        if (!isset($replyMsg)) {
            $replyMsg = ErrorMessage::createErrorMessageFromMessage($msg);
        }

        $session->sendMessage($replyMsg);

    }

    /**
     * @param Session $session
     * @param RegisterMessage $msg
     * @return $this|RegisteredMessage
     */
    public function register(Session $session, RegisterMessage $msg)
    {
        //Check to see if the procedure is already registered
        /* @registration Registration */
        foreach ($this->registrations as $registration) {
            if ($registration->getProcedureName() == $msg->getProcedureName()) {
                $errorMsg = ErrorMessage::createErrorMessageFromMessage($msg);

                echo 'Already Registered: ' . $registration->getProcedureName();

                return $errorMsg->setErrorURI('wamp.error.procedure_already_exists');
            }
        }

        $registration = new Registration($session, $msg->getProcedureName(), $msg->getRequestId());
        $this->registrations->attach($registration);

        echo 'Registered: ' . $registration->getProcedureName();

        return new RegisteredMessage($msg->getRequestId(), $registration->getId());
    }

    public function unregister(Session $session, UnregisterMessage $msg)
    {

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