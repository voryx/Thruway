<?php
require __DIR__ . '/../../vendor/autoload.php';

class InternalClient extends Thruway\Peer\Client {
    function __construct()
    {
        parent::__construct("realm1");

        $this->on('open', array($this, 'onSessionStart'));
    }

    public function onSessionStart($session, $transport) {
        // TODO: now that the session has started, setup the stuff
        echo "--------------- Hello from InternalClient ------------";
        $this->getCallee()->register($this->session, 'com.example.getphpversion', array($this, 'getPhpVersion'));
    }

    function start()
    {
    }

    function getPhpVersion() {
        return array(phpversion());
    }
}