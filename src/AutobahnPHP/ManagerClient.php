<?php

namespace AutobahnPHP;

use AutobahnPHP\Peer\Client;

class ManagerClient extends Client {

    function __construct()
    {
        parent::__construct("realm1");
    }

    /**
     * Override start because we are not your typical client
     * We have no transport provider and we do not start a loop
     * (although we may want a loop later on if we want to setup
     * outgoing connections or timers or something)
     */
    function start() {

    }

    function testSubscribe() {
        $this->getSubscriber()->subscribe("com.myapp.hello", array($this, "onSomethingElse"));
    }

    function onSomethingElse($msg) {
        echo "\n\n\n---------------------------------\n";
        var_dump($msg);
        echo "---------------------------------\n";
    }
}