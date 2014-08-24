<?php

namespace Thruway\Manager;

use Thruway\Peer\Client;
use Thruway\Role\Publisher;
use Thruway\Session;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerTrait;
use Psr\Log\NullLogger;

class ManagerClient extends Client implements ManagerInterface
{
    use LoggerAwareTrait;
    use LoggerTrait;


    /**
     * @var
     */
    private $loggingPublish = true;

    function __construct()
    {
        parent::__construct("manager");

        $this->callables = array();
        $this->setLogger(new NullLogger);
    }

    /**
     * Override start because we are not your typical client
     * We have no transport provider and we do not start a loop
     * (although we may want a loop later on if we want to setup
     * outgoing connections or timers or something)
     */
    function start()
    {

    }

    //-------------------------------------------
    /**
     * @var array
     */
    private $callables;

    public function addCallable($name, $callback)
    {
        $this->callables[] = array($name, $callback);

        if ($this->sessionIsUp()) {
            $this->getCallee()->register($this->session, "manager." . $name, $callback);
        }
    }

    public function onSessionStart($session, $transport) {
        foreach ($this->callables as $callable) {
            $this->getCallee()->register($this->session, "manager." . $callable[0], $callable[1]);
        }
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

    function sessionIsUp() {
        $sessionIsUp = false;
        if ($this->session !== null) {
            if ($this->session->getState() == Session::STATE_UP) {
                $sessionIsUp = true;
            }
        }

        return $sessionIsUp;
    }

    public function log($level, $message, array $context = array()) {
        $this->logger->log($level, $message, $context);
    }
}
