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
     * @param \Thruway\Serializer\SerializerInterface $serializer
     * @return \Thruway\Transport\TransportInterface
     */
    public function setSerializer(SerializerInterface $serializer);

    /**
     * @return \Thruway\Serializer\SerializerInterface
     */
    public function getSerializer();
}
