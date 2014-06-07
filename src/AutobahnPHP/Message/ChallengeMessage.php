<?php

namespace AutobahnPHP\Message;


class ChallengeMessage extends Message {
    const MSG_CODE = Message::MSG_CHALLENGE;

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
        // TODO: Implement getAdditionalMsgFields() method.
    }

    /**
     * @return array
     */
    public function getValidConnectionStates()
    {
        // TODO: Implement getValidConnectionStates() method.
    }


} 