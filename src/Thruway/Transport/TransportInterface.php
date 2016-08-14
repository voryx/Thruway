<?php

namespace Thruway\Transport;

use Thruway\Message\Message;
use Thruway\Serializer\SerializerInterface;

/**
 * Interface transport
 */
interface TransportInterface
{
    /**
     * @return mixed
     */
    public function getTransportDetails();

    /**
     * @param \Thruway\Message\Message $msg
     */
    public function sendMessage(Message $msg);

    /**
     * Close transport
     */
    public function close();

    /**
     * Ping
     */
    public function ping();

    /**
     * Set serializer
     *
     * @param \Thruway\Serializer\SerializerInterface $serializer
     */
    public function setSerializer(SerializerInterface $serializer);

    /**
     * Get serializer
     *
     * @return \Thruway\Serializer\SerializerInterface
     */
    public function getSerializer();

    /**
     * Checks to see if a transport is trusted
     *
     * @return boolean
     */
    public function isTrusted();

    /**
     * @param boolean $trusted
     */
    public function setTrusted($trusted);
}
