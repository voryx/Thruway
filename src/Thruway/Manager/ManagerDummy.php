<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/20/14
 * Time: 5:07 PM
 */

namespace Thruway\Manager;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerTrait;
use Psr\Log\NullLogger;


class ManagerDummy implements ManagerInterface {
    use LoggerAwareTrait;
    use LoggerTrait;

    /**
     * @var bool
     */
    private $quiet;
    
    public function __construct()
    {
        $this->setLogger(new NullLogger);
    }

    /**
     * This intentionally does nothing
     *
     * @param $name
     * @param $callback
     */
    public function addCallable($name, $callback)
    {

    }

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

} 