<?php

namespace Thruway\Transport;

use Thruway\Manager\ManagerInterface;
use Thruway\Peer\AbstractPeer;
use React\EventLoop\LoopInterface;

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
     * @param \Thruway\Peer\AbstractPeer $peer
     * @param \React\EventLoop\LoopInterface $loop
     */
    public function startTransportProvider(AbstractPeer $peer, LoopInterface $loop);

    /**
     * Get manager
     *
     * @return \Thruway\Manager\ManagerInterface
     */
    public function getManager();

    /**
     * Get manager
     *
     * @param \Thruway\Manager\ManagerInterface $managerInterface
     */
    public function setManager(ManagerInterface $managerInterface);

    /**
     * @param boolean $trusted
     */
    public function setTrusted($trusted);

}
