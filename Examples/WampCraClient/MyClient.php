<?php

/**
 * Class MyClient
 */
class MyClient extends \Thruway\Peer\Client
{
    /**
     * @param \Thruway\AbstractSession $session
     * @param \Thruway\Transport\TransportInterface $transport
     */
    public function onSessionStart($session, $transport)
    {
        $this->getPublisher()->publish($session, 'testing...', [], [], []);
    }
} 