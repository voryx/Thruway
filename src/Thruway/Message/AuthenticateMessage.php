<?php
namespace Thruway\Message;


/**
 * Class AuthenticateMessage
 * @package Thruway\Message
 */
class AuthenticateMessage extends Message
{


    /**
     * @var
     */
    private $signature;

    /**
     * @var array
     */
    private $extra;


    /**
     * @param $signature
     * @param $extra
     */
    public function __construct($signature, $extra = null)
    {
        $this->signature = $signature;

        if ($extra === null) $extra = new \stdClass();

        $this->extra = $extra;
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
        return array($this->getSignature(), $this->getExtra());
    }

    /**
     * @return mixed
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