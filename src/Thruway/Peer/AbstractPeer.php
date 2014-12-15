<?php

namespace Thruway\Peer;

use Thruway\Logging\Logger;
use Thruway\Message\Message;
use Thruway\Transport\TransportInterface;
use Thruway\Transport\TransportProviderInterface;

/**
 * class AbstractPeer
 *
 * @package Thruway\Peer
 */
abstract class AbstractPeer
{
    /**
     * @var \Thruway\Manager\ManagerInterface
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
     * @param \Thruway\Transport\TransportProviderInterface $transportProvider
     */
    abstract public function addTransportProvider(TransportProviderInterface $transportProvider);

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

    protected function checkPrecision() {
        if (ini_get('precision') < 16) {
            Logger::notice($this, 'Changing PHP precision from ' . ini_get('precision') . ' to 16');
            ini_set('precision', 16);
        }
    }

}
