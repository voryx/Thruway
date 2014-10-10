<?php

namespace Thruway\Message;

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

    /**
     * using arguments trait
     * @see \Thruway\Message\ArgumentsTrait
     */
    use ArgumentsTrait;

    /**
     * @var int
     */
    private $requestId;

    /**
     * @var array
     */
    private $details;

    /**
     * Constructor
     * 
     * @param int $requestId
     * @param array $details
     * @param array $arguments
     * @param array $argumentsKw
     */
    public function __construct($requestId, $details, $arguments = null, $argumentsKw = null)
    {
        $this->requestId = $requestId;
        $this->details   = $details;
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
        $details = $this->getDetails();
        if ($details === null) {
            $details = new \stdClass();
        }
        $details = (object)$details;

        $a = [$this->getRequestId(), $details];

        $a = array_merge($a, $this->getArgumentsForSerialization());

        return $a;
    }

    /**
     * Get result details
     * 
     * @return array
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * Set result details
     * 
     * @param array $details
     */
    public function setDetails($details)
    {
        $this->details = $details;
    }

    /**
     * Get request ID
     * 
     * @return int
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * Set request ID
     * 
     * @param int $requestId
     */
    public function setRequestId($requestId)
    {
        $this->requestId = $requestId;
    }

}
