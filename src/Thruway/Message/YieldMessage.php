<?php

namespace Thruway\Message;


/**
 * Class YieldMessage
 * @package Thruway\Message
 */
class YieldMessage extends Message
{
    use ArgumentsTrait;

    /**
     * @var
     */
    private $requestId;

    /**
     * @var
     */
    private $options;

    /**
     * @param $requestId
     * @param $options
     * @param $arguments
     * @param $argumentsKw
     */
    function __construct($requestId, $options, $arguments = null, $argumentsKw = null)
    {
        $this->requestId = $requestId;
        $this->options = $options;
        $this->setArguments($arguments);
        $this->setArgumentsKw($argumentsKw);

    }


    /**
     * @return int
     */
    public function getMsgCode()
    {
        return static::MSG_YIELD;
    }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        $options = $this->getOptions() === null ? new \stdClass() : (object)$this->getOptions();

        $a = array($this->getRequestId(), $options);

        $a = array_merge($a, $this->getArgumentsForSerialization());

        return $a;
    }

    /**
     * @return mixed
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param mixed $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
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