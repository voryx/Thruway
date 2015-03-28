<?php


namespace Thruway\Peer;


use Thruway\Event\EventDispatcher;
use Thruway\Message\Message;
use Thruway\Session;
use Thruway\Transport\TransportInterface;


/**
 * Interface RouterInterface
 * @package Thruway\Peer
 */
interface RouterInterface extends PeerInterface
{
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
     * Start the transport
     *
     * @throws \Exception
     */
    public function start();

    /** @return EventDispatcher */
    public function getEventDispatcher();

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

    /**
     * Add a client that uses the internal transport provider
     *
     * @param ClientInterface $client
     */
    public function addInternalClient(ClientInterface $client);

    /**
     * @param TransportInterface $transport
     * @return Session
     */
    public function createNewSession(TransportInterface $transport);
}
