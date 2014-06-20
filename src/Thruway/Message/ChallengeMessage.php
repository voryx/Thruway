<?php

namespace Thruway\Message;


/**
 * Class ChallengeMessage
 * @package Thruway\Message
 */
class ChallengeMessage extends Message
{

    /**
     * @var
     */
    private $authMethod;

    /**
     * @var null
     */
    private $extra;

    /**
     * @param $authMethod
     * @param array $extra
     */
    public function __construct($authMethod, $extra = [])
    {
        $this->authMethod = $authMethod;
        $this->extra = $extra;
    }

    /**
     * @return int
     */
    public function getMsgCode()
    {
        return static::MSG_CHALLENGE;
    }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        return array($this->getAuthMethod(), $this->getExtra());
    }

    /**
     * @return mixed
     */
    public function getAuthMethod()
    {
        return $this->authMethod;
    }

    /**
     * @return null
     */
    public function getExtra()
    {
        return $this->extra;
    }




} 