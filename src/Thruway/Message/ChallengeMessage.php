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
     * @var mixed
     */
    private $details;

    /**
     * @param $authMethod
     * @param $details
     */
    public function __construct($authMethod, $details = null)
    {
        $this->authMethod = $authMethod;
        $this->details = $details;
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
        return array($this->getAuthMethod(), $this->getDetails());
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
    public function getDetails()
    {
        return $this->details;
    }




} 