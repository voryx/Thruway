<?php

namespace Thruway\Message;


class CancelMessage extends Message {

    /**
     * @return int
     */
    public function getMsgCode()
    {
        // TODO: Implement getMsgCode() method.
    }

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

}