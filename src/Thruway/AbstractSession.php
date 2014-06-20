<?php
/**
 * Created by PhpStorm.
 * User: daviddan
 * Date: 6/17/14
 * Time: 1:31 PM
 */

namespace Thruway;


use Thruway\Message\Message;

/**
 * Class AbstractSession
 * @package Thruway
 */
abstract class AbstractSession
{

    const STATE_UNKNOWN = 0;
    const STATE_PRE_HELLO = 1;
    const STATE_CHALLENGE_SENT = 2;
    const STATE_UP = 3;
    const STATE_DOWN = 4;

    /**
     * @var Realm
     */
    protected $realm;

    /**
     * @var bool
     */
    protected $authenticated;

    /**
     * @var
     */
    protected $state;

    /**
     * @var
     */
    protected $transport;

    /**
     * @var
     */
    protected $sessionId;

    /**
     * @param Message $msg
     * @return mixed
     */
    abstract public function sendMessage(Message $msg);

    /**
     * @param mixed $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }


    /**
     * @param boolean $authenticated
     */
    public function setAuthenticated($authenticated)
    {
        $this->authenticated = $authenticated;
    }

    /**
     * @return boolean
     */
    public function getAuthenticated()
    {
        return $this->authenticated;
    }

    /**
     * @return bool
     */
    public function isAuthenticated()
    {
        return $this->getAuthenticated();
    }

    /**
     * @param \Thruway\Realm $realm
     */
    public function setRealm($realm)
    {
        $this->realm = $realm;
    }

    /**
     * @return \Thruway\Realm
     */
    public function getRealm()
    {
        return $this->realm;
    }


    /**
     * @return mixed
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @return mixed
     */
    public function getTransport()
    {
        return $this->transport;
    }
} 