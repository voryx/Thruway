<?php

namespace Thruway\Message;

/**
 * Class AuthenticateMessage
 * In response to a CHALLENGE message, an Endpoint MUST send an AUTHENTICATION message.
 * <code>[AUTHENTICATE, Signature|string, Extra|dict]</code>
 *
 * @package Thruway\Message
 */
class AuthenticateMessage extends Message
{

    /**
     * @var string
     */
    private $signature;

    /**
     * @var array
     */
    private $extra;

    /**
     * @param string $signature
     * @param array $extra
     */
    public function __construct($signature, $extra = null)
    {
        $this->signature = $signature;

        if ($extra === null) {
            $extra = new \stdClass();
        }

        $this->extra = Message::shouldBeDictionary($extra);
    }

    /**
     * @return int
     */
    public function getMsgCode()
    {
        return static::MSG_AUTHENTICATE;
    }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        return [$this->getSignature(), $this->getExtra()];
    }

    /**
     * @return string
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * @return array
     */
    public function getExtra()
    {
        return $this->extra;
    }

}
