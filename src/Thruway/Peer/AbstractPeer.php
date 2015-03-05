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
     *
     */
    abstract public function addTransportProvider(TransportProviderInterface $transportProvider);
}
