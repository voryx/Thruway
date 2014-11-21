<?php

namespace Thruway\Message;

use Thruway\Message\Traits\ArgumentsTrait;
use Thruway\Message\Traits\DetailsTrait;
use Thruway\Message\Traits\RequestTrait;

/**
 * Class ResultMessage
 * Result of a call as returned by Dealer to Caller.
 * <code>[RESULT, CALL.Request|id, Details|dict]</code>
 * <code>[RESULT, CALL.Request|id, Details|dict, YIELD.Arguments|list]</code>
 * <code>[RESULT, CALL.Request|id, Details|dict, YIELD.Arguments|list, YIELD.ArgumentsKw|dict]</code>
 *
 * @package Thruway\Message
 */
class ResultMessage extends Message
{
    use RequestTrait;
    use DetailsTrait;

    /**
     * using arguments trait
     * @see \Thruway\Message\ArgumentsTrait
     */
    use ArgumentsTrait;


    /**
     * Constructor
     *
     * @param int $requestId
     * @param \stdClass $details
     * @param array $arguments
     * @param array $argumentsKw
     */
    public function __construct($requestId, $details, $arguments = null, $argumentsKw = null)
    {
        $this->setRequestId($requestId);
        $this->setDetails($details);
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

        $a = [$this->getRequestId(), $this->getDetails()];

        return array_merge($a, $this->getArgumentsForSerialization());
    }


}
