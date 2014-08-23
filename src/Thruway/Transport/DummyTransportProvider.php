<?php

namespace Thruway\Transport;


use React\EventLoop\LoopInterface;
use Thruway\Manager\ManagerInterface;
use Thruway\Peer\AbstractPeer;

class DummyTransportProvider extends AbstractTransportProvider {
    /**
     * @param AbstractPeer $peer
     * @param LoopInterface $loop
     */
    public function startTransportProvider(AbstractPeer $peer, LoopInterface $loop)
    {
    }

    /**
     * @return ManagerInterface
     */
    public function getManager()
    {
        return null;
    }

    /**
     * @param ManagerInterface $managerInterface
     */
    public function setManager(ManagerInterface $managerInterface)
    {
    }

} 