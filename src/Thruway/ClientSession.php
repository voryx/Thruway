<?php
/**
 * Created by PhpStorm.
 * User: daviddan
 * Date: 6/17/14
 * Time: 12:43 AM
 */

namespace Thruway;


use Thruway\Message\AbortMessage;
use Thruway\Message\Message;
use Thruway\Peer\AbstractPeer;
use Thruway\Peer\Client;
use Thruway\Transport\TransportInterface;
use Ratchet\Client\WebSocket;

/**
 * Class ClientSession
 * @package Thruway
 */
class ClientSession extends AbstractSession
{



    /**
     * @var Client
     */
    private $peer;

    function __construct(TransportInterface $transport, AbstractPeer $peer)
    {
        $this->transport = $transport;
        $this->peer = $peer;
    }

    /**
     * @param $topicName
     * @param $callback
     */
    public function subscribe($topicName, $callback)
    {
        $this->peer->getSubscriber()->subscribe($this, $topicName, $callback);
    }

    public function publish($topicName, $arguments, $argumentsKw = null, $options = null)
    {
        return $this->peer->getPublisher()->publish($this, $topicName, $arguments, $argumentsKw, $options);
    }

    /**
     * @param $procedureName
     * @param $callback
     * @param null $options
     * @return \React\Promise\Promise
     */
    public function register($procedureName, $callback, $options = null)
    {
        return $this->peer->getCallee()->register($this, $procedureName, $callback, $options);
    }

    /**
     * @param $procedureName
     * @return \React\Promise\Promise
     */
    public function unregister($procedureName)
    {
        return $this->peer->getCallee()->unregister($this, $procedureName);
    }

    /**
     * @param $procedureName
     * @param $arguments
     * @return \React\Promise\Promise
     */
    public function call($procedureName, $arguments = null, $argumentskw = null, $options = null)
    {
        return $this->peer->getCaller()->call($this, $procedureName, $arguments, $argumentskw, $options);
    }

    /**
     * @param Message $msg
     * @return mixed|void
     */
    public function sendMessage(Message $msg)
    {
        $this->transport->sendMessage($msg);
    }

    /**
     * @param mixed $sessionId
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;
    }

    /**
     * TODO: Need to send goodbye message
     */
    public function close()
    {
        $this->transport->close();
    }

    public function onClose() {

    }

}
