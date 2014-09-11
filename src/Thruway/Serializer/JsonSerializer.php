<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 9/10/14
 * Time: 10:19 PM
 */

namespace Thruway\Serializer;


use Thruway\Exception\DeserializationException;
use Thruway\Message\Message;

class JsonSerializer implements SerializerInterface {
    public function serialize(Message $msg)
    {
        return json_encode($msg);
    }

    public function deserialize($serializedData)
    {
        if (null === ($data = @json_decode($serializedData, true))) {
            throw new DeserializationException("Error decoding json \"" . $serializedData . "\"");
        }

        $msg = Message::createMessageFromArray($data);

        return $msg;
    }
}