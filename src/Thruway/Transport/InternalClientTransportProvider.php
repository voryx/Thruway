<?php

namespace Thruway\Transport;


use Thruway\Event\ConnectionOpenEvent;
use Thruway\Event\RouterStartEvent;
use Thruway\Peer\ClientInterface;
use Thruway\Session;

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
        /** @var Session $session */
        $session = null;

        // create a new transport for the client side to use
        $clientTransport = new InternalClientTransport(function ($msg) use (&$session) {
            $session->dispatchMessage($msg);
        }, $this->loop);


        // create a new transport for the router side to use
        $transport = new InternalClientTransport(function ($msg) use ($clientTransport) {
            $this->internalClient->onMessage($clientTransport, $msg);
        }, $this->loop);
        $transport->setTrusted($this->trusted);

        $session = $this->router->createNewSession($transport);

        // connect the transport to the Router/Peer
        $this->router->getEventDispatcher()->dispatch("connection_open", new ConnectionOpenEvent($session));

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
