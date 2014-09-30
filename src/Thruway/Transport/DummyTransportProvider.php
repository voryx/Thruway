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
class DummyTransportProvider extends AbstractTransportProvider
{

    /**
     * @param \Thruway\Peer\AbstractPeer $peer
     * @param \React\EventLoop\LoopInterface $loop
     */
    public function startTransportProvider(AbstractPeer $peer, LoopInterface $loop)
    {
    }

    /**
     * @return \Thruway\Manager\ManagerInterface
     */
    public function getManager()
    {
        return null;
    }

    /**
     * @param \Thruway\Manager\ManagerInterface $managerInterface
     */
    public function setManager(ManagerInterface $managerInterface)
    {
    }

} 