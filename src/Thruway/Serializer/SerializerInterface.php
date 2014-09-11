<?php

namespace Thruway\Serializer;


use Thruway\Message\Message;

/**
 * Interface SerializerInterface
 *
 * The serializer is responsible for converting input (specific to the serialization)
 * into an array and also taking an array and converting it to output
 *
 * @package Thruway\Serializer
 */
interface SerializerInterface {
    /**
     * @param Message $msg
     * @return mixed
     */
    public function serialize(Message $msg);

    /**
     * @param $serializedData
     * @return Message
     */
    public function deserialize($serializedData);
} 