<?php

namespace Thruway\Serializer;

use Thruway\Exception\DeserializationException;
use Thruway\Message\Message;

/**
 * Class JsonSerializer
 * Serialize and deserialize using JSON methods
 * 
 * @package Thruway\Serializer
 */

class JsonSerializer implements SerializerInterface
{
    /**
     * Serialize message
     * 
     * @param \Thruway\Message\Message $msg
     * @return string
     */
    public function serialize(Message $msg)
    {
        return json_encode($msg);
    }

    /**
     * Deserialize message
     * 
     * @param string $serializedData
     * @return \Thruway\Message\Message
     * @throws \Thruway\Exception\DeserializationException
     */
    public function deserialize($serializedData)
    {
        if (null === ($data = @json_decode($serializedData, true))) {
            throw new DeserializationException("Error decoding json \"" . $serializedData . "\"");
        }

        $msg = Message::createMessageFromArray($data);

        return $msg;
    }

}
