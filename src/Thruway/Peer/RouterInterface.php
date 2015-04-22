<?php


namespace Thruway\Peer;


use Thruway\Message\Message;
use Thruway\Transport\TransportInterface;
use Thruway\Transport\TransportProviderInterface;


/**
 * Interface RouterInterface
 * @package Thruway\Peer
 */
interface RouterInterface
{

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
     * @param TransportInterface $transport
     * @return
     */
    public function onClose(TransportInterface $transport);


    /**
     * Add transport provider
     *
     * @param \Thruway\Transport\TransportProviderInterface $transportProvider
     * @throws \Exception
     */
    public function addTransportProvider(TransportProviderInterface $transportProvider);


    /**
     * Start the transport
     *
     * @throws \Exception
     */
    public function start();

    /**
     * This is to stop the router.
     *
     * Note that this should bring down all connections and timers associated with the router
     * which will cause the loop to exit once there is nothing being watched.
     *
     * If there are other things added to the loop through clients or otherwise, the loop
     * will continue running.
     *
     * @param bool $gracefully
     */
    public function stop($gracefully = true);

}
