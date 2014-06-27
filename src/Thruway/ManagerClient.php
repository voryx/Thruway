<?php

namespace Thruway;

use Thruway\Peer\Client;
use Thruway\Role\Publisher;

class ManagerClient extends Client implements ManagerInterface {



    function __construct()
    {
        parent::__construct("manager");

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

        $this->getCallee()->register($this->session, "manager." . $name, $callback);
    }


//    function testSubscribe() {
//        $this->getSubscriber()->subscribe($this->session, "com.myapp.hello", array($this, "onSomethingElse"));
//    }
//
//    function onSomethingElse($msg) {
//        echo "\n\n\n---------------------------------\n";
//        var_dump($msg);
//        echo "---------------------------------\n";
//    }

    function logIt($logLevel, $msg) {
        echo $logLevel . ": " . $msg . "\n";

        if ($this->getPublisher() instanceof Publisher) {
            $this->getPublisher()->publish($this->session, "manager.log." . strtolower($logLevel), array($msg), array(), array());
        }


    }

    function logInfo($msg) {
        $this->logIt("INFO", $msg);
    }

    function logError($msg) {
        $this->logIt("ERROR", $msg);
    }

    function logWarning($msg) {
        $this->logIt("WARNING", $msg);
    }

    function logDebug($msg) {
        $this->logIt("DEBUG", $msg);
    }
}