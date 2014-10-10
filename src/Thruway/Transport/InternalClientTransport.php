<?php

namespace Thruway\Transport;

use React\EventLoop\LoopInterface;
use Thruway\Exception\PingNotSupportedException;
use Thruway\Message\Message;
use Thruway\Peer\AbstractPeer;
use Thruway\Serializer\SerializerInterface;

/**
 * Class InternalClientTransport
 *
 * @package Thruway\Transport
 */
class InternalClientTransport implements TransportInterface
{

    /**
     * @var \Thruway\Peer\AbstractPeer
     */
    private $farPeer;

    /**
     * @var \Thruway\Transport\TransportInterface
     */
    private $farPeerTransport;

    /**
     * @var \React\EventLoop\LoopInterface
     */
    private $loop;

    /*
     * @var boolean
     */
    private $trusted;

    /**
     * Constructor
     *
     * @param \Thruway\Peer\AbstractPeer $farPeer
     * @param \React\EventLoop\LoopInterface $loop
     */
    public function __construct(AbstractPeer $farPeer, LoopInterface $loop)
    {
        $this->farPeer = $farPeer;
        $this->loop    = $loop;
    }

    /**
     * Set FarPeerTransport
     *
     * @param \Thruway\Transport\TransportInterface $farPeerTransport
     */
    public function setFarPeerTransport($farPeerTransport)
    {
        $this->farPeerTransport = $farPeerTransport;
    }

    /**
     * @return \Thruway\Transport\TransportInterface
     */
    public function getFarPeerTransport()
    {
        return $this->farPeerTransport;
    }

    /**
     * Send message
     *
     * @param \Thruway\Message\Message $msg
     * @throws \Exception
     */
    public function sendMessage(Message $msg)
    {
        if ($this->getFarPeerTransport() === null) {
            throw new \Exception("You must set the farPeerTransport on internal client transports");
        }

        $this->farPeer->onMessage($this->getFarPeerTransport(), $msg);
    }

    /**
     * Close transport
     */
    public function close()
    {
        // TODO: Implement close() method.
    }

    /**
     * Get transport details
     *
     * @return array
     */
    public function getTransportDetails()
    {
        return [
            "type"             => "internalClient",
            "transportAddress" => "internal"
        ];
    }

    /**
     * Ping
     *
     * @throws \Thruway\Exception\PingNotSupportedException
     */
    public function ping()
    {
        throw new PingNotSupportedException;
    }

    /**
     * Handle on pong
     */
    public function onPong()
    {

    }

    /**
     * Set Serializer
     *
     * These are required by interface but not used here because there is no serialization
     */
    public function setSerializer(SerializerInterface $serializer)
    {

    }

    /**
     * Get Serializer
     *
     * These are required by interface but not used here because there is no serialization
     */
    public function getSerializer()
    {

    }

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
     * @param $trusted
     * @return boolean
     */
    public function setTrusted($trusted)
    {
        $this->trusted = $trusted;
    }

} 