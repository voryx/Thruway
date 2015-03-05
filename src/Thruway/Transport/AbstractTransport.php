<?php

namespace Thruway\Transport;

use Thruway\Exception\PingNotSupportedException;
use Thruway\Serializer\SerializerInterface;

/**
 * Class AbstractTransport
 * @package Thruway\Transport
 */
abstract class AbstractTransport implements TransportInterface
{

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /*
     * @var boolean
     */
    protected $trusted;

    /**
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;

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
     * @param boolean $trusted \
     */
    public function setTrusted($trusted)
    {
        $this->trusted = $trusted;
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
     * Get serializer
     *
     * @return \Thruway\Serializer\SerializerInterface
     */
    public function getSerializer()
    {
        return $this->serializer;
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
        throw new PingNotSupportedException();
    }
}