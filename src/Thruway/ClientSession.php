<?php

namespace Thruway;

use React\Promise\Promise;
use Thruway\Message\Message;
use Thruway\Peer\ClientInterface;
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
     * @param ClientInterface $peer
     */
    public function __construct(TransportInterface $transport, ClientInterface $peer)
    {
        $this->transport = $transport;
        $this->peer      = $peer;
    }

    /**
     * Subscribe
     *
     * @param string $topicName
     * @param callable $callback
     * @param $options array
     * @return Promise
     */
    public function subscribe($topicName, callable $callback, $options = null)
    {
        return $this->peer->getSubscriber()->subscribe($this, $topicName, $callback, $options);
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
    public function publish($topicName, $arguments = null, $argumentsKw = null, $options = null)
    {
        return $this->peer->getPublisher()->publish($this, $topicName, $arguments, $argumentsKw, $options);
    }

    /**
     * Register
     *
     * @param string $procedureName
     * @param callable $callback
     * @param array|mixed $options
     * @return \React\Promise\Promise
     */
    public function register($procedureName, callable $callback, $options = null)
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
        $this->state = static::STATE_DOWN;
    }
}
