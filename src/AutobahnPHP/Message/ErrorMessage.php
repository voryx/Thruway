<?php

namespace AutobahnPHP\Message;

class ErrorMessage extends Message
{
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
     */
    function __construct($errorMsgCode, $errorRequestId, $details, $errorURI)
    {
        $this->errorRequestId = $errorRequestId;
        $this->errorMsgCode = $errorMsgCode;
        $this->details = $details;
        $this->errorURI = $errorURI;
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
        return array($this->getErrorMsgCode(), $this->getErrorRequestId(), $this->getDetails(), $this->getErrorURI());
    }

    /**
     * @return array
     */
    public function getValidConnectionStates()
    {
        return array(Wamp2Connection::STATE_ALL);
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