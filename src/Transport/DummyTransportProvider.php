<?php

namespace Thruway\Transport;

use React\EventLoop\LoopInterface;
use Thruway\Peer\ClientInterface;

/**
 * class DummyTransportProvider
 *
 * @package Thruway\Transport
 */
class DummyTransportProvider extends AbstractClientTransportProvider
{
    /**
     * Start transport provider
     *
     * @param \Thruway\Peer\ClientInterface $client
     * @param \React\EventLoop\LoopInterface $loop
     */
    public function startTransportProvider(ClientInterface $client, LoopInterface $loop)
    {
    }
}
