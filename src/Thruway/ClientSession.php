<?php

namespace Thruway;


use React\Promise\Promise;
use Thruway\Message\Message;
use Thruway\Peer\AbstractPeer;
use Thruway\Transport\TransportInterface;

/**
 * Class ClientSession
 *
 * @package Thruway
 */
class ClientSession extends AbstractSession
{

    /**
     * @var \Thruway\Peer\Client
     */
    private $peer;

    /**
     * Constructor
     *
     * @param \Thruway\Transport\TransportInterface $transport
     * @param \Thruway\Peer\AbstractPeer $peer
     */
    function __construct(TransportInterface $transport, AbstractPeer $peer)
    {
        $this->transport = $transport;
        $this->peer      = $peer;
    }

    /**
     * Subscribe
     *
     * @param string $topicName
     * @param \Closure $callback
     *
     * @return Promise
     */
    public function subscribe($topicName, $callback)
    {
        return $this->peer->getSubscriber()->subscribe($this, $topicName, $callback);
    }

    /**
     * Publish
     *
     * @param string $topicName
     * @param array|mixed $arguments
     * @param array|mixed $argumentsKw
     * @param array|mixed $options
     * @return \React\Promise\Promise
     */
    public function publish($topicName, $arguments, $argumentsKw = null, $options = null)
    {
        return $this->peer->getPublisher()->publish($this, $topicName, $arguments, $argumentsKw, $options);
    }

    /**
     * Register
     *
     * @param string $procedureName
     * @param \Closure $callback
     * @param array|mixed $options
     * @return \React\Promise\Promise
     */
    public function register($procedureName, $callback, $options = null)
    {
        return $this->peer->getCallee()->register($this, $procedureName, $callback, $options);
    }

    /**
     * Unregister
     *
     * @param string $procedureName
     * @return \React\Promise\Promise|FALSE
     */
    public function unregister($procedureName)
    {
        return $this->peer->getCallee()->unregister($this, $procedureName);
    }

    /**
     * Call
     *
     * @param string $procedureName
     * @param array|mixed $arguments
     * @param array|mixed $argumentsKw
     * @param array|mixed $options
     * @return \React\Promise\Promise
     */
    public function call($procedureName, $arguments = null, $argumentsKw = null, $options = null)
    {
        return $this->peer->getCaller()->call($this, $procedureName, $arguments, $argumentsKw, $options);
    }

    /**
     * @param \Thruway\Message\Message $msg
     * @return mixed|void
     */
    public function sendMessage(Message $msg)
    {
        $this->transport->sendMessage($msg);
    }

    /**
     * @param int $sessionId
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;
    }

    /**
     * Close client session
     * TODO: Need to send goodbye message
     */
    public function close()
    {
        $this->transport->close();
    }

    /**
     * Handle on close client session
     */
    public function onClose()
    {

    }

}
