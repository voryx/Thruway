<?php

namespace Thruway\Logging;

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
    public function log($level, $message, array $context = [])
    {
        $now = date("Y-m-d\TH:i:s") . substr((string)microtime(), 1, 8);
        echo $now . " " . str_pad($level, 10, " ") . " " . $message . "\n";
    }
}
