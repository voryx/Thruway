<?php

namespace Thruway\Message;

/**
 * Class YieldMessage
 * Actual yield from an endpoint send by a Callee to Dealer.
 * <code>[YIELD, INVOCATION.Request|id, Options|dict]</code>
 * <code>[YIELD, INVOCATION.Request|id, Options|dict, Arguments|list]</code>
 * <code>[YIELD, INVOCATION.Request|id, Options|dict, Arguments|list, ArgumentsKw|dict]</code>
 *
 * @package Thruway\Message
 */
class YieldMessage extends Message
{

    /**
     * using arguments trait
     * @see \Thruway\Message\ArgumentsTrait
     */
    use ArgumentsTrait;

    /**
     * @var mixed
     */
    private $requestId;

    /**
     * @var mixed
     */
    private $options;

    /**
     * Constructor
     *
     * @param mixed $requestId
     * @param mixed $options
     * @param mixed $arguments
     * @param mixed $argumentsKw
     */
    function __construct($requestId, $options, $arguments = null, $argumentsKw = null)
    {
        $this->requestId = $requestId;
        $this->options   = $options;
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

        $a = [$this->getRequestId(), $options];

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
