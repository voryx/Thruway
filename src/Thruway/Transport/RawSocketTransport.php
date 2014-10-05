<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 8/9/14
 * Time: 6:18 PM
 */

namespace Thruway\Transport;


use React\EventLoop\LoopInterface;
use React\Socket\Connection;
use Thruway\Exception\PingNotSupportedException;
use Thruway\Message\Message;
use Thruway\Peer\AbstractPeer;
use Thruway\Serializer\SerializerInterface;

class RawSocketTransport implements TransportInterface {
    /**
     * @var Connection
     */
    private $conn;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var SerializerInterface
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
     * @var AbstractPeer
     */
    private $peer;

    function __construct(Connection $conn, LoopInterface $loop, AbstractPeer $peer)
    {
        $this->conn = $conn;
        $this->loop = $loop;
        $this->peer = $peer;

        $this->buffer = "";
        $this->msgLen = 0;

        $this->handshakeByte = 0;
    }

    public function handleData($data) {
//        if ($this->handshakeByte == 0) {
//            $this->handshakeByte = $data[0];
//            $data = substr($data, 1);
//        }
        $this->buffer = $this->buffer . $data;

        echo "Data: " . $data . "\n";

        for ($i = 0; $i < 8; $i++) {
            echo ord($this->buffer[$i]) . "(" . $this->buffer[$i] . "),";
        }

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
                    $bufferLen = $bufferLen - 4;
                    $this->buffer = substr($this->buffer, 4, $bufferLen);
                } else {
                    // we don't have enough to get the message length
                    return;
                }
            }


            if ($bufferLen >= $this->msgLen) {
                $msg = $this->getSerializer()->deserialize(substr($this->buffer, 0, $this->msgLen));

                echo "Received Message " . json_encode($msg) . "\n";

                $this->peer->onMessage($this, $msg);

                if ($bufferLen == $this->msgLen) {
                    $this->buffer = "";
                    $this->msgLen = 0;
                    $bufferLen = 0;
                } else {
                    $bufferLen = $bufferLen - $this->msgLen;
                    $this->buffer = substr($this->buffer, $this->msgLen, $bufferLen);
                    $this->msgLen = 0;
                }
            }
        }
    }

    /**
     * @return SerializerInterface
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * @param SerializerInterface $serializer
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @return Connection
     */
    public function getConn()
    {
        return $this->conn;
    }

    /**
     * @return LoopInterface
     */
    public function getLoop()
    {
        return $this->loop;
    }

    /////// TransportInterface

    /**
     * @return mixed
     */
    public function getTransportDetails()
    {
        return [
            "type"             => "raw",
            "transportAddress" => $this->getConn()->getRemoteAddress()
        ];
    }

    /**
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
     */
    public function ping()
    {
        throw new PingNotSupportedException();
    }


}