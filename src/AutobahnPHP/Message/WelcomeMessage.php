<?php

namespace AutobahnPHP\Message;


class WelcomeMessage extends Message {
    const MSG_CODE = Message::MSG_WELCOME;

    private $sessionId;
    private $details;

    function __construct($sessionId, $details)
    {
        $this->details = $details;
        $this->sessionId = $sessionId;
    }


    /**
     * @return int
     */
    public function getMsgCode() { return static::MSG_CODE; }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        return array($this->sessionId, $this->details);
    }

    /**
     * @return array
     */
    public function getValidConnectionStates()
    {
        return array(Wamp2Connection::STATE_NEW);
    }

} 