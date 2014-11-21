<?php

namespace Thruway\Message;

use Thruway\Logging\Logger;
use Thruway\Message\Traits\ArgumentsTrait;
use Thruway\Message\Traits\DetailsTrait;

/**
 * Class error message
 * Error reply sent by a Peer as an error response to different kinds of requests.
 * <code>[ERROR, REQUEST.Type|int, REQUEST.Request|id, Details|dict, Error|uri]</code>
 * <code>[ERROR, REQUEST.Type|int, REQUEST.Request|id, Details|dict, Error|uri, Arguments|list]</code>
 * <code>[ERROR, REQUEST.Type|int, REQUEST.Request|id, Details|dict, Error|uri, Arguments|list, ArgumentsKw|dict]</code>
 *
 * @package Thruway\Message
 */
class ErrorMessage extends Message
{

    use DetailsTrait;
    use ArgumentsTrait;

    /**
     * Error message code
     * @var int
     */
    private $errorMsgCode;

    /**
     * Error request id
     * @var mixed
     */
    private $errorRequestId;

    /**
     * Error URI
     * @var string
     */
    private $errorURI;

    /**
     * Constructor
     *
     * @param int $errorMsgCode
     * @param mixed $errorRequestId
     * @param \stdClass $details
     * @param string $errorURI
     * @param mixed $arguments
     * @param mixed $argumentsKw
     */
    public function __construct($errorMsgCode, $errorRequestId, $details, $errorURI, $arguments = null, $argumentsKw = null)
    {

        $this->setErrorRequestId($errorRequestId);
        $this->setErrorMsgCode($errorMsgCode);
        $this->setDetails($details);
        $this->setErrorURI($errorURI);
        $this->setArguments($arguments);
        $this->setArgumentsKw($argumentsKw);
    }

    /**
     * Set error URI
     *
     * @param string $errorURI
     * @return \Thruway\Message\ErrorMessage
     */
    public function setErrorURI($errorURI)
    {
        $this->errorURI = $errorURI;

        return $this;
    }

    /**
     * Get error URI
     *
     * @return string
     */
    public function getErrorURI()
    {
        return $this->errorURI;
    }

    /**
     * This creates a specific error message depending on the message we are reporting
     * an error on.
     *
     * @param \Thruway\Message\Message $msg
     * @param string $errorUri
     * @return \Thruway\Message\ErrorMessage
     */
    public static function createErrorMessageFromMessage(Message $msg, $errorUri = null)
    {
        if ($errorUri === null) {
            $errorUri = "wamp.error.unknown";
        }

        if (method_exists($msg, "getRequestId")) {
            return new ErrorMessage($msg->getMsgCode(), $msg->getRequestId(), new \stdClass, $errorUri);
        }

        Logger::error(null, "Can't send an error message because the message didn't not have a request id ");
    }

    /**
     * Get message code
     *
     * @return int
     */
    public function getMsgCode()
    {
        return static::MSG_ERROR;
    }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return mixed
     */
    public function getAdditionalMsgFields()
    {
        $a = [$this->getErrorMsgCode(), $this->getErrorRequestId(), $this->getDetails(), $this->getErrorURI()];

        $a = array_merge($a, $this->getArgumentsForSerialization());

        return $a;
    }

    /**
     * Set error message code
     *
     * @param int $errorMsgCode
     * @return \Thruway\Message\ErrorMessage
     */
    public function setErrorMsgCode($errorMsgCode)
    {
        $this->errorMsgCode = $errorMsgCode;

        return $this;
    }

    /**
     * Get error message code
     *
     * @return mixed
     */
    public function getErrorMsgCode()
    {
        return $this->errorMsgCode;
    }

    /**
     * Set request ID
     *
     * @param mixed $requestId
     * @return \Thruway\Message\ErrorMessage
     */
    public function setRequestId($requestId)
    {
        $this->errorRequestId = $requestId;

        return $this;
    }

    /**
     * Get request ID
     *
     * @return mixed
     */
    public function getRequestId()
    {
        return $this->errorRequestId;
    }

    /**
     * Set error request ID
     *
     * @param mixed $errorRequestId
     * @return \Thruway\Message\ErrorMessage
     */
    public function setErrorRequestId($errorRequestId)
    {
        $this->errorRequestId = $errorRequestId;

        return $this;
    }

    /**
     * Get error request ID
     *
     * @return mixed
     */
    public function getErrorRequestId()
    {
        return $this->errorRequestId;
    }

    /**
     * Convert error message to string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getErrorURI();
    }

}
