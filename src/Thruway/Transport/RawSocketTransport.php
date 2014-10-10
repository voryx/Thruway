<?php

namespace Thruway\Transport;

use React\EventLoop\LoopInterface;
use React\Socket\Connection;
use React\Stream\Stream;
use Thruway\Exception\PingNotSupportedException;
use Thruway\Message\Message;
use Thruway\Peer\AbstractPeer;
use Thruway\Serializer\SerializerInterface;

/**
 * Class RawSocketTransport
 * 
 * Implement transport on raw socket
 * 
 * @package Thruway\Transport
 */

class RawSocketTransport implements TransportInterface
{

    /**
     * @var \React\Stream\Stream
     */
    private $conn;

    /**
     * @var \React\EventLoop\LoopInterface
     */
    private $loop;

    /**
     * @var \Thruway\Serializer\SerializerInterface
     */
    private $serializer;

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
     * @var \Thruway\Peer\AbstractPeer
     */
    private $peer;

    /*
     * @var boolean
     */
    private $trusted;

    /**
     * Constructor
     * 
     * @param \React\Stream\Stream $conn
     * @param \React\EventLoop\LoopInterface $loop
     * @param \Thruway\Peer\AbstractPeer $peer
     */
    public function __construct(Stream $conn, LoopInterface $loop, AbstractPeer $peer)
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

        while ($bufferLen > 0) {
            if ($this->msgLen == 0) {
                // the next 4 bytes are going to be the msglen
                if ($bufferLen >= 4) {
                    $this->msgLen = array_values(unpack("N", $this->buffer))[0];
                    if ($this->msgLen <= 0) {
                        echo "Invalid message size sent\n";
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

                $this->peer->onMessage($this, $msg);

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
     * Get serializer
     * 
     * @return \Thruway\Serializer\SerializerInterface
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * Set serializer
     * 
     * @param \Thruway\Serializer\SerializerInterface $serializer
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
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
     * Get loop
     * 
     * @return \React\EventLoop\LoopInterface
     */
    public function getLoop()
    {
        return $this->loop;
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
            "transportAddress" => $this->getConn()->getRemoteAddress()
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

    /**
     * Ping
     * 
     * @throws \Thruway\Exception\PingNotSupportedException
     */
    public function ping()
    {
        throw new PingNotSupportedException();
    }

    /**
     * Checks to see if a transport is trusted
     *
     * @return boolean
     */
    public function isTrusted()
    {
        return (boolean)$this->trusted;
    }

    /**
     * @param $trusted
     * @return boolean
     */
    public function setTrusted($trusted)
    {
        $this->trusted = $trusted;
    }


}
