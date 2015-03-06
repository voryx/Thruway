<?php


namespace Thruway\Peer;


use Thruway\Message\Message;
use Thruway\Transport\ClientTransportProviderInterface;
use Thruway\Transport\TransportInterface;

/**
 * Interface ClientInterface
 * @package Thruway\Peer
 */
interface ClientInterface extends PeerInterface
{
    /**
     * This is meant to be overridden so that the client can do its
     * thing
     *
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Transport\TransportInterface $transport
     */
    public function onSessionStart($session, $transport);


    /**
     * Handle open transport
     *
     * @param TransportInterface $transport
     */
    public function onOpen(TransportInterface $transport);


    /**
     * Handle process message
     *
     * @param \Thruway\Transport\TransportInterface $transport
     * @param \Thruway\Message\Message $msg
     */
    public function onMessage(TransportInterface $transport, Message $msg);


    /**
     * Handle close session
     *
     * @param mixed $reason
     */
    public function onClose($reason);


    /**
     * Add transport provider
     *
     * @param \Thruway\Transport\TransportProviderInterface $transportProvider
     * @throws \Exception
     */
    public function addTransportProvider(ClientTransportProviderInterface $transportProvider);


    /**
     * Start the transport
     *
     * @param boolean $startLoop
     * @throws \Exception
     */
    public function start($startLoop = true);

}