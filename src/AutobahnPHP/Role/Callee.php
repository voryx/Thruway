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
     * @var array
     */
    private $registrations;

    /**
     * @param $session
     */
    function __construct()
    {
        $this->registrations = array();
    }


    /**
     * @param AbstractSession $session
     * @param Message $msg
     * @return mixed
     */
    public function onMessage(AbstractSession $session, Message $msg)
    {

        if ($msg instanceof RegisteredMessage):
            $this->processRegistered($session, $msg);
        elseif ($msg instanceof UnregisteredMessage):
            $this->processUnregistered($session, $msg);
        elseif ($msg instanceof InvocationMessage):
            $this->processInvocation($session, $msg);
        elseif ($msg instanceof ErrorMessage):
            $this->processError($session, $msg);
        else:
            $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg));
        endif;
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

                $session->sendMessage($yieldMsg);

                break;
            }
        }

    }

    /**
     * @param ClientSession $session
     * @param ErrorMessage $msg
     */
    public function processError(ClientSession $session, ErrorMessage $msg)
    {
        foreach ($this->registrations as $key => $registration) {
            if ($registration["request_id"] === $msg->getRequestId()) {

                //TODO: actually do something with this error

                unset($this->registrations[$key]);
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

        $handledMsgCodes = array(
            Message::MSG_REGISTERED,
            Message::MSG_UNREGISTERED,
            Message::MSG_INVOCATION,
        );

        if (in_array($msg->getMsgCode(), $handledMsgCodes)) {
            return true;
        } elseif ($msg instanceof ErrorMessage && $msg->getErrorMsgCode() == Message::MSG_REGISTER) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * @param $procedureName
     * @param $callback
     */
    public function register(ClientSession $session, $procedureName, $callback)
    {
        $requestId = Session::getUniqueId();
        $options = new \stdClass();
        $registration = [
            "procedure_name" => $procedureName,
            "callback" => $callback,
            "request_id" => $requestId,
            'options' => $options
        ];

        array_push($this->registrations, $registration);

        $registerMsg = new RegisterMessage($requestId, $options, $procedureName);

        $session->sendMessage($registerMsg);

    }

} 