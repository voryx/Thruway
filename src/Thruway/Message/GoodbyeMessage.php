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
     * @var string
     */
    private $reason;

    /**
     * @param mixed $details
     * @param string $reason
     */
    public function __construct($details, $reason)
    {
        $this->details = $details;
        $this->reason  = $reason;
    }

    /**
     * Set details
     * 
     * @param mixed $details
     */
    public function setDetails($details)
    {
        $this->details = $details;
    }

    /**
     * Get details
     * 
     * @return mixed
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * Set reason
     * 
     * @param string $reason
     */
    public function setReason($reason)
    {
        $this->reason = $reason;
    }

    /**
     * Get reason
     * 
     * @return string 
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
        return [(object)$this->getDetails(), $this->getReason()];
    }

} 