<?php

namespace Thruway\Manager;

use Thruway\Peer\Client;
use Thruway\Role\Publisher;
use Thruway\Session;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerTrait;
use Psr\Log\NullLogger;

/**
 * Class managerClient
 * 
 * @package Thruway\Manager
 */

class ManagerClient extends Client implements ManagerInterface
{
    /**
     * Implements \Psr\Log\LoggerAwareInterface
     * @see Psr\Log\LoggerAwareTrait
     */
    use LoggerAwareTrait;
    
    /**
     * Implements \Psr\Log\LoggerInterface
     * @see Psr\Log\LoggerTrait
     */
    use LoggerTrait;

    /**
     * @var boolean
     */
    private $loggingPublish = true;

    /**
     * Contructor
     */
    function __construct()
    {
        parent::__construct("manager");

        $this->callables = [];
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

    /**
     * @var array
     */
    private $callables;

    /**
     * Add callable
     * 
     * @param string $name
     * @param \Closure $callback
     */
    public function addCallable($name, $callback)
    {
        $this->callables[] = [$name, $callback];

        if ($this->sessionIsUp()) {
            $this->getCallee()->register($this->session, "manager." . $name, $callback);
        }
    }

    /**
     * Handle start session
     * Register all added callables for manager
     * 
     * @param \Thruway\Session $session
     * @param \Thruway\Transport\TransportInterface $transport
     */
    public function onSessionStart($session, $transport) 
    {
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

    /**
     * Check session is up (started)
     * 
     * @return boolean
     */
    public function sessionIsUp()
    {
        $sessionIsUp = false;
        if ($this->session !== null) {
            if ($this->session->getState() == Session::STATE_UP) {
                $sessionIsUp = true;
            }
        }

        return $sessionIsUp;
    }

    /**
     * Logging
     * 
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @see \Psr\Log\LoggerInterface::log($level, $message, $context)
     */
    public function log($level, $message, array $context = array()) 
    {
        $this->logger->log($level, $message, $context);
    }

    /**
     * Get logger
     * @return \Psr\Log\LoggerInterface|NullLogger
     */
    public function getLogger()
    {
        return $this->logger;
    }
}
