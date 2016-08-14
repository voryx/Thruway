<?php

namespace Thruway;

use Thruway\Message\ResultMessage;

/**
 * Class CallResult
 *
 * @package Thruway
 */
class CallResult extends \ArrayObject
{
    /**
     * @var \Thruway\Message\ResultMessage
     */
    private $resultMessage;

    /**
     * Constructor
     *
     * @param \Thruway\Message\ResultMessage $msg
     */
    public function __construct(ResultMessage $msg)
    {
        $this->resultMessage = $msg;

        parent::__construct($msg->getArguments());
    }

    /**
     * Convert to string
     *
     * @return string
     */
    public function __toString()
    {
        return isset($this[0]) ? (string)$this[0] : "";
    }

    /**
     * Set Result Message
     *
     * @param \Thruway\Message\ResultMessage $msg
     */
    public function setResultMessage(ResultMessage $msg)
    {
        $this->resultMessage = $msg;
    }

    /**
     * @return \Thruway\Message\ResultMessage
     */
    public function getResultMessage()
    {
        return $this->resultMessage;
    }

    /**
     * Get arguments
     *
     * @return mixed
     */
    public function getArguments()
    {
        return $this->getResultMessage()->getArguments();
    }

    /**
     * Get arguments kw
     *
     * @return mixed
     */
    public function getArgumentsKw()
    {
        return $this->getResultMessage()->getArgumentsKw();
    }

    /**
     * Get result detail
     *
     * @return mixed
     */
    public function getDetails()
    {
        return $this->getResultMessage()->getDetails();
    }
}
