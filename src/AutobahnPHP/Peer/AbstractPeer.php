<?php

namespace AutobahnPHP\Peer;

use AutobahnPHP\Message\Message;
use AutobahnPHP\Transport\AbstractTransportProvider;
use AutobahnPHP\Transport\TransportInterface;

abstract class AbstractPeer
{
    public function onRawMessage(TransportInterface $transport, $msg)
    {
        echo "Raw message... (" . $msg . ")\n";

        $msgObj = Message::createMessageFromRaw($msg);

        $this->onMessage($transport, $msgObj);
    }

    abstract public function onMessage(TransportInterface $transport, Message $msg);

    abstract public function onOpen(TransportInterface $transport);

    abstract public function addTransportProvider(AbstractTransportProvider $transportProvider);

    abstract public function start();

}