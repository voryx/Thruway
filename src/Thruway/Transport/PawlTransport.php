<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/19/14
 * Time: 12:03 AM
 */

namespace Thruway\Transport;


use Ratchet\WebSocket\Version\RFC6455\Frame;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;
use React\Promise\Deferred;
use Thruway\Exception\PingNotSupportedException;
use Thruway\Message\Message;
use Ratchet\Client\WebSocket;
use Thruway\Serializer\SerializerInterface;

class PawlTransport implements TransportInterface {

    private $pingSeq;

    private $pingRequests;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var WebSocket
     */
    private $conn;

    /**
     * @var LoopInterface
     */
    private $loop;

    function __construct($conn, LoopInterface $loop)
    {
        $this->conn = $conn;
        $this->pingSeq = 0;

        $this->pingRequests = array();

        $this->loop = $loop;
    }


    public function sendMessage(Message $msg)
    {
        $this->conn->send($this->getSerializer()->serialize($msg));
    }

    public function close()
    {
        $this->conn->close();
    }

    public function getTransportDetails()
    {
        return array(
            "type" => "pawl"
        );
    }

    public function ping($timeout = 10) {
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

            $this->pingRequests[$seq] = array(
                'seq' => $seq,
                'deferred' => $deferred,
                'timer' => $timer
            );

            return $deferred->promise();
        }


    }

    public function onPong(Frame $frame) {
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
     * @param SerializerInterface $serializer
     * @return $this
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @return SerializerInterface
     */
    public function getSerializer()
    {
        return $this->serializer;
    }


} 