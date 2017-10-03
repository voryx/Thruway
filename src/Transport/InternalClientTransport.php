<?php

namespace Thruway\Transport;

use React\EventLoop\LoopInterface;
use Thruway\Message\Message;

/**
 * Class InternalClientTransport
 *
 * @package Thruway\Transport
 */
class InternalClientTransport extends AbstractTransport
{
    /**
     * Constructor
     *
     * @param callable $sendMessage
     * @param \React\EventLoop\LoopInterface $loop
     */
    public function __construct(callable $sendMessage, LoopInterface $loop)
    {
        $this->sendMessageFunction = $sendMessage;
        $this->loop                = $loop;
    }

    /**
     * Send message
     *
     * @param \Thruway\Message\Message $msg
     * @throws \Exception
     */
    public function sendMessage(Message $msg)
    {
        if (is_callable($this->sendMessageFunction)) {
            call_user_func_array($this->sendMessageFunction, [$msg]);
        }
    }

    /**
     * Get transport details
     *
     * @return array
     */
    public function getTransportDetails()
    {
        return [
            'type'             => 'internalClient',
            'transport_address' => 'internal'
        ];
    }

} 