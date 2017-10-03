<?php

namespace Thruway\Event;

use Thruway\Message\Message;
use Thruway\Session;

class MessageEvent extends Event
{
    /** @var Session */
    public $session;
    /** @var Message */
    public $message;

    public function __construct($session, $message)
    {
        $this->session = $session;
        $this->message = $message;
    }
}
