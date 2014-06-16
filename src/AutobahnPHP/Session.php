<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/8/14
 * Time: 11:15 PM
 */

namespace AutobahnPHP;


use AutobahnPHP\Message\Message;
use Ratchet\ConnectionInterface;

class Session {
    const STATE_UNKNOWN = 0;
    const STATE_PRE_HELLO = 1;
    const STATE_CHALLENGE_SENT = 2;
    const STATE_UP = 3;
    const STATE_DOWN = 4;


    /**
     * @var Realm
     */
    private $realm;

    /**
     * @var bool
     */
    private $authenticated;

    private $state;

    /**
     * @var \Ratchet\ConnectionInterface
     */
    private $transport;

    private $sessionId;

    private $subscriptions;

    private $authenticationProvider;

    function __construct(ConnectionInterface $transport)
    {
        $this->transport = $transport;
        $this->state = static::STATE_PRE_HELLO;
        $this->sessionId = static::getUniqueId();
        $this->realm = null;

        $this->subscriptions = new \SplObjectStorage();
    }

    function __destruct() {
        // TODO get rid of subscriptions
        // this part needs to get moved somewhere because it will not work like this
        // topic will have a reference to session and will never get gc'd
        // and I am not daring enough to delete the object explicitly
        $this->subscriptions->rewind();
        while ($this->subscriptions->valid()) {
            /* @var $topic Topic */
            $topic = $this->subscriptions->current();
            $topic->unsubscribe($this);
            $this->subscriptions->next();
            $this->subscriptions->detach($topic);
        }
    }

    public function addSubscription(Topic $topic) {
        $this->subscriptions->attach($topic);
    }

    public function removeSubscription(Topic $topic) {
        $this->subscriptions->detach($topic);
    }

    public function sendMessage(Message $msg) {
        $this->transport->send($msg->getSerializedMessage());
    }


    public function shutdown() {

        $this->transport->close();
    }

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

    public function isAuthenticated() {
        return $this->getAuthenticated();
    }

    /**
     * @param \AutobahnPHP\Realm $realm
     */
    public function setRealm($realm)
    {
        $this->realm = $realm;
    }

    /**
     * @return \AutobahnPHP\Realm
     */
    public function getRealm()
    {
        return $this->realm;
    }

    static public function getUniqueId()
    {
        // TODO: make this better
        $result = sscanf(uniqid(), "%x");
        return $result[0];
    }

    /**
     * @return mixed
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @return ConnectionInterface
     */
    public function getTransport()
    {
        return $this->transport;
    }

    /**
     * @return mixed
     */
    public function getAuthenticationProvider()
    {
        return $this->authenticationProvider;
    }

    /**
     * @param mixed $authenticationProvider
     */
    public function setAuthenticationProvider($authenticationProvider)
    {
        $this->authenticationProvider = $authenticationProvider;
    }


    public function onClose()
    {
        $this->realm->leave($this);
    }


} 