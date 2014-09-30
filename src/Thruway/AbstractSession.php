<?php

namespace Thruway;


use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\Promise;
use Thruway\Message\AbortMessage;
use Thruway\Message\Message;
use Thruway\Transport\TransportInterface;

/**
 * Class AbstractSession
 * 
 * @package Thruway
 */
abstract class AbstractSession
{
    /**
     * Session state
     * @const int
     */
    const STATE_UNKNOWN = 0;
    const STATE_PRE_HELLO = 1;
    const STATE_CHALLENGE_SENT = 2;
    const STATE_UP = 3;
    const STATE_DOWN = 4;

    /**
     * @var \Thruway\Realm
     */
    protected $realm;

    /**
     * @var boolean
     */
    protected $authenticated;

    /**
     * @var int
     */
    protected $state;

    /**
     * @var \Thruway\Transport\TransportInterface
     */
    protected $transport;

    /**
     * @var int
     */
    protected $sessionId;

    /**
     * @var boolean
     */
    private $goodbyeSent = false;

    /**
     *
     * @var array
     */
    protected $pingRequests = [];

    /**
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;

    /**
     * @param \Thruway\Message\Message $msg
     * @return mixed
     */
    abstract public function sendMessage(Message $msg);

    /**
     * @param int $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return int
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
     * @return boolean
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
     * @return int
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @return \Thruway\Transport\TransportInterface
     */
    public function getTransport()
    {
        return $this->transport;
    }

    /**
     * @return boolean
     */
    public function isGoodbyeSent()
    {
        return $this->goodbyeSent;
    }

    /**
     * @param boolean $goodbyeSent
     */
    public function setGoodbyeSent($goodbyeSent)
    {
        $this->goodbyeSent = $goodbyeSent;
    }

    /**
     * @param int $timeout
     * @return \React\Promise\Promise
     */
    public function ping($timeout = 5) 
    {
        return $this->getTransport()->ping($timeout);
    }

    /**
     * process abort request
     * 
     * @param mixed $details
     * @param mixed $responseURI
     * @throws \Exception
     */
    public function abort($details = null, $responseURI = null) 
    {
        if ($this->isAuthenticated()){
            throw new \Exception("Session::abort called after we are authenticated");
        }

        $abortMsg = new AbortMessage($details, $responseURI);

        $this->sendMessage($abortMsg);

        $this->shutdown();
    }

    /**
     * Process Shutdown session
     */
    public function shutdown()
    {
        // we want to immediately remove
        // all references

        $this->onClose();

        $this->transport->close();
    }

    /**
     * @param \React\EventLoop\LoopInterface $loop
     */
    public function setLoop($loop)
    {
        $this->loop = $loop;
    }

    /**
     * @return \React\EventLoop\LoopInterface
     */
    public function getLoop()
    {
        return $this->loop;
    }


} 