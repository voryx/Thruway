<?php

namespace Thruway;

use Psr\Log\AbstractLogger;

/**
 * Class ConsoleLogger
 * 
 * @package Thruway
 */
class ConsoleLogger extends AbstractLogger
{

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        echo $level . ": " . $message . "\n";
    }

}
