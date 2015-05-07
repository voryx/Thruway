<?php

namespace Thruway\Transport;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use React\EventLoop\LoopInterface;
use React\Stream\Stream;
use Thruway\Logging\Logger;
use Thruway\Message\Message;
use Thruway\Peer\PeerInterface;

/**
 * Class RawSocketTransport
 *
 * Implement transport on raw socket
 *
 * @package Thruway\Transport
 */
class RawSocketTransport extends AbstractTransport implements EventEmitterInterface
{
    use EventEmitterTrait;

    /**
     * @var \React\Stream\Stream
     */
    private $conn;

    /**
     * This buffers the message that is coming in
     *
     * @var string
     */
    private $buffer;

    /**
     * The length of the current message we are coalescing
     *
     * @var int
     */
    private $msgLen;

    /**
     * @var int
     */
    private $handshakeByte;

    /**
     * @var PeerInterface
     */
    private $peer;

    /**
     * Constructor
     *
     * @param \React\Stream\Stream $conn
     * @param \React\EventLoop\LoopInterface $loop
     * @param \Thruway\Peer\PeerInterface $peer
     */
    public function __construct(Stream $conn, LoopInterface $loop, PeerInterface $peer)
    {
        $this->conn = $conn;
        $this->loop = $loop;
        $this->peer = $peer;

        $this->buffer = "";
        $this->msgLen = 0;

        $this->handshakeByte = 0;
    }

    /**
     * Handle process reveived data
     *
     * @param mixed $data
     * @return void
     */
    public function handleData($data)
    {
//        if ($this->handshakeByte == 0) {
//            $this->handshakeByte = $data[0];
//            $data = substr($data, 1);
//        }
        $this->buffer = $this->buffer . $data;

        $bufferLen = strlen($this->buffer);

        while ($bufferLen > 0 && $bufferLen >= $this->msgLen) {
            if ($this->msgLen == 0) {
                // the next 4 bytes are going to be the msglen
                if ($bufferLen >= 4) {
                    $this->msgLen = array_values(unpack("N", $this->buffer))[0];
                    if ($this->msgLen <= 0) {
                        Logger::error("Invalid message size sent");
                        $this->close();
                    }
                    // shift off the first 4 bytes
                    $bufferLen    = $bufferLen - 4;
                    $this->buffer = substr($this->buffer, 4, $bufferLen);
                } else {
                    // we don't have enough to get the message length
                    return;
                }
            }

            if ($bufferLen >= $this->msgLen) {
                $msg = $this->getSerializer()->deserialize(substr($this->buffer, 0, $this->msgLen));

                //$this->peer->onMessage($this, $msg);
                $this->emit("message", [$this, $msg]);

                if ($bufferLen == $this->msgLen) {
                    $this->buffer = "";
                    $this->msgLen = 0;
                    $bufferLen    = 0;
                } else {
                    $bufferLen    = $bufferLen - $this->msgLen;
                    $this->buffer = substr($this->buffer, $this->msgLen, $bufferLen);
                    $this->msgLen = 0;
                }
            }
        }
    }

    /**
     * Get connection
     *
     * @return \React\Stream\Stream
     */
    public function getConn()
    {
        return $this->conn;
    }


    /**
     * Get transport details
     *
     * @return array
     */
    public function getTransportDetails()
    {
        return [
            "type"             => "raw",
            "transport_address" => $this->getConn()->getRemoteAddress()
        ];
    }

    /**
     * Send message
     *
     * @param \Thruway\Message\Message $msg
     */
    public function sendMessage(Message $msg)
    {
        $serializedMsg = $this->getSerializer()->serialize($msg);

        $msgLen = strlen($serializedMsg);

        // need to pack the msgLen in a 32 bit binary string
        $packedMsgLen = pack("N", $msgLen);

        $this->getConn()->write($packedMsgLen);
        $this->getConn()->write($serializedMsg);
    }

    /**
     * Close transport
     */
    public function close()
    {
        $this->getConn()->close();
    }

}
