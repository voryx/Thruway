<?php

namespace Thruway\Message;

/**
 * Class WelcomeMessage
 * Sent by a Router to accept a Client. The WAMP session is now open.
 * [WELCOME, Session|id, Details|dict]
 *
 * @package Thruway\Message
 */
class WelcomeMessage extends Message
{

    /**
     * @var int
     */
    private $sessionId;

    /**
     * @var array
     */
    private $details;

    /**
     * Constructor
     *
     * @param int $sessionId
     * @param array $details
     */
    public function __construct($sessionId, $details)
    {
        $this->details   = $details;
        $this->sessionId = $sessionId;
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
     * Get details
     * 
     * @return mixed
     */
    public function getDetails()
    {
        return $this->details;
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

} 