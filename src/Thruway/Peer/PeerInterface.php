<?php

namespace Thruway\Peer;

use Thruway\Message\Message;
use Thruway\Transport\TransportInterface;

/**
 * Interface PeerInterface
 * @package Thruway\Peer
 */
interface PeerInterface
{
    /**
     * @param \Thruway\Transport\TransportInterface $transport
     * @param \Thruway\Message\Message $message
     * @return mixed
     */
    public function onMessage(TransportInterface $transport, Message $message);
}
