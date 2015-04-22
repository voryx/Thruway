<?php

namespace Thruway\Transport;


use Thruway\Logging\Logger;
use Thruway\Manager\ManagerDummy;
use Thruway\Peer\AbstractPeer;
use React\EventLoop\LoopInterface;
use Thruway\Peer\Client;
use Thruway\Peer\ClientInterface;

/**
 * Class InternalClientTransportProvider
 *
 * @package Thruway\Transport
 */
class InternalClientTransportProvider extends AbstractTransportProvider
{

    /**
     * @var \Thruway\Peer\ClientInterface
     */
    private $internalClient;

    /**
     * Constructor
     *
     * @param \Thruway\Peer\ClientInterface $internalClient
     */
    public function __construct(ClientInterface $internalClient)
    {
        $this->internalClient = $internalClient;
        $this->manager        = new ManagerDummy();
        $this->trusted        = true;

        // internal clients shouldn't retry if they are killed
        $internalClient->setAttemptRetry(false);

        $this->internalClient->addTransportProvider(new DummyTransportProvider());

    }

    /**
     * Start transport provider
     *
     * @param \Thruway\Peer\AbstractPeer $peer
     * @param \React\EventLoop\LoopInterface $loop
     */
    public function startTransportProvider(AbstractPeer $peer, LoopInterface $loop)
    {
        // the peer that is passed into here is the server that our internal client connects to
        $this->peer = $peer;

        // create a new transport for the router side to use
        $transport = new InternalClientTransport($this->internalClient, $loop);
        $transport->setTrusted($this->trusted);

        // create a new transport for the client side to use
        $clientTransport = new InternalClientTransport($this->peer, $loop);

        // give the transports each other because they are going to call directly into the
        // other side
        $transport->setFarPeerTransport($clientTransport);
        $clientTransport->setFarPeerTransport($transport);


        // connect the transport to the Router/Peer
        $this->peer->onOpen($transport);

        // open the client side
        $this->internalClient->onOpen($clientTransport);


        // tell the internal client to start up
        $this->internalClient->start(false);
    }

    /**
     * Shut down the transport provider
     *
     * @param bool $gracefully
     *
     */
    public function stop($gracefully = true)
    {
        $this->internalClient->onClose("transport stopped");
        Logger::alert($this, "stop not implemented on InternalClientTransportProvider");
    }

    /**
     * @return ClientInterface
     */
    public function getInternalClient()
    {
        return $this->internalClient;
    }


}
