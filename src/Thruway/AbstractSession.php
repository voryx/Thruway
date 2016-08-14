<?php

namespace Thruway;

use Thruway\Message\AbortMessage;
use Thruway\Message\Message;

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
     * Send message
     *
     * @param \Thruway\Message\Message $msg
     * @return mixed
     */
    abstract public function sendMessage(Message $msg);

    /**
     * Set client state
     *
     * @param int $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * Get client state
     *
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set athentication state (authenticated or not)
     *
     * @param boolean $authenticated
     */
    public function setAuthenticated($authenticated)
    {
        $this->authenticated = $authenticated;
    }

    /**
     * Get authentication state (authenticated or not)
     *
     * @return boolean
     */
    public function getAuthenticated()
    {
        return $this->authenticated;
    }

    /**
     * check is authenticated
     *
     * @return boolean
     */
    public function isAuthenticated()
    {
        return $this->getAuthenticated();
    }

    /**
     * Set realm
     *
     * @param \Thruway\Realm $realm
     */
    public function setRealm($realm)
    {
        $this->realm = $realm;
    }

    /**
     * Get realm
     *
     * @return \Thruway\Realm
     */
    public function getRealm()
    {
        return $this->realm;
    }

    /**
     * Get session ID
     *
     * @return int
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Get transport
     *
     * @return \Thruway\Transport\TransportInterface
     */
    public function getTransport()
    {
        return $this->transport;
    }

    /**
     * Check sent Goodbye message
     *
     * @return boolean
     */
    public function isGoodbyeSent()
    {
        return $this->goodbyeSent;
    }

    /**
     * Set state sent goodbye message ?
     *
     * @param boolean $goodbyeSent
     */
    public function setGoodbyeSent($goodbyeSent)
    {
        $this->goodbyeSent = $goodbyeSent;
    }

    /**
     * Ping
     *
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
        if ($this->isAuthenticated()) {
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
     * Set loop
     *
     * @param \React\EventLoop\LoopInterface $loop
     */
    public function setLoop($loop)
    {
        $this->loop = $loop;
    }

    /**
     * Get loop
     *
     * @return \React\EventLoop\LoopInterface
     */
    public function getLoop()
    {
        return $this->loop;
    }
}
