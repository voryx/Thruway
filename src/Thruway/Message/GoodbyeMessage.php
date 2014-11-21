<?php

namespace Thruway\Message;

use Thruway\Message\Traits\DetailsTrait;


/**
 * Class GoodbyeMessage
 * Sent by a Peer to close a previously opened WAMP session. Must be echo'ed by the receiving Peer.
 * <code>[GOODBYE, Details|dict, Reason|uri]</code>
 *
 * @package Thruway\Message
 */
class GoodbyeMessage extends Message
{

    use DetailsTrait;

    /**
     * @var string
     */
    private $reason;

    /**
     * @param \stdClass $details
     * @param string $reason
     */
    public function __construct($details, $reason)
    {
        $this->setDetails($details);
        $this->setReason($reason);
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