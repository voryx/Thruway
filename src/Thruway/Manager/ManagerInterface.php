<?php

namespace Thruway\Manager;

use Psr\Log\LoggerInterface;

/**
 * Interface manager
 *
 * @package Thruway\Manager
 */
interface ManagerInterface extends LoggerInterface
{

    /**
     * Add callable
     *
     * @param string $name
     * @param \Closure $callback
     */
    public function addCallable($name, $callback);

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger();

}
