<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/20/14
 * Time: 4:59 PM
 */

namespace Thruway\Manager;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

interface ManagerInterface extends LoggerInterface {
    public function addCallable($name, $callback);
} 
