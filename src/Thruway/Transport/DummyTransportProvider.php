<?php

namespace Thruway\Transport;


use React\EventLoop\LoopInterface;
use Thruway\Manager\ManagerInterface;
use Thruway\Peer\AbstractPeer;

/**
 * class DummyTransportProvider
 *
 * @package Thruway\Transport
 */
class DummyTransportProvider implements TransportProviderInterface
{

    /**
     * Start transport provider
     * 
     * @param \Thruway\Peer\AbstractPeer $peer
     * @param \React\EventLoop\LoopInterface $loop
     */
    public function startTransportProvider(AbstractPeer $peer, LoopInterface $loop)
    {
    }

    /**
     * Get manager
     * 
     * @return \Thruway\Manager\ManagerInterface
     */
    public function getManager()
    {
        return null;
    }

    /**
     * Set manager
     * 
     * @param \Thruway\Manager\ManagerInterface $managerInterface
     */
    public function setManager(ManagerInterface $managerInterface)
    {
    }

} 