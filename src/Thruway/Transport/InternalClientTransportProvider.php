<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/19/14
 * Time: 11:43 AM
 */

namespace Thruway\Transport;


use Thruway\Manager\ManagerDummy;
use Thruway\Manager\ManagerInterface;
use Thruway\Peer\AbstractPeer;
use React\EventLoop\LoopInterface;

class InternalClientTransportProvider extends AbstractTransportProvider {

    /**
     * @var AbstractPeer
     */
    private $peer;

    /**
     * @var AbstractPeer
     */
    private $internalClient;

    /**
     * @var ManagerInterface
     */
    private $manager;

    function __construct(AbstractPeer $internalClient)
    {
        $this->internalClient = $internalClient;

        $this->internalClient->addTransportProvider(new DummyTransportProvider());

        $this->manager = new ManagerDummy();
    }


    public function startTransportProvider(AbstractPeer $peer, LoopInterface $loop)
    {
        // the peer that is passed into here is the server that our internal client connects to
        $this->peer = $peer;

        // create a new transport for the router side to use
        $transport = new InternalClientTransport($this->internalClient);

        // create a new transport for the client side to use
        $clientTransport = new InternalClientTransport($this->peer);

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
     * @param ManagerInterface $manager
     */
    public function setManager(ManagerInterface $manager)
    {
        $this->manager = $manager;

        $this->manager->logInfo("Manager attached to InternalClientTransportProvider");
    }

    /**
     * @return ManagerInterface
     */
    public function getManager()
    {
        return $this->manager;
    }


} 