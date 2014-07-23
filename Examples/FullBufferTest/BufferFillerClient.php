<?php

class BufferFillerClient extends \Thruway\Peer\Client {
    public function doTimerStuff() {

        $payload = "Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed";

        $this->getPublisher()->publish($this->session, 'bufferFill', array($payload), new stdClass(), array());
        $this->getPublisher()->publish($this->session, 'bufferFill', array($payload), new stdClass(), array());
        $this->getPublisher()->publish($this->session, 'bufferFill', array($payload), new stdClass(), array());
        $this->getPublisher()->publish($this->session, 'bufferFill', array($payload), new stdClass(), array());
        $this->getPublisher()->publish($this->session, 'bufferFill', array($payload), new stdClass(), array());
    }

    public function onSessionStart($session, $transport) {
        $loop = $this->getLoop();

        echo "Setting up timer...\n";

        $loop->addPeriodicTimer(1, array($this, 'doTimerStuff'));
    }
} 