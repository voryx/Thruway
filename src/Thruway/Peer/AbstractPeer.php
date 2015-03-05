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
     * Set manager
     *
     * @param \Thruway\Manager\ManagerInterface $manager
     */
    abstract public function setManager($manager);

    /**
     * Changes the Precision for PHP configs that default to less than 16
     */
    protected function checkPrecision()
    {
        if (ini_get('precision') < 16) {
            Logger::notice($this, 'Changing PHP precision from ' . ini_get('precision') . ' to 16');
            ini_set('precision', 16);
        }
    }

    /**
     *
     */
    abstract public function addTransportProvider(TransportProviderInterface $transportProvider);


}
