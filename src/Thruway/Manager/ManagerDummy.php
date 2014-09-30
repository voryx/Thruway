<?php

namespace Thruway\Manager;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerTrait;
use Psr\Log\NullLogger;

/**
 * Class ManagerDummy
 * 
 * @package Thruway\Manager
 */

class ManagerDummy implements ManagerInterface 
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
     * @var bool
     */
    private $quiet;
    
    /**
     * Contructor
     */
    public function __construct()
    {
        $this->setLogger(new NullLogger);
    }

    /**
     * This intentionally does nothing
     *
     * @param string $name
     * @param \Closure $callback
     */
    public function addCallable($name, $callback)
    {

    }

    /**
     * Logging
     * 
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @see \Psr\Log\LoggerInterface::log($level, $message, $context);
     */
    public function log($level, $message, array $context = array())
    {
        if ( ! $this->getQuiet()) {
            $this->logger->log($level, $message, $context);
        }
    }

    /**
     * @param boolean $quiet
     */
    public function setQuiet($quiet)
    {
        $this->quiet = $quiet;
    }

    /**
     * @return boolean
     */
    public function getQuiet()
    {
        return $this->quiet;
    }

    /**
     * @return \Psr\Log\LoggerInterface|NullLogger
     */
    public function getLogger()
    {
        return $this->logger;
    }
} 