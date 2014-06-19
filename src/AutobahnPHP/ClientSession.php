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
use AutobahnPHP\Transport\TransportInterface;
use Ratchet\Client\WebSocket;

/**
 * Class ClientSession
 * @package AutobahnPHP
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
        $this->peer->getSubscriber()->subscribe($topicName, $callback);
    }

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