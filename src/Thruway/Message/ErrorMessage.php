<?php

namespace Thruway\Message;

class ErrorMessage extends Message
{
    use ArgumentsTrait;

    const MSG_CODE = Message::MSG_ERROR;

    private $errorMsgCode;
    private $errorRequestId;
    private $details;
    private $errorURI;

    /**
     * @param $errorMsgCode
     * @param $errorRequestId
     * @param $details
     * @param $errorURI
     * @param null $arguments
     * @param null $argumentsKw
     */
    function __construct($errorMsgCode, $errorRequestId, $details, $errorURI, $arguments = null, $argumentsKw = null)
    {
        $this->errorRequestId = $errorRequestId;
        $this->errorMsgCode = $errorMsgCode;
        if (is_array($details) && count($details) == 0) $details = new \stdClass();
        $this->details = $details;
        $this->errorURI = $errorURI;
        $this->setArguments($arguments);
        $this->setArgumentsKw($argumentsKw);
    }

    /**
     * @param mixed $errorURI
     * @return $this
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
     * @param Message $msg
     * @return ErrorMessage
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
        return static::MSG_CODE;
    }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return mixed
     */
    public function getAdditionalMsgFields()
    {
        $a = array($this->getErrorMsgCode(), $this->getErrorRequestId(), $this->getDetails(), $this->getErrorURI());

        $a = array_merge($a, $this->getArgumentsForSerialization());

        return $a;
    }

    /**
     * @param mixed $details
     * @return $this
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
     * @return $this
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
     * @return $this|void
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
     * @return $this
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
}