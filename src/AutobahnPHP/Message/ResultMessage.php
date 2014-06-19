<?php

namespace AutobahnPHP\Message;


/**
 * Class ResultMessage
 * @package AutobahnPHP\Message
 */
/**
 * Class ResultMessage
 * @package AutobahnPHP\Message
 */
class ResultMessage extends Message
{

    /**
     * @var
     */
    private $requestId;

    /**
     * @var
     */
    private $details;

    /**
     * @var
     */
    private $arguments;

    /**
     * @var
     */
    private $argumentsKw;


    /**
     * @param $requestId
     * @param $details
     * @param $arguments
     * @param $argumentsKw
     */
    function __construct($requestId, $details, $arguments = null, $argumentsKw = null)
    {
        $this->requestId = $requestId;
        $this->details = $details;
        $this->arguments = $arguments;
        $this->argumentsKw = $argumentsKw;

    }


    /**
     * @return int
     */
    public function getMsgCode()
    {
        return static::MSG_RESULT;
    }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        return array($this->getRequestId(), $this->getDetails(), $this->getArguments(), $this->getArgumentsKw());
    }

    /**
     * @return mixed
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param mixed $arguments
     */
    public function setArguments($arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * @return mixed
     */
    public function getArgumentsKw()
    {
        return $this->argumentsKw;
    }

    /**
     * @param mixed $argumentsKw
     */
    public function setArgumentsKw($argumentsKw)
    {
        $this->argumentsKw = $argumentsKw;
    }

    /**
     * @return mixed
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @param mixed $details
     */
    public function setDetails($details)
    {
        $this->details = $details;
    }

    /**
     * @return mixed
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * @param mixed $requestId
     */
    public function setRequestId($requestId)
    {
        $this->requestId = $requestId;
    }


}