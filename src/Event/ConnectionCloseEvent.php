<?php

namespace Thruway\Event;

use Thruway\Session;

class ConnectionCloseEvent extends Event {
    /** @var  Session */
    public $session;

    public function __construct($session)
    {
        $this->session = $session;
    }
}
