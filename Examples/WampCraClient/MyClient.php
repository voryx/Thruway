<?php
/**
 *
 */

class MyClient extends \Thruway\Peer\Client {
    public function onSessionStart($session, $transport) {
        $this->getPublisher()->publish($session, 'testing...', [], [], []);
    }
} 