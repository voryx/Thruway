<?php

namespace Thruway\Transport;

use React\EventLoop\LoopInterface;
use Thruway\Message\Message;
use Thruway\Peer\PeerInterface;

/**
 * Class InternalClientTransport
 *
 * @package Thruway\Transport
 */
class InternalClientTransport extends AbstractTransport
{

    /**
     * @var \Thruway\Peer\RouterInterface
     */
    private $farPeer;

    /**
     * @var \Thruway\Transport\TransportInterface
     */
    private $farPeerTransport;

    /**
     * Constructor
     *
     * @param PeerInterface $farPeer
     * @param \React\EventLoop\LoopInterface $loop
     */
    public function __construct(PeerInterface $farPeer, LoopInterface $loop)
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

} 