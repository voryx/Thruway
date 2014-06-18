<?php
/**
 * Created by PhpStorm.
 * User: daviddan
 * Date: 6/17/14
 * Time: 12:43 AM
 */

namespace AutobahnPHP;


use AutobahnPHP\Message\AbortMessage;
use AutobahnPHP\Message\Message;
use AutobahnPHP\Peer\AbstractPeer;
use AutobahnPHP\Peer\Client;
use Ratchet\Client\WebSocket;

/**
 * Class ClientSession
 * @package AutobahnPHP
 */
class ClientSession extends AbstractSession
{


    /**
     * @var \Ratchet\Client\WebSocket
     */
    private $conn;

    /**
     * @var Client
     */
    private $peer;

    /**
     * @param WebSocket $conn
     * @param Peer\AbstractPeer $peer
     * @internal param null $onChallenge callback
     */

    function __construct(WebSocket $conn, AbstractPeer $peer)
    {
        $this->conn = $conn;
        $this->peer = $peer;
    }

    /**
     * @param $topicName
     * @param $callback
     */
    public function subscribe($topicName, $callback)
    {
        $this->peer->getSubscriber()->subscribe($topicName, $callback);
    }

    /**
     * @param $topicName
     * @param $arguments
     * @return \React\Promise\Promise
     */
    public function publish($topicName, $arguments, $argumentsKw = null, $options = null)
    {
        return $this->peer->getPublisher()->publish($topicName, $arguments, $argumentsKw, $options);
    }

    /**
     * @param $procedureName
     * @param $callback
     */
    public function register($procedureName, $callback)
    {
        $this->peer->getCallee()->register($procedureName, $callback);
    }

    /**
     * @param $procedureName
     * @param $arguments
     * @return \React\Promise\Promise
     */
    public function call($procedureName, $arguments)
    {
        return $this->peer->getCaller()->call($procedureName, $arguments);
    }

    /**
     * @param Message $msg
     * @return mixed|void
     */
    public function sendMessage(Message $msg)
    {
        $this->conn->send($msg->getSerializedMessage());
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
        $this->conn->close();
    }

}