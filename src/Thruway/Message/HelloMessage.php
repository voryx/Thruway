<?php

namespace Thruway\Message;

/**
 * Class HelloMessage
 * Sent by a Client to initiate opening of a WAMP session to a Router attaching to a Realm.
 * <code>[HELLO, Realm|uri, Details|dict]</code>
 *
 * @package Thruway\Message
 */
class HelloMessage extends Message
{

    /**
     * @var string
     */
    private $realm;

    /**
     * @var mixed
     */
    private $details;

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
     * @param mixed $details
     */
    public function __construct($realm, $details)
    {
        $this->setDetails($details);
        $this->realm       = $realm;
        $this->authMethods = isset($details['authmethods']) ? $details['authmethods'] : [];
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
     * Set details
     * 
     * @param mixed $details
     */
    public function setDetails($details)
    {
        $this->details = $details;

        if (isset($details['roles'])) {
            $this->roles = $details['roles'];
        }
    }

    /**
     * Get details
     * 
     * @return mixed
     */
    public function getDetails()
    {
        return $this->details;
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

}
