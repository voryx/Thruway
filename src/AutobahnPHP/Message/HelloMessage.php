<?php

namespace AutobahnPHP\Message;


/**
 * Class HelloMessage
 * @package AutobahnPHP\Message
 */
class HelloMessage extends Message
{
    /**
     * @var
     */
    private $realm;
    /**
     * @var
     */
    private $details;
    /**
     * @var
     */
    private $roles;
    /**
     * @var
     */
    private $authMethods;

    /**
     * @param $realm
     * @param $details
     * @param $authMethods
     */
    function __construct($realm, $details, $authMethods)
    {
        $this->setDetails($details);
        $this->realm = $realm;
        $this->authMethods = $authMethods;
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
        return array($this->getRealm(), $this->getDetails());
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