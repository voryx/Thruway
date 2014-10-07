<?php


namespace Thruway\Transport;


use Thruway\Message\Message;
use Thruway\Serializer\JsonSerializer;
use Thruway\Serializer\SerializerInterface;

class DummyTransport implements TransportInterface {

    /**
     * @var SerializerInterface
     */
    private $serializer;

    function __construct()
    {
        $this->serializer = new JsonSerializer();
    }

    /**
     * @return mixed
     */
    public function getTransportDetails()
    {
        return [
            "type"             => "dummyTransport",
            "transportAddress" => "dummy"
        ];
    }

    /**
     * @param \Thruway\Message\Message $msg
     */
    public function sendMessage(Message $msg)
    {
    }

    /**
     * Close transport
     */
    public function close()
    {
    }

    /**
     * Ping
     */
    public function ping()
    {
    }

    /**
     * @param \Thruway\Serializer\SerializerInterface $serializer
     * @return \Thruway\Transport\TransportInterface
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