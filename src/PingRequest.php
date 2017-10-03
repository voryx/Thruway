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
     * @var \DateTime
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
     * Constructor
     *
     * @param \Thruway\Message\Message $pingMsg
     */
    public function __construct($pingMsg)
    {
        $this->pingMsg = $pingMsg;
    }

    /**
     * Get Deferred
     *
     * @return \React\Promise\Deferred
     */
    public function getDeferred()
    {
        if ($this->deferred === null) {
            $this->deferred = new Deferred();
        }

        return $this->deferred;
    }

    /**
     * Set timer
     *
     * @param \React\EventLoop\Timer\TimerInterface $timer
     */
    public function setTimer($timer)
    {
        $this->timer = $timer;
    }

    /**
     * Get timer
     *
     * @return \React\EventLoop\Timer\TimerInterface
     */
    public function getTimer()
    {
        return $this->timer;
    }

    /**
     * Set ping message
     *
     * @param \Thruway\Message\PingMessage $pingMsg
     */
    public function setPingMsg($pingMsg)
    {
        $this->pingMsg = $pingMsg;
    }

    /**
     * Get ping message
     *
     * @return \Thruway\Message\PingMessage
     */
    public function getPingMsg()
    {
        return $this->pingMsg;
    }

    /**
     * Set loop
     *
     * @param \React\EventLoop\LoopInterface $loop
     */
    public function setLoop($loop)
    {
        $this->loop = $loop;
    }

    /**
     * Get loop
     *
     * @return \React\EventLoop\LoopInterface
     */
    public function getLoop()
    {
        return $this->loop;
    }
}
