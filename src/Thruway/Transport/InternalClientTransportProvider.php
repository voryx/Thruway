<?php

namespace Thruway\Transport;


use Thruway\Manager\ManagerDummy;
use Thruway\Manager\ManagerInterface;
use Thruway\Peer\AbstractPeer;
use React\EventLoop\LoopInterface;
use Thruway\Peer\Client;

/**
 * Class InternalClientTransportProvider
 *
 * @package Thruway\Transport
 */
class InternalClientTransportProvider implements TransportProviderInterface
{

    /**
     * @var \Thruway\Peer\AbstractPeer
     */
    private $peer;

    /**
     * @var \Thruway\Peer\AbstractPeer
     */
    private $internalClient;

    /**
     * @var \Thruway\Manager\ManagerInterface
     */
    private $manager;

    /**
     * Constructor
     * 
     * @param \Thruway\Peer\AbstractPeer $internalClient
     */
    public function __construct(AbstractPeer $internalClient)
    {
        $this->internalClient = $internalClient;

        $this->internalClient->addTransportProvider(new DummyTransportProvider());

        $this->manager = new ManagerDummy();
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
        $this->internalClient->start();
    }

    /**
     * Set manager
     * 
     * @param \Thruway\Manager\ManagerInterface $manager
     */
    public function setManager(ManagerInterface $manager)
    {
        $this->manager = $manager;

        if ($this->internalClient instanceof Client) {
            $this->internalClient->setLogger($this->getManager()->getLogger());
        }

        $this->manager->info("Manager attached to InternalClientTransportProvider");
    }

    /**
     * Get manager
     * 
     * @return \Thruway\Manager\ManagerInterface
     */
    public function getManager()
    {
        return $this->manager;
    }

} 
