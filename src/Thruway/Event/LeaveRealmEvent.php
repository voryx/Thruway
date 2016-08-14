<?php

namespace Thruway\Event;

use Thruway\Realm;
use Thruway\Session;

class LeaveRealmEvent extends Event
{
    /** @var Session */
    public $session;

    /** @var Realm */
    public $realm;

    public function __construct($realm, $session)
    {
        $this->realm   = $realm;
        $this->session = $session;
    }
}
