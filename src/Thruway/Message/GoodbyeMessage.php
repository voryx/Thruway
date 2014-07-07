<?php

namespace Thruway\Message;


/**
 * Class GoodbyeMessage
 * @package Thruway\Message
 */
class GoodbyeMessage extends Message
{

    /**
     * @var
     */
    private $details;
    /**
     * @var
     */
    private $reason;

    /**
     * @param $details
     * @param $reason
     */
    function __construct($details, $reason)
    {
        $this->details = $details;
        $this->reason = $reason;
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
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @param mixed $reason
     */
    public function setReason($reason)
    {
        $this->reason = $reason;
    }

    /**
     * @return mixed
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * @return int
     */
    public function getMsgCode()
    {
        return static::MSG_GOODBYE;
    }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        return array($this->getDetails(), $this->getReason());
    }

} 