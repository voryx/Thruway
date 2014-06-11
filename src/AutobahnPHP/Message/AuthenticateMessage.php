<?php
namespace AutobahnPHP\Message;


class AuthenticateMessage extends Message {
    const MSG_CODE = Message::MSG_AUTHENTICATE;

    private $signature;

    public function __construct($signature)
    {
        $this->signature = $signature;
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
        // TODO: Implement getAdditionalMsgFields() method.
    }

    /**
     * @return array
     */
    public function getValidConnectionStates()
    {
        // TODO: Implement getValidConnectionStates() method.
    }

    /**
     * @return mixed
     */
    public function getSignature()
    {
        return $this->signature;
    }


} 