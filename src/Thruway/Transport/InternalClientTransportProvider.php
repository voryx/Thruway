<?php

namespace Thruway\Transport;


use Thruway\Logging\Logger;
use Thruway\Manager\ManagerDummy;
use Thruway\Manager\ManagerInterface;
use Thruway\Peer\AbstractPeer;
use React\EventLoop\LoopInterface;

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
     * @var boolean
     */
    private $trusted;

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
        $this->trusted = true;
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
     * Set manager
     *
     * @param \Thruway\Manager\ManagerInterface $manager
     */
    public function setManager(ManagerInterface $manager)
    {
        $this->manager = $manager;
        Logger::info($this, "Manager attached to InternalClientTransportProvider");
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

    /**
     * @param boolean $trusted
     */
    public function setTrusted($trusted)
    {
        $this->trusted = $trusted;
    }
}
