<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/7/14
 * Time: 12:02 PM
 */

namespace Thruway\Role;


use Psr\Log\NullLogger;
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
use Thruway\Message\UnregisterMessage;
use Thruway\Message\YieldMessage;
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
     * @var NullLogger
     */
    private $logger;

    function __construct($logger = null)
    {
        if (!$logger){
            $this->logger = new NullLogger();
        } else {
            $this->logger = $logger;
        }

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
                $this->logger->info("---Setting registration_id for " . $registration['procedure_name'] . " (" . $key . ")\n");
                $this->registrations[$key]['registration_id'] = $msg->getRegistrationId();

                if ($this->registrations[$key]['futureResult'] instanceof Deferred) {
                    /** @var Deferred $futureResult */
                    $futureResult = $this->registrations[$key]['futureResult'];
                    $futureResult->resolve();
                }
                return;
            }
        }
        $this->logger->error("---Got a Registered Message, but the request ids don't match\n");
    }

    /**
     * @param ClientSession $session
     * @param UnregisteredMessage $msg
     */
    public function processUnregistered(ClientSession $session, UnregisteredMessage $msg)
    {
        foreach ($this->registrations as $key => $registration) {
            if (isset($registration['unregister_request_id'])) {
                if ($registration["unregister_request_id"] == $msg->getRequestId()) {
                    /** @var Deferred $deferred */
                    $deferred = $registration['unregister_deferred'];
                    $deferred->resolve();

                    unset($this->registrations[$key]);
                    return;
                }
            }
        }
        $this->logger->error("---Got an Unregistered Message, but couldn't find corresponding request.\n");
    }

    /**
     * @param ClientSession $session
     * @param InvocationMessage $msg
     */
    public function processInvocation(ClientSession $session, InvocationMessage $msg)
    {
        foreach ($this->registrations as $key => $registration) {
            if (!isset($registration["registration_id"])) {
                $this->logger->info("Registration_id not set for " . $registration['procedure_name'] . "\n");
            } else {
                if ($registration["registration_id"] === $msg->getRegistrationId()) {

                    if ($registration['callback'] === null) {
                        // this is where calls end up if the client has called unregister but
                        // have not yet received confirmation from the router about the
                        // unregistration
                        $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg,"thruway.error.unregistering"));

                        return;
                    }

                    $results = $registration["callback"]($msg->getArguments(), $msg->getArgumentsKw(), $msg->getDetails());

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
        if ($msg->getErrorMsgCode() == Message::MSG_REGISTER) {
            $this->handleErrorRegister($session, $msg);
        } elseif ($msg->getErrorMsgCode() == Message::MSG_UNREGISTER) {
            $this->handleErrorUnregister($session, $msg);
        } else {
            $this->logger->error("Unhandled error message: " . $msg->getSerializedMessage() . "\n");
        }

    }

    public function handleErrorRegister(ClientSession $session, ErrorMessage $msg) {
        foreach ($this->registrations as $key => $registration) {
            if ($registration["request_id"] === $msg->getRequestId()) {
                /** @var Deferred $deferred */
                $deferred = $registration['futureResult'];
                $deferred->reject($msg);
                unset($this->registrations[$key]);
                break;
            }
        }
    }

    public function handleErrorUnregister(ClientSession $session, ErrorMessage $msg) {
        foreach ($this->registrations as $key => $registration) {
            if (isset($registration['unregister_request_id'])) {
                if ($registration["unregister_request_id"] === $msg->getRequestId()) {
                    /** @var Deferred $deferred */
                    $deferred = $registration['unregister_deferred'];
                    $deferred->reject($msg);

                    // I guess we get rid of the registration now?
                    unset($this->registrations[$key]);
                    break;
                }
            }
        }
    }

    /**
     * Returns true if this role handles this message.
     * Error messages are checked according to the
     * message the error corresponds to.
     *
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

        $codeToCheck = $msg->getMsgCode();

        if ($codeToCheck instanceof ErrorMessage) $codeToCheck = $msg->getErrorMsgCode();

        if (in_array($codeToCheck, $handledMsgCodes)) {
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

    /**
     * @param \Thruway\ClientSession $session
     * @param $Uri
     * @throws \Exception
     * @return \React\Promise\Promise
     */
    public function unregister(ClientSession $session, $Uri)
    {
        // TODO: maybe add an option to wait for pending calls to finish

        $registration = null;

        foreach($this->registrations as $k => $r) {
            if (isset($r['procedure_name'])) {
                if ($r['procedure_name'] == $Uri) {
                    $registration = &$this->registrations[$k];
                    break;
                }
            }
        }

        if ($registration === null) {
            throw new \Exception("registration not found");
        }

        // we remove the callback from the client here
        // because we don't want the client to respond to any more calls
        $registration['callback'] = null;

        $futureResult = new Deferred();

        if (!isset($registration["registration_id"])) {
            // this would happen if the registration was never acknowledged by the router
            // we should remove the registration and resolve any pending deferreds
            $this->logger->error("Registration ID is not set while attempting to unregister " . $Uri . "\n");

            // reject the pending registration
            $registration['futureResult']->reject();

            // TODO: need to figure out what to do in this off chance
            // We should still probably return a promise here that just rejects
            // there is an issue with the pending registration too that
            // the router may have a "REGISTERED" in transit and may still think that is
            // good to go - so maybe still send the unregister?
        }

        $requestId = Session::getUniqueId();

        // save the request id so we can find this in the registration
        // list to call the deferred and remove it from the list
        $registration['unregister_request_id'] = $requestId;
        $registration['unregister_deferred'] = $futureResult;

        $unregisterMsg = new UnregisterMessage($requestId, $registration['registration_id']);

        $session->sendMessage($unregisterMsg);

        return $futureResult->promise();
    }

    // This belongs somewhere else I am thinking
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

    /**
     * @return NullLogger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param NullLogger $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }


} 