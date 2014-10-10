<?php

namespace Thruway\Role;

use Psr\Log\LoggerInterface;
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
use Thruway\Result;
use Thruway\Session;

/**
 * Class Callee
 *
 * @package Thruway\Role
 */
class Callee extends AbstractRole
{

    /**
     * @var array
     */
    private $registrations;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger        = $logger ? $logger : new NullLogger();
        $this->registrations = [];
    }


    /**
     * Handle process reveiced message
     *
     * @param \Thruway\AbstractSession $session
     * @param \Thruway\Message\Message $msg
     * @return mixed|void
     */
    public function onMessage(AbstractSession $session, Message $msg)
    {
        if ($msg instanceof RegisteredMessage):
            $this->processRegistered($msg);
        elseif ($msg instanceof UnregisteredMessage):
            $this->processUnregistered($msg);
        elseif ($msg instanceof InvocationMessage):
            $this->processInvocation($session, $msg);
        elseif ($msg instanceof ErrorMessage):
            $this->processError($session, $msg);
        else:
            $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg));
        endif;
    }

    /**
     * Process RegisteredMessage
     *
     * @param \Thruway\Message\RegisteredMessage $msg
     * @return void
     */
    protected function processRegistered(RegisteredMessage $msg)
    {
        foreach ($this->registrations as $key => $registration) {
            if ($registration["request_id"] === $msg->getRequestId()) {
                $this->logger->info("---Setting registration_id for " . $registration['procedure_name'] . " (" . $key . ")\n");
                $this->registrations[$key]['registration_id'] = $msg->getRegistrationId();

                if ($this->registrations[$key]['futureResult'] instanceof Deferred) {
                    /* @var $futureResult \React\Promise\Deferred */
                    $futureResult = $this->registrations[$key]['futureResult'];
                    $futureResult->resolve();
                }
                return;
            }
        }
        $this->logger->error("---Got a Registered Message, but the request ids don't match\n");
    }

    /**
     * Process Unregistered
     *
     * @param \Thruway\Message\UnregisteredMessage $msg
     */
    protected function processUnregistered(UnregisteredMessage $msg)
    {
        foreach ($this->registrations as $key => $registration) {
            if (isset($registration['unregister_request_id'])) {
                if ($registration["unregister_request_id"] == $msg->getRequestId()) {
                    /** @var $deferred \React\Promise\Deferred */
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
     * Process InvocationMessage
     *
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Message\InvocationMessage $msg
     */
    protected function processInvocation(ClientSession $session, InvocationMessage $msg)
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
                        $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg));

                        return;
                    }

                    try {
                        $results = $registration["callback"]($msg->getArguments(), $msg->getArgumentsKw(), $msg->getDetails());

                        if ($results instanceof Promise) {
                            $this->processResultAsPromise($results, $msg, $session, $registration);
                        } else {
                            $this->processResultAsArray($results, $msg, $session);
                        }

                    } catch (\Exception $e) {
                        $errorMsg = ErrorMessage::createErrorMessageFromMessage($msg);
                        $errorMsg->setErrorURI($registration['procedure_name'] . '.error');
                        $errorMsg->setArguments([$e->getMessage()]);
                        $errorMsg->setArgumentsKw($e);

                        $session->sendMessage($errorMsg);
                    }

                    break;
                }
            }
        }

    }

    /**
     *  Process a result as a promise
     *
     * @param \React\Promise\Promise $promise
     * @param \Thruway\Message\InvocationMessage $msg
     * @param \Thruway\ClientSession $session
     * @param array $registration
     */
    private function processResultAsPromise(Promise $promise, InvocationMessage $msg, ClientSession $session, $registration)
    {

        $promise->then(
            function ($promiseResults) use ($msg, $session) {
                $options = new \stdClass();
                if ($promiseResults instanceof Result) {
                    $yieldMsg = new YieldMessage($msg->getRequestId(), $options,
                        $promiseResults->getArguments(), $promiseResults->getArgumentsKw());
                } else {
                    $promiseResults = is_array($promiseResults) ? $promiseResults : [$promiseResults];
                    $promiseResults = !$this::is_list($promiseResults) ? [$promiseResults] : $promiseResults;

                    $yieldMsg = new YieldMessage($msg->getRequestId(), $options, $promiseResults);
                }

                $session->sendMessage($yieldMsg);
            },
            function () use ($msg, $session, $registration) {
                $errorMsg = ErrorMessage::createErrorMessageFromMessage($msg);
                $errorMsg->setErrorURI($registration['procedure_name'] . '.error');

                $session->sendMessage($errorMsg);
            },
            function ($results) use ($msg, $session, $registration) {
                $options = ["progress" => true];
                if ($results instanceof Result) {
                    $yieldMsg = new YieldMessage($msg->getRequestId(), $options, $results->getArguments(),
                        $results->getArgumentsKw());
                } else {
                    $results = is_array($results) ? $results : [$results];
                    $results = !$this::is_list($results) ? [$results] : $results;

                    $yieldMsg = new YieldMessage($msg->getRequestId(), $options, $results);
                }

                $session->sendMessage($yieldMsg);
            }
        );
    }

    /**
     * Process result as an array
     *
     * @param mixed $results
     * @param \Thruway\Message\InvocationMessage $msg
     * @param \Thruway\ClientSession $session
     */
    private function processResultAsArray($results, InvocationMessage $msg, ClientSession $session)
    {
        $options = new \stdClass();
        if ($results instanceof Result) {
            $yieldMsg = new YieldMessage($msg->getRequestId(), $options, $results->getArguments(),
                $results->getArgumentsKw());
        } else {
            $results = is_array($results) ? $results : [$results];
            $results = !$this::is_list($results) ? [$results] : $results;

            $yieldMsg = new YieldMessage($msg->getRequestId(), $options, $results);
        }

        $session->sendMessage($yieldMsg);
    }

    /**
     * Process ErrorMessage
     *
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Message\ErrorMessage $msg
     */
    public function processError(ClientSession $session, ErrorMessage $msg)
    {
        if ($msg->getErrorMsgCode() == Message::MSG_REGISTER) {
            $this->handleErrorRegister($session, $msg);
        } elseif ($msg->getErrorMsgCode() == Message::MSG_UNREGISTER) {
            $this->handleErrorUnregister($session, $msg);
        } else {
            $this->logger->error("Unhandled error message: " . json_encode($msg) . "\n");
        }

    }

    /**
     * Handle error when register
     *
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Message\ErrorMessage $msg
     */
    public function handleErrorRegister(ClientSession $session, ErrorMessage $msg)
    {
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

    /**
     * Handle error when unregister
     *
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Message\ErrorMessage $msg
     */
    public function handleErrorUnregister(ClientSession $session, ErrorMessage $msg)
    {
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
     * @param \Thruway\Message\Message $msg
     * @return boolean
     */
    public function handlesMessage(Message $msg)
    {

        $handledMsgCodes = [
            Message::MSG_REGISTERED,
            Message::MSG_UNREGISTERED,
            Message::MSG_INVOCATION,
            Message::MSG_REGISTER
        ];

        $codeToCheck = $msg->getMsgCode();

        if ($msg instanceof ErrorMessage) {
            $codeToCheck = $msg->getErrorMsgCode();
        }

        if (in_array($codeToCheck, $handledMsgCodes)) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * process register
     *
     * @param \Thruway\ClientSession $session
     * @param string $procedureName
     * @param callable $callback
     * @param mixed $options
     * @return \React\Promise\Promise
     */
    public function register(ClientSession $session, $procedureName, $callback, $options = null)
    {
        $futureResult = new Deferred();

        $requestId    = Session::getUniqueId();
        $options      = isset($options) ? $options : new \stdClass();
        $registration = [
            "procedure_name" => $procedureName,
            "callback"       => $callback,
            "request_id"     => $requestId,
            'options'        => $options,
            'futureResult'   => $futureResult
        ];

        array_push($this->registrations, $registration);

        $registerMsg = new RegisterMessage($requestId, $options, $procedureName);

        $session->sendMessage($registerMsg);

        return $futureResult->promise();
    }

    /**
     * process unregister
     * 
     * @param \Thruway\ClientSession $session
     * @param string $Uri
     * @throws \Exception
     * @return \React\Promise\Promise|false
     */
    public function unregister(ClientSession $session, $Uri)
    {
        // TODO: maybe add an option to wait for pending calls to finish

        $registration = null;

        foreach ($this->registrations as $k => $r) {
            if (isset($r['procedure_name'])) {
                if ($r['procedure_name'] == $Uri) {
                    $registration = &$this->registrations[$k];
                    break;
                }
            }
        }

        if ($registration === null) {
            $this->logger->warning("registration not found: " . $Uri);
            return false;
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
        $registration['unregister_deferred']   = $futureResult;

        $unregisterMsg = new UnregisterMessage($requestId, $registration['registration_id']);

        $session->sendMessage($unregisterMsg);

        return $futureResult->promise();
    }

    /**
     * This belongs somewhere else I am thinking
     *
     * @param array $array
     * @return boolean
     */
    public static function is_list($array)
    {
        if (!is_array($array)) {
            return false;
        }

        // Keys of the array
        $keys = array_keys($array);

        // If the array keys of the keys match the keys, then the array must
        // not be associative (e.g. the keys array looked like {0:0, 1:1...}).
        return array_keys($keys) === $keys;
    }

    /**
     * Get logger
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Set logger
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

} 