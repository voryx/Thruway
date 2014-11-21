<?php

namespace Thruway\Message;

use Thruway\Message\Traits\ArgumentsTrait;
use Thruway\Message\Traits\OptionsTrait;
use Thruway\Message\Traits\RequestTrait;

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

    use RequestTrait;
    use OptionsTrait;
    use ArgumentsTrait;

    /**
     * Constructor
     *
     * @param mixed $requestId
     * @param \stdClass $options
     * @param mixed $arguments
     * @param mixed $argumentsKw
     */
    public function __construct($requestId, $options, $arguments = null, $argumentsKw = null)
    {
        $this->setRequestId($requestId);
        $this->setOptions($options);
        $this->setArguments($arguments);
        $this->setArgumentsKw($argumentsKw);
    }

    /**
     * Get message code
     *
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
        $a = [$this->getRequestId(), $this->getOptions()];

        return array_merge($a, $this->getArgumentsForSerialization());
    }

}
