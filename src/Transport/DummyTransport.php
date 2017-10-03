<?php

namespace Thruway\Transport;

use Thruway\Message\Message;
use Thruway\Serializer\JsonSerializer;

/**
 * Class DummyTransport
 *
 * @package Thruway\Transport
 */
class DummyTransport extends AbstractTransport
{
    /**
     * lastMessageSent holds the last message that was sent on the transport
     * makes testing a little easier too
     *
     * @var Message
     */
    private $lastMessageSent;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->serializer = new JsonSerializer();
    }

    /**
     * @return Message
     */
    public function getLastMessageSent()
    {
        return $this->lastMessageSent;
    }

    /**
     * @param Message $lastMessageSent
     */
    public function setLastMessageSent($lastMessageSent)
    {
        $this->lastMessageSent = $lastMessageSent;
    }

    /**
     * Get transport details
     *
     * @return mixed
     */
    public function getTransportDetails()
    {
        return [
            'type'              => 'dummyTransport',
            'transport_address' => 'dummy'
        ];
    }

    /**
     * Send message
     *
     * @param \Thruway\Message\Message $msg
     */
    public function sendMessage(Message $msg)
    {
        $this->setLastMessageSent($msg);
    }
}
