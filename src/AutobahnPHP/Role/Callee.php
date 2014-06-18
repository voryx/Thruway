<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/7/14
 * Time: 12:02 PM
 */

namespace AutobahnPHP\Role;


use AutobahnPHP\AbstractSession;
use AutobahnPHP\ClientSession;
use AutobahnPHP\Message\ErrorMessage;
use AutobahnPHP\Message\InvocationMessage;
use AutobahnPHP\Message\Message;
use AutobahnPHP\Message\RegisteredMessage;
use AutobahnPHP\Message\RegisterMessage;
use AutobahnPHP\Message\UnregisteredMessage;
use AutobahnPHP\Message\YieldMessage;
use AutobahnPHP\Session;

/**
 * Class Callee
 * @package AutobahnPHP\Role
 */
class Callee extends AbstractRole
{
    /**
     * @var ClientSession
     */
    private $session;

    /**
     * @var array
     */
    private $registrations;

    /**
     * @param $session
     */
    function __construct($session)
    {
        $this->session = $session;
        $this->registrations = array();
    }


    /**
     * @param AbstractSession $session
     * @param Message $msg
     * @return mixed
     */
    public function onMessage(AbstractSession $session, Message $msg)
    {
        switch ($msg) {
            case ($msg instanceof RegisteredMessage):
                $this->processRegistered($session, $msg);
                break;
            case ($msg instanceof UnregisteredMessage):
                $this->processUnregistered($session, $msg);
                break;
            case ($msg instanceof InvocationMessage):
                $this->processInvocation($session, $msg);
                break;
            default:
                $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg));
        }
    }

    /**
     * @param ClientSession $session
     * @param RegisteredMessage $msg
     */
    public function processRegistered(ClientSession $session, RegisteredMessage $msg)
    {
        foreach ($this->registrations as $key => $registration) {
            if ($registration["request_id"] === $msg->getRequestId()) {
                $this->registrations[$key]['registration_id'] = $msg->getRegistrationId();
                break;
            }
        }
    }

    /**
     * @param ClientSession $session
     * @param UnregisteredMessage $msg
     */
    public function processUnregistered(ClientSession $session, UnregisteredMessage $msg)
    {
    }

    /**
     * @param ClientSession $session
     * @param InvocationMessage $msg
     */
    public function processInvocation(ClientSession $session, InvocationMessage $msg)
    {
        foreach ($this->registrations as $key => $registration) {
            if ($registration["registration_id"] === $msg->getRegistrationId()) {
                $arguments = $registration["callback"]($msg->getArguments());
                $options = new \stdClass();
                $yieldMsg = new YieldMessage($msg->getRequestId(), $options, [$arguments]);

                $this->session->sendMessage($yieldMsg);

                break;
            }
        }

    }

    /**
     * @param Message $msg
     * @return mixed
     */
    public function handlesMessage(Message $msg)
    {
        $handledMessages = array(
            Message::MSG_REGISTERED,
            Message::MSG_UNREGISTERED,
            Message::MSG_INVOCATION,
        );

        return in_array($msg->getMsgCode(), $handledMessages);
    }


    /**
     * @param $procedureName
     * @param $callback
     */
    public function register($procedureName, $callback)
    {

        $requestId = Session::getUniqueId();
        $registration = ["procedure_name" => $procedureName, "callback" => $callback, "request_id" => $requestId];
        array_push($this->registrations, $registration);

        $options = new \stdClass();

        $registerMsg = new RegisterMessage($requestId, $options, $procedureName);

        $this->session->sendMessage($registerMsg);


    }
} 