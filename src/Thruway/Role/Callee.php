<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/7/14
 * Time: 12:02 PM
 */

namespace Thruway\Role;


use React\Promise\Deferred;
use React\Promise\Promise;
use Thruway\AbstractSession;
use Thruway\ClientSession;
use Thruway\Message\ErrorMessage;
use Thruway\Message\InvocationMessage;
use Thruway\Message\Message;
use Thruway\Message\RegisteredMessage;
use Thruway\Message\RegisterMessage;
use Thruway\Message\UnregisteredMessage;
use Thruway\Message\YieldMessage;
use Thruway\Registration;
use Thruway\Session;

/**
 * Class Callee
 * @package Thruway\Role
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
                echo "---Setting registration_id for " . $registration['procedure_name'] . " (" . $key . ")\n";
                $this->registrations[$key]['registration_id'] = $msg->getRegistrationId();

                if ($this->registrations[$key]['futureResult'] instanceof Deferred) {
                    /** @var Deferred $futureResult */
                    $futureResult = $this->registrations[$key]['futureResult'];
                    $futureResult->resolve();
                }
                return;
            }
        }
        echo "---Got a Registered Message, but the request ids don't match\n";
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
            if (!isset($registration["registration_id"])) {
                echo "Registration_id not set for " . $registration['procedure_name'] . "\n";
            } else {
                if ($registration["registration_id"] === $msg->getRegistrationId()) {
                    $results = $registration["callback"]($msg->getArguments(), $msg->getDetails());

                    if ($results instanceof Promise) {
                        // the result is a promise - hook up stuff as a callback
                        $results->then(
                            function ($promiseResults) use ($msg, $session) {
                                $promiseResults = is_array($promiseResults) ? $promiseResults : [$promiseResults];
                                $promiseResults = !$this::is_list($promiseResults) ? [$promiseResults]: $promiseResults;
                                $options = new \stdClass();
                                $yieldMsg = new YieldMessage($msg->getRequestId(), $options, $promiseResults);

                                $session->sendMessage($yieldMsg);
                            }
                        );
                    } else {
                        $results = !$this::is_list($results) ? [$results]: $results;
                        $options = new \stdClass();
                        $yieldMsg = new YieldMessage($msg->getRequestId(), $options, $results);

                        $session->sendMessage($yieldMsg);
                    }
                    break;
                }
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
     * @param \Thruway\ClientSession $session
     * @param $procedureName
     * @param $callback
     * @param null $options
     * @return \React\Promise\Promise
     */
    public function register(ClientSession $session, $procedureName, $callback, $options = null)
    {
        $futureResult = new Deferred();

        $requestId = Session::getUniqueId();
        $options = isset($options) ? $options : new \stdClass();
        $registration = [
            "procedure_name" => $procedureName,
            "callback" => $callback,
            "request_id" => $requestId,
            'options' => $options,
            'futureResult' => $futureResult
        ];

        array_push($this->registrations, $registration);

        $registerMsg = new RegisterMessage($requestId, $options, $procedureName);

        $session->sendMessage($registerMsg);

        return $futureResult->promise();
    }

    public static function is_list($array)
    {
        if (!is_array($array)){
            return false;
        }

        // Keys of the array
        $keys = array_keys($array);

        // If the array keys of the keys match the keys, then the array must
        // not be associative (e.g. the keys array looked like {0:0, 1:1...}).
        return array_keys($keys) === $keys;
    }
} 