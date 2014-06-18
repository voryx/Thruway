<?php
/**
 * Created by PhpStorm.
 * User: daviddan
 * Date: 6/12/14
 * Time: 12:36 PM
 */

namespace AutobahnPHP\Message;


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