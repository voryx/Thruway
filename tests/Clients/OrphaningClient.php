<?php

/**
 * This class registers a stored proc and upon receiving the registered
 * message goes into an endless loop so it cannot process anything
 * leaving the session hung
 *
 * Class OrphaningClient
 *
 *
 */
class OrphaningClient extends \Thruway\Peer\Client {
    public function onSessionStart($session, $transport) {
        $this->getCallee()->register(
            $this->session,
            'com.example.orphan_testing',
            array($this, 'callOrphaingTest')
        )->then(function () {
                while (true) {
                    sleep(10);
                }
            });
    }

    public function callOrphaningTest() {
        return "From OrphaningClient";
    }
}