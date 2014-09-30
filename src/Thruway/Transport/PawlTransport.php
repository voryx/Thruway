<?php

namespace Thruway\Transport;


use Ratchet\WebSocket\Version\RFC6455\Frame;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;
use React\Promise\Deferred;
use Thruway\Exception\PingNotSupportedException;
use Thruway\Message\Message;
use Ratchet\Client\WebSocket;
use Thruway\Serializer\SerializerInterface;

/**
 * Class PawlTransport
 *
 * @package Thruway\Transport
 */
class PawlTransport implements TransportInterface
{

    /**
     * @var
     */
    private $pingSeq;

    /**
     * @var \Thruway\PingRequest
     */
    private $pingRequests;

    /**
     * @var \Thruway\Serializer\SerializerInterface
     */
    private $serializer;

    /**
     * @var \Ratchet\Client\WebSocket
     */
    private $conn;

    /**
     * @var \React\EventLoop\LoopInterface
     */
    private $loop;

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
        $payload = $this->pingSeq;

        $this->conn->send(new Frame($payload, true, Frame::OP_PING));

        $seq = $this->pingSeq;

        $this->pingSeq++;

        if ($timeout > 0) {
            $timer = $this->loop->addTimer($timeout, function () use ($seq) {
                if (isset($this->pingRequests[$seq])) {
                    $this->pingRequests[$seq]['deferred']->reject('timeout');
                    unset($this->pingRequests[$seq]);
                }

            });

            $deferred = new Deferred();

            $this->pingRequests[$seq] = [
                'seq'      => $seq,
                'deferred' => $deferred,
                'timer'    => $timer
            ];

            return $deferred->promise();
        }


    }

    /**
     * Handle on pong
     *
     * @param \Ratchet\WebSocket\Version\RFC6455\Frame $frame
     */
    public function onPong(Frame $frame)
    {
        $seq = $frame->getPayload();

        if (isset($this->pingRequests[$seq]) && isset($this->pingRequests[$seq]['deferred'])) {
            $this->pingRequests[$seq]['deferred']->resolve();
            /** @var TimerInterface $timer */
            $timer = $this->pingRequests[$seq]['timer'];
            $timer->cancel();

            unset($this->pingRequests[$seq]);
        }

        // all sequence numbers before this one are probably no good anymore
        // and actually are probably errors
    }

    /**
     * @param \Thruway\Serializer\SerializerInterface $serializer
     * @return $this
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @return \Thruway\Serializer\SerializerInterface
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

} 