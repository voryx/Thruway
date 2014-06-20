<?php

namespace Thruway;

use Thruway\Peer\Client;

class ManagerClient extends Client implements ManagerInterface {

    function __construct()
    {
        parent::__construct("realm1");

        $this->callables = array();
    }

    /**
     * Override start because we are not your typical client
     * We have no transport provider and we do not start a loop
     * (although we may want a loop later on if we want to setup
     * outgoing connections or timers or something)
     */
    function start() {

    }

    //-------------------------------------------
    /**
     * @var array
     */
    private $callables;

    public function addCallable($name, $callback)
    {
        $this->callables[] = array($name, $callback);

        $this->getCallee()->register("manager.call." . $name, $callback);
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