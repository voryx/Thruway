<?php

namespace Thruway\Transport;


use React\EventLoop\LoopInterface;
use Thruway\Manager\ManagerInterface;
use Thruway\Peer\AbstractPeer;
use Thruway\Peer\PeerInterface;
use Thruway\Peer\RouterInterface;

/**
 * class DummyTransportProvider
 *
 * @package Thruway\Transport
 */
class DummyTransportProvider extends AbstractTransportProvider
{

    /**
     * Start transport provider
     *
     * @param \Thruway\Peer\PeerInterface $peer
     * @param \React\EventLoop\LoopInterface $loop
     */
    public function startTransportProvider(PeerInterface $peer, LoopInterface $loop)
    {
    }

}