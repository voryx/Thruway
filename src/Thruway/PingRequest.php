<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 8/23/14
 * Time: 1:13 AM
 */

namespace Thruway;


use React\Promise\Deferred;
use Thruway\Message\PingMessage;

/**
 * Class PingRequest
 * @package Thruway
 */
class PingRequest {
    /**
     * @var PingMessage
     */
    private $pingMsg;

    private $timeout;

    private $deferred;

    private $pingStart;

    private $timer;

    private $loop;

    function __construct($pingMsg)
    {
        $this->pingMsg = $pingMsg;
    }

    /**
     * @return Deferred
     */
    function getDeferred() {
        if ($this->deferred === null) $this->deferred = new Deferred();

        return $this->deferred;
    }

    function setTimer($timer) {
        $this->timer = $timer;
    }

    function getTimer() {
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
     * @param mixed $loop
     */
    public function setLoop($loop)
    {
        $this->loop = $loop;
    }

    /**
     * @return mixed
     */
    public function getLoop()
    {
        return $this->loop;
    }



}