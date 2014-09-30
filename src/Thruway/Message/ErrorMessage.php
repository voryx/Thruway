<?php

namespace Thruway\Message;

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

    /**
     * using arguments trait
     * @see \Thruway\Message\ArgumentsTrait
     */
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
     * Error details
     * @var mixed
     */
    private $details;

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
     * @param mixed $details
     * @param string $errorURI
     * @param mixed $arguments
     * @param mixed $argumentsKw
     */
    function __construct($errorMsgCode, $errorRequestId, $details, $errorURI, $arguments = null, $argumentsKw = null)
    {
        $this->errorRequestId = $errorRequestId;
        $this->errorMsgCode   = $errorMsgCode;
        $this->details        = Message::shouldBeDictionary($details);
        $this->errorURI       = $errorURI;

        $this->setArguments($arguments);
        $this->setArgumentsKw($argumentsKw);
    }

    /**
     * @param mixed $errorURI
     * @return \Thruway\Message\ErrorMessage
     */
    public function setErrorURI($errorURI)
    {
        $this->errorURI = $errorURI;

        return $this;
    }

    /**
     * @return mixed
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
     * @return \Thruway\Message\ErrorMessage
     */
    static public function createErrorMessageFromMessage(Message $msg)
    {
        return new ErrorMessage($msg->getMsgCode(), $msg->getRequestId(), new \stdClass, "wamp.error.unknown");
    }

    /**
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
     * @param mixed $details
     * @return \Thruway\Message\ErrorMessage
     */
    public function setDetails($details)
    {
        $this->details = $details;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @param mixed $errorMsgCode
     * @return \Thruway\Message\ErrorMessage
     */
    public function setErrorMsgCode($errorMsgCode)
    {
        $this->errorMsgCode = $errorMsgCode;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getErrorMsgCode()
    {
        return $this->errorMsgCode;
    }

    /**
     * @param mixed $requestId
     * @return \Thruway\Message\ErrorMessage
     */
    public function setRequestId($requestId)
    {
        $this->errorRequestId = $requestId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRequestId()
    {
        return $this->errorRequestId;
    }

    /**
     * @param mixed $errorRequestId
     * @return \Thruway\Message\ErrorMessage
     */
    public function setErrorRequestId($errorRequestId)
    {
        $this->errorRequestId = $errorRequestId;

        return $this;
    }

    /**
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
    function __toString()
    {
        return $this->getErrorURI();
    }

}
