<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/18/14
 * Time: 10:36 PM
 */

namespace Thruway\Transport;


use Ratchet\WebSocket\Version\RFC6455\Frame;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use Thruway\Exception\PingNotSupportedException;
use Thruway\Message\Message;
use Ratchet\ConnectionInterface;

class RatchetTransport implements TransportInterface {
    /**
     * @var ConnectionInterface
     */
    private $conn;

    private $pingSeq;
    private $pingRequests;

    /**
     * @var LoopInterface
     */
    private $loop;

    function __construct($conn, LoopInterface $loop)
    {
        $this->conn = $conn;
        $this->pingSeq = 1234;
        $this->pingRequests = array();

        $this->loop = $loop;
    }

    public function sendMessage(Message $msg)
    {
        $this->conn->send($msg->getSerializedMessage());
    }

    public function close()
    {
        $this->conn->close();
    }

    public function getTransportDetails()
    {
        $transportAddress = null;

        $transportAddress = $this->conn->remoteAddress;

        return array(
            "type" => "ratchet",
            "transportAddress" => $transportAddress
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
            $this->pingRequests[$seq]['deferred']->resolve($seq);
            /** @var TimerInterface $timer */
            $timer = $this->pingRequests[$seq]['timer'];
            $timer->cancel();

            unset($this->pingRequests[$seq]);
        }

    }
} 