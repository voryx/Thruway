<?php

namespace Thruway\Message;

use Thruway\Message\Traits\DetailsTrait;

/**
 * Class HelloMessage
 * Sent by a Client to initiate opening of a WAMP session to a Router attaching to a Realm.
 * <code>[HELLO, Realm|uri, Details|dict]</code>
 *
 * @package Thruway\Message
 */
class HelloMessage extends Message
{

    use DetailsTrait;

    /**
     * @var string
     */
    private $realm;

    /**
     * @var array
     */
    private $roles;

    /**
     * @var array
     */
    private $authMethods;

    /**
     * Constructor
     *
     * @param string $realm
     * @param \stdClass $details
     */
    public function __construct($realm, $details)
    {
        if (!is_scalar($realm)) {
            throw new \InvalidArgumentException("Non-scalar realm name.");
        }

        $this->setDetails($details);
        $this->setRealm($realm);
        $authMethods = isset($details->authmethods) ? $details->authmethods : [];
        $this->setAuthMethods($authMethods);
    }


    /**
     * Get message code
     *
     * @return int
     */
    public function getMsgCode()
    {
        return static::MSG_HELLO;
    }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        return [$this->getRealm(), $this->getDetails()];
    }

    /**
     * Set realm
     *
     * @param string $realm
     */
    public function setRealm($realm)
    {
        $this->realm = $realm;
    }

    /**
     * Get realm
     *
     * @return string
     */
    public function getRealm()
    {
        return $this->realm;
    }

    /**
     * Get list authenticate methods
     *
     * @return array
     */
    public function getAuthMethods()
    {
        return $this->authMethods;
    }

    /**
     * @param array $authMethods
     */
    public function setAuthMethods($authMethods)
    {
        $this->authMethods = $authMethods;
    }

}
