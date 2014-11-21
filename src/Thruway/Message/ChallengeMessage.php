<?php

namespace Thruway\Message;

use Thruway\Message\Traits\DetailsTrait;

/**
 * Class ChallengeMessage
 * During authenticated session establishment, a Router sends a challenge message.
 * <code>[CHALLENGE, AuthMethod|string, Extra|dict]</code>
 *
 * @package Thruway\Message
 */
class ChallengeMessage extends Message
{
    use DetailsTrait;

    /**
     * @var mixed
     */
    private $authMethod;

    /**
     * @param mixed $authMethod
     * @param \stdClass $details
     */
    public function __construct($authMethod, $details = null)
    {
        $this->setAuthMethod($authMethod);
        $this->setDetails($details);
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
        return [$this->getAuthMethod(), (object)$this->getDetails()];
    }

    /**
     * @return mixed
     */
    public function getAuthMethod()
    {
        return $this->authMethod;
    }


    /**
     * @param mixed $authMethod
     */
    public function setAuthMethod($authMethod)
    {
        $this->authMethod = $authMethod;
    }

}
