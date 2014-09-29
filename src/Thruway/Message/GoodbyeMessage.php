<?php

namespace Thruway\Message;


/**
 * Class GoodbyeMessage
 * Sent by a Peer to close a previously opened WAMP session. Must be echo'ed by the receiving Peer.
 * <code>[GOODBYE, Details|dict, Reason|uri]</code>
 * 
 * @package Thruway\Message
 */
class GoodbyeMessage extends Message
{

    /**
     * @var mixed
     */
    private $details;
    /**
     * @var mixed
     */
    private $reason;

    /**
     * @param mixed $details
     * @param mixed $reason
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
     * Get message code
     * 
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
        return [$this->getDetails(), $this->getReason()];
    }

} 