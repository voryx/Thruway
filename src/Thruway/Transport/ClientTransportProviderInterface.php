<?php

namespace Thruway\Transport;

use React\EventLoop\LoopInterface;
use Thruway\Peer\ClientInterface;

interface ClientTransportProviderInterface extends TransportProviderInterface
{
    /**
     * Start transport provider
     *
     * @param \Thruway\Peer\ClientInterface $peer
     * @param \React\EventLoop\LoopInterface $loop
     */
    public function startTransportProvider(ClientInterface $peer, LoopInterface $loop);
}
