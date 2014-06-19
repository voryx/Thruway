<?php
namespace AutobahnPHP\Message;


/**
 * Class AuthenticateMessage
 * @package AutobahnPHP\Message
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
     * @param array $extra
     */
    public function __construct($signature, $extra = [])
    {
        $this->signature = $signature;
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