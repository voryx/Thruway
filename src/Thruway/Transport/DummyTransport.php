<?php

namespace Thruway\Transport;

use Thruway\Message\Message;
use Thruway\Serializer\JsonSerializer;
use Thruway\Serializer\SerializerInterface;

/**
 * Class DummyTransport
 * 
 * @package Thruway\Transport
 */
class DummyTransport implements TransportInterface
{

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->serializer = new JsonSerializer();
    }

    /**
     * Get transport details
     * 
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
     * Send message
     * 
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
     * Set serializer
     * 
     * @param \Thruway\Serializer\SerializerInterface $serializer
     * @return \Thruway\Transport\TransportInterface
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Set serializer
     * 
     * @return \Thruway\Serializer\SerializerInterface
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

}
