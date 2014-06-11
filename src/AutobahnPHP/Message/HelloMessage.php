<?php

namespace AutobahnPHP\Message;


class HelloMessage extends Message
{
    const MSG_CODE = Message::MSG_HELLO;

    private $realm;
    private $details;
    private $roles;
    private $authMethods;

    function __construct($realm, $details, $authMethods)
    {
        $this->setDetails($details);
        $this->realm = $realm;
        $this->authMethods = $authMethods;
    }

    /**
     * @return array
     */
    public function getValidConnectionStates()
    {
        return array(Wamp2Connection::STATE_NEW);
    }


    /**
     * @return int
     */
    public function getMsgCode()
    {
        return static::MSG_CODE;
    }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return mixed
     */
    public function getAdditionalMsgFields()
    {
        return array($this->getRealm(), $this->getDetails());
    }


    /**
     * @param mixed $details
     */
    public function setDetails($details)
    {
        $this->details = $details;

        if ($details['roles']) {
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