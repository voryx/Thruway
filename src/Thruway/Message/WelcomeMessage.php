<?php

namespace Thruway\Message;

use Thruway\Message\Traits\DetailsTrait;

/**
 * Class WelcomeMessage
 * Sent by a Router to accept a Client. The WAMP session is now open.
 * [WELCOME, Session|id, Details|dict]
 *
 * @package Thruway\Message
 */
class WelcomeMessage extends Message
{

    use DetailsTrait;

    /**
     * @var int
     */
    private $sessionId;


    /**
     * Constructor
     *
     * @param int $sessionId
     * @param \stdClass $details
     */
    public function __construct($sessionId, $details)
    {
        $this->setDetails($details);
        $this->setSessionId($sessionId);
    }


    /**
     * Get message code
     *
     * @return int
     */
    public function getMsgCode()
    {
        return static::MSG_WELCOME;
    }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        return [$this->sessionId, $this->details];
    }

    /**
     * Get session ID
     *
     * @return int
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @param int $sessionId
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;
    }

} 