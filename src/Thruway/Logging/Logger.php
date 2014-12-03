<?php

namespace Thruway\Logging;


use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class Logger
{

    /**
     * @var LoggerInterface
     */
    private static $logger;

    /**
     * @param LoggerInterface $logger
     */
    public static function set(LoggerInterface $logger)
    {
        static::$logger = $logger;
    }

    /**
     * Log
     *
     * @param mixed $object
     * @param string $level See \Psr\Log\LogLevel
     * @param string $message
     * @param array $context
     * @return null
     */
    public static function log($object = null, $level = LogLevel::INFO, $message = '', $context = [])
    {
        if (is_object($object)) {
            $className = get_class($object);
            $pid       = getmypid();
            $message   = "[{$className} {$pid}] {$message}";
        }

        if (static::$logger == null) {
            static::$logger = new ConsoleLogger();
        }

        return static::$logger->log($level, $message, $context);
    }

    /**
     * @param mixed $object
     * @param string $message
     * @param array $context
     */
    public static function alert($object = null, $message = '', $context = [])
    {
        static::log($object, LogLevel::ALERT, $message, $context);
    }

    /**
     * @param mixed $object
     * @param string $message
     * @param array $context
     */
    public static function critical($object = null, $message = '', $context = [])
    {
        static::log($object, LogLevel::CRITICAL, $message, $context);
    }

    /**
     * @param mixed $object
     * @param string $message
     * @param array $context
     */
    public static function debug($object = null, $message = '', $context = [])
    {

        static::log($object, LogLevel::DEBUG, $message, $context);
    }

    /**
     * @param mixed $object
     * @param string $message
     * @param array $context
     */
    public static function emergency($object = null, $message = '', $context = [])
    {

        static::log($object, LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * @param mixed $object
     * @param string $message
     * @param array $context
     */
    public static function error($object = null, $message = '', $context = [])
    {
        static::log($object, LogLevel::ERROR, $message, $context);
    }

    /**
     * @param mixed $object
     * @param string $message
     * @param array $context
     */
    public static function info($object = null, $message = '', $context = [])
    {
        static::log($object, LogLevel::INFO, $message, $context);
    }

    /**
     * @param mixed $object
     * @param string $message
     * @param array $context
     */
    public static function notice($object = null, $message = '', $context = [])
    {
        static::log($object, LogLevel::NOTICE, $message, $context);
    }

    /**
     * @param mixed $object
     * @param string $message
     * @param array $context
     */
    public static function warning($object = null, $message = '', $context = [])
    {
        static::log($object, LogLevel::WARNING, $message, $context);
    }

    /**
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     */
    protected function __construct()
    {
    }

    /**
     * Private clone method to prevent cloning of the instance of the
     * *Singleton* instance.
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Private unserialize method to prevent unserializing of the *Singleton*
     * instance.
     *
     * @return void
     */
    private function __wakeup()
    {
    }
}
