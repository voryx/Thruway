<?php

namespace Thruway\Message;

/**
 * Class ChallengeMessage
 * During authenticated session establishment, a Router sends a challenge message.
 * <code>[CHALLENGE, AuthMethod|string, Extra|dict]</code>
 *
 * @package Thruway\Message
 */
class ChallengeMessage extends Message
{

    /**
     * @var mixed
     */
    private $authMethod;

    /**
     * @var mixed
     */
    private $details;

    /**
     * @param mixed $authMethod
     * @param mixed $details
     */
    public function __construct($authMethod, $details = null)
    {
        $this->authMethod = $authMethod;
        $this->details    = $details;
    }

    /**
     * Get message code
     * 
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
        return [$this->getAuthMethod(), $this->getDetails()];
    }

    /**
     * @return mixed
     */
    public function getAuthMethod()
    {
        return $this->authMethod;
    }

    /**
     * @return mixed
     */
    public function getDetails()
    {
        return $this->details;
    }

}
