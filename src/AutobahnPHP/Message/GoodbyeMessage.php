<?php

namespace AutobahnPHP\Message;


class GoodbyeMessage extends Message {
    const MSG_CODE = Message::MSG_GOODBYE;

    private $details;
    private $reason;

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
     * @return int
     */
    public function getMsgCode() { echo "\n" . static::MSG_CODE . "\n"; return static::MSG_CODE; }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        return array($this->getDetails(), $this->getReason());
    }

} 