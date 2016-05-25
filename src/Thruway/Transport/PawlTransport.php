<?php

namespace Thruway\Transport;

use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;
use React\Promise\Deferred;
use Thruway\Message\Message;

/**
 * Class PawlTransport
 *
 * @package Thruway\Transport
 */
class PawlTransport extends AbstractTransport
{

    /**
     * @var mixed
     */
    private $pingSeq;

    /**
     * @var array
     */
    private $pingRequests;

    /**
     * @var \Ratchet\Client\WebSocket
     */
    private $conn;

    /**
     * Constructor
     *
     * @param \Ratchet\Client\WebSocket $conn
     * @param \React\EventLoop\LoopInterface $loop
     */
    function __construct($conn, LoopInterface $loop)
    {
        $this->conn         = $conn;
        $this->pingSeq      = 0;
        $this->pingRequests = [];
        $this->loop         = $loop;
    }

    /**
     * Send message
     *
     * @param \Thruway\Message\Message $msg
     */
    public function sendMessage(Message $msg)
    {
        $this->conn->send($this->getSerializer()->serialize($msg));
    }

    /**
     * close transport
     */
    public function close()
    {
        $this->conn->close();
    }

    /**
     * @return array
     */
    public function getTransportDetails()
    {
        return [
            "type" => "pawl"
        ];
    }

    /**
     * ping
     *
     * @param int $timeout
     * @return \React\Promise\Promise
     */
    public function ping($timeout = 10)
    {
        return false;
        
    }
}
