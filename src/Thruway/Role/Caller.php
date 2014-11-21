<?php

namespace Thruway\Role;


use Thruway\AbstractSession;
use Thruway\CallResult;
use Thruway\ClientSession;
use Thruway\Common\Utils;
use Thruway\Logging\Logger;
use Thruway\Message\CallMessage;
use Thruway\Message\ErrorMessage;
use Thruway\Message\Message;
use Thruway\Message\ResultMessage;
use Thruway\Session;
use React\Promise\Deferred;

/**
 * Class Caller
 *
 * @package Thruway\Role
 */
class Caller extends AbstractRole
{

    /**
     * @var array
     */
    private $callRequests;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->callRequests = [];
    }

    /**
     * Return supported features
     *
     * @return \stdClass
     */
    public function getFeatures() {
        $features = new \stdClass();

        $features->caller_identification = true;
        $features->progressive_call_results = true;

        return $features;
    }

    /**
     * process message
     *
     * @param \Thruway\AbstractSession $session
     * @param \Thruway\Message\Message $msg
     * @return void
     */
    public function onMessage(AbstractSession $session, Message $msg)
    {

        if ($msg instanceof ResultMessage):
            $this->processResult($msg);
        elseif ($msg instanceof ErrorMessage):
            $this->processError($msg);
        else:
            $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg));
        endif;
    }

    /**
     * Process ResultMessage
     *
     * @param \Thruway\Message\ResultMessage $msg
     */
    protected function processResult(ResultMessage $msg)
    {
        if (isset($this->callRequests[$msg->getRequestId()])) {
            /* @var $futureResult Deferred */
            $futureResult = $this->callRequests[$msg->getRequestId()]['future_result'];

            $callResult = new CallResult($msg);

            $details = $msg->getDetails();
            if (is_object($details) && isset($details->progress) && $details->progress) {
                // TODO: what if we didn't want progress?
                $futureResult->progress($callResult);
            } else {
                $futureResult->resolve($callResult);
                unset($this->callRequests[$msg->getRequestId()]);
            }
        }
    }

    /**
     * Process ErrorMessage
     *
     * @param \Thruway\Message\ErrorMessage $msg
     */
    protected function processError(ErrorMessage $msg)
    {
        switch ($msg->getErrorMsgCode()) {
            case Message::MSG_CALL:
                if (isset($this->callRequests[$msg->getRequestId()])) {
                    /* @var $futureResult Deferred */
                    $futureResult = $this->callRequests[$msg->getRequestId()]['future_result'];
                    $futureResult->reject($msg);
                    unset($this->callRequests[$msg->getRequestId()]);
                }
                break;
        }

    }

    /**
     * handle message
     * Returns true if this role handles this message.
     *
     * @param \Thruway\Message\Message $msg
     * @return boolean
     */
    public function handlesMessage(Message $msg)
    {

        $handledMsgCodes = [
            Message::MSG_RESULT,
        ];

        if (in_array($msg->getMsgCode(), $handledMsgCodes)) {
            return true;
        } elseif ($msg instanceof ErrorMessage && $msg->getErrorMsgCode() == Message::MSG_CALL) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * process call
     *
     * @param \Thruway\ClientSession $session
     * @param string $procedureName
     * @param mixed $arguments
     * @param mixed $argumentsKw
     * @param mixed $options
     * @return \React\Promise\Promise
     */
    public function call(ClientSession $session, $procedureName, $arguments = null, $argumentsKw = null, $options = null)
    {
        //This promise gets resolved in Caller::processResult
        $futureResult = new Deferred();

        $requestId = Utils::getUniqueId();

        $this->callRequests[$requestId] = [
            "procedure_name" => $procedureName,
            "future_result"  => $futureResult
        ];

        if (is_array($options)) {
            $options = (object)$options;
        }

        if (!is_object($options)) {
            if ($options !== null) {
                Logger::warning($this, "Options don't appear to be the correct type.");
            }
            $options = new \stdClass();
        }

        $callMsg = new CallMessage($requestId, $options, $procedureName, $arguments, $argumentsKw);

        $session->sendMessage($callMsg);

        return $futureResult->promise();
    }

} 