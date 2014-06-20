<?php

namespace Thruway\Message;


/**
 * Class YieldMessage
 * @package Thruway\Message
 */
class YieldMessage extends Message
{

    /**
     * @var
     */
    private $requestId;

    /**
     * @var
     */
    private $options;

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
     * @param $options
     * @param $arguments
     * @param $argumentsKw
     */
    function __construct($requestId, $options, $arguments = null, $argumentsKw = null)
    {
        $this->requestId = $requestId;
        $this->options = $options;
        $this->arguments = $arguments;
        $this->argumentsKw = $argumentsKw;

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
        $a = array($this->getRequestId(), $this->getOptions());

        if ($this->getArguments() != null) {
            $a = array_merge($a, array($this->getArguments()));
            if ($this->getArgumentsKw()) {
                $a = array_merge($a, array($this->getArgumentsKw()));
            }
        }

        return $a;
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