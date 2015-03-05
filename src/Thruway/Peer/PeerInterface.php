<?php


namespace Thruway\Peer;


use Thruway\Message\Message;
use Thruway\Transport\TransportInterface;

interface PeerInterface {
    public function onMessage(TransportInterface $transport, Message $message);
} 