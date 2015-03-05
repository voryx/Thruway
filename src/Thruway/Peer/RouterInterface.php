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

    /////////
    // Should be removed because they will be modular
    /**
     * Set authentication manager
     *
     * @param \Thruway\Authentication\AuthenticationManagerInterface $authenticationManager
     */
    public function setAuthenticationManager($authenticationManager);

    /**
     * Get realm manager
     *
     * @return \Thruway\RealmManager
     */
    public function getRealmManager();

    /**
     * Get authentication manager
     *
     * @return \Thruway\Authentication\AuthenticationManagerInterface
     */
    public function getAuthenticationManager();

    /**
     * Get session by session ID
     *
     * @param int $sessionId
     * @return \Thruway\Session|boolean
     */
    public function getSessionBySessionId($sessionId);
}
