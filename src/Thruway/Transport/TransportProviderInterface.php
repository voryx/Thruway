<?php

namespace Thruway\Transport;

use Thruway\Peer\AbstractPeer;
use React\EventLoop\LoopInterface;
use Thruway\Peer\PeerInterface;
use Thruway\Peer\RouterInterface;

/**
 * Interface class for transport provider
 *
 * @package Thruway\Transport
 */
interface TransportProviderInterface
{
    /**
     * Start transport provider
     *
     * @param \Thruway\Peer\PeerInterface $peer
     * @param \React\EventLoop\LoopInterface $loop
     */
    public function startTransportProvider(PeerInterface $peer, LoopInterface $loop);

    /**
     * @param boolean $trusted
     */
    public function setTrusted($trusted);

}
