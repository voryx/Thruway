<?php

namespace Thruway\Transport;


use Thruway\Event\NewConnectionEvent;
use Thruway\Event\RouterStartEvent;
use Thruway\Peer\ClientInterface;

/**
 * Class InternalClientTransportProvider
 *
 * @package Thruway\Transport
 */
class InternalClientTransportProvider extends AbstractRouterTransportProvider
{
    /**
     * @var \Thruway\Peer\AbstractPeer
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
        $this->trusted        = true;

        $this->internalClient->addTransportProvider(new DummyTransportProvider());
    }

    public function handleRouterStart(RouterStartEvent $event) {
        // create a new transport for the router side to use
        $transport = new InternalClientTransport($this->internalClient, $this->loop);
        $transport->setTrusted($this->trusted);

        // create a new transport for the client side to use
        $clientTransport = new InternalClientTransport($this->router, $this->loop);

        // give the transports each other because they are going to call directly into the
        // other side
        $transport->setFarPeerTransport($clientTransport);
        $clientTransport->setFarPeerTransport($transport);


        // connect the transport to the Router/Peer
        $this->router->getEventDispatcher()->dispatch("new_connection", new NewConnectionEvent($transport));

        // open the client side
        $this->internalClient->onOpen($clientTransport);

        // tell the internal client to start up
        $this->internalClient->start(false);
    }

    public static function getSubscribedEvents()
    {
        return [
            "router.start" => ['handleRouterStart', 10]
        ];
    }


}
