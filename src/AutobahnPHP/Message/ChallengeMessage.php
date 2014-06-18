<?php

namespace AutobahnPHP\Message;


class ChallengeMessage extends Message {
    const MSG_CODE = Message::MSG_CHALLENGE;

    private $authMethod;

    public function __construct($authMethod)
    {
        $this->authMethod = $authMethod;
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
        return array($this->getAuthMethod());
    }

    /**
     * @return mixed
     */
    public function getAuthMethod()
    {
        return $this->authMethod;
    }


} 