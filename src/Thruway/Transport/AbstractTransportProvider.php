<?php

namespace Thruway\Transport;

use Thruway\Manager\ManagerInterface;
use Thruway\Peer\AbstractPeer;
use React\EventLoop\LoopInterface;

/**
 * abstract class for transport provider
 *
 * @package Thruway\Transport
 */
abstract class AbstractTransportProvider
{

    /**
     * @param \Thruway\Peer\AbstractPeer $peer
     * @param \React\EventLoop\LoopInterface $loop
     */
    abstract public function startTransportProvider(AbstractPeer $peer, LoopInterface $loop);

    /**
     * @return \Thruway\Manager\ManagerInterface
     */
    abstract public function getManager();

    /**
     * @param \Thruway\Manager\ManagerInterface $managerInterface
     */
    abstract public function setManager(ManagerInterface $managerInterface);

}
