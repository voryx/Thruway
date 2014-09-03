<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 9/2/14
 * Time: 10:13 PM
 */

namespace Thruway;

use Psr\Log\AbstractLogger;

class ConsoleLogger extends AbstractLogger {
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