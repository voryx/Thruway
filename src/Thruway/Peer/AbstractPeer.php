<?php

namespace Thruway\Peer;

use Thruway\Manager\ManagerInterface;
use Thruway\Message\Message;
use Thruway\Transport\AbstractTransportProvider;
use Thruway\Transport\TransportInterface;

/**
 * class AbstractPeer
 *
 * @package Thruway\Peer
 */
abstract class AbstractPeer
{
    /**
     * @var ManagerInterface
     */
    protected $manager;

    /**
     * Handle process message
     *
     * @param \Thruway\Transport\TransportInterface $transport
     * @param \Thruway\Message\Message $msg
     */
    abstract public function onMessage(TransportInterface $transport, Message $msg);

    /**
     * Handle process onpen transport
     *
     * @param \Thruway\Transport\TransportInterface $transport
     */
    abstract public function onOpen(TransportInterface $transport);

    /**
     * Add transport provider
     *
     * @param \Thruway\Transport\AbstractTransportProvider $transportProvider
     */
    abstract public function addTransportProvider(AbstractTransportProvider $transportProvider);

    /**
     * Set manager
     *
     * @param \Thruway\Manager\ManagerInterface $manager
     */
    abstract public function setManager($manager);

    /**
     * start can take an argument of $runLoop = true
     * not added here because it would break other people's stuff who
     * don't implement that
     */
    abstract public function start();

}
