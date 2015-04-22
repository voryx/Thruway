<?php

namespace Thruway\Transport;


use React\EventLoop\LoopInterface;
use Thruway\Logging\Logger;
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
     * Start transport provider
     *
     * @param \Thruway\Peer\AbstractPeer $peer
     * @param \React\EventLoop\LoopInterface $loop
     */
    public function startTransportProvider(AbstractPeer $peer, LoopInterface $loop)
    {
    }

    /**
     * Shut down the transport provider
     *
     * @param bool $gracefully
     *
     */
    public function stop($gracefully = true)
    {
        Logger::alert($this, "stop not implemented on DummyTransportProvider");
    }


}