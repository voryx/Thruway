<?php

namespace Thruway;

use React\Promise\Deferred;
use Thruway\Message\PingMessage;

/**
 * Class PingRequest
 * 
 * @package Thruway
 */
class PingRequest
{

    /**
     * @var PingMessage
     */
    private $pingMsg;

    /**
     * @var int
     */
    private $timeout;

    /**
     * @var \React\Promise\Deferred
     */
    private $deferred;

    /**
     *
     * @var type 
     */
    private $pingStart;

    /**
     * @var \React\EventLoop\Timer\TimerInterface
     */
    private $timer;
    
    /**
     * @var \React\EventLoop\LoopInterface
     */
    private $loop;

    /**
     * @param \Thruway\Message\Message $pingMsg
     */
    function __construct($pingMsg)
    {
        $this->pingMsg = $pingMsg;
    }

    /**
     * @return \React\Promise\Deferred
     */
    function getDeferred()
    {
        if ($this->deferred === null)
            $this->deferred = new Deferred();

        return $this->deferred;
    }

    /**
     * @param \React\EventLoop\Timer\TimerInterface $timer
     */
    function setTimer($timer)
    {
        $this->timer = $timer;
    }

    /**
     * @return \React\EventLoop\Timer\TimerInterface
     */
    function getTimer()
    {
        return $this->timer;
    }

    /**
     * @param \Thruway\Message\PingMessage $pingMsg
     */
    public function setPingMsg($pingMsg)
    {
        $this->pingMsg = $pingMsg;
    }

    /**
     * @return \Thruway\Message\PingMessage
     */
    public function getPingMsg()
    {
        return $this->pingMsg;
    }

    /**
     * @param \React\EventLoop\LoopInterface $loop
     */
    public function setLoop($loop)
    {
        $this->loop = $loop;
    }

    /**
     * @return \React\EventLoop\LoopInterface
     */
    public function getLoop()
    {
        return $this->loop;
    }

}
