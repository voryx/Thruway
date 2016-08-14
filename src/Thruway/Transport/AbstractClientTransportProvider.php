<?php

namespace Thruway\Transport;

abstract class AbstractClientTransportProvider implements ClientTransportProviderInterface
{
    /**
     * @var \Thruway\Peer\ClientInterface
     */
    protected $client;

    /**
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;

    /**
     * @var \Thruway\Transport\TransportInterface
     */
    protected $transport;
}
