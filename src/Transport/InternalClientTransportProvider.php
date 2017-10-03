<?php

namespace Thruway\Transport;

use Thruway\Event\ConnectionOpenEvent;
use Thruway\Event\RouterStartEvent;
use Thruway\Event\RouterStopEvent;
use Thruway\Message\Message;
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
     * @var ClientInterface
     */
    private $internalClient;

    /**
     * @var Session
     */
    private $session;

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

    public function handleRouterStart(RouterStartEvent $event)
    {
        /** @var Session $session */
        $session = null;

        // create a new transport for the client side to use
        $clientTransport = new InternalClientTransport(function ($msg) use (&$session) {
            $session->dispatchMessage(Message::createMessageFromArray(json_decode(json_encode($msg))));
        }, $this->loop);


        // create a new transport for the router side to use
        $transport = new InternalClientTransport(function ($msg) use ($clientTransport) {
            $this->internalClient->onMessage($clientTransport,
                Message::createMessageFromArray(json_decode(json_encode($msg))));
        }, $this->loop);
        $transport->setTrusted($this->trusted);

        $session       = $this->router->createNewSession($transport);
        $this->session = $session;

        // connect the transport to the Router/Peer
        $this->router->getEventDispatcher()->dispatch('connection_open', new ConnectionOpenEvent($session));

        // open the client side
        $this->internalClient->onOpen($clientTransport);

        // internal client shouldn't retry
        $this->internalClient->setAttemptRetry(false);

        // tell the internal client to start up
        $this->internalClient->start(false);
    }

    public function handleRouterStop(RouterStopEvent $event)
    {
        if ($this->session) {
            $this->session->shutdown();
        }

        $this->internalClient->onClose('router stopped');
    }

    public static function getSubscribedEvents()
    {
        return [
            'router.start' => ['handleRouterStart', 10],
            'router.stop'  => ['handleRouterStop', 10]
        ];
    }
}
