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
     * @var mixed
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
     * @param $realm
     * @param $details
     * @param $authMethods
     */
    function __construct($realm, $details)
    {
        $this->setDetails($details);
        $this->realm       = $realm;
        $this->authMethods = isset($details['authmethods']) ? $details['authmethods'] : [];
    }

    /**
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
     * @return mixed
     */
    public function getAdditionalMsgFields()
    {
        return [$this->getRealm(), $this->getDetails()];
    }

    /**
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
     * @return mixed
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @param mixed $realm
     */
    public function setRealm($realm)
    {
        $this->realm = $realm;
    }

    /**
     * @return mixed
     */
    public function getRealm()
    {
        return $this->realm;
    }

    /**
     * @return mixed
     */
    public function getAuthMethods()
    {
        return $this->authMethods;
    }

}
