<?php

namespace Thruway\Event;

use Thruway\Realm;

/**
 * Class NewRealmEvent
 * @package Thruway\Event
 */
class NewRealmEvent extends Event
{
    /** @var  Realm */
    public $realm;

    /**
     * @param $realm
     */
    public function __construct($realm)
    {
        $this->realm = $realm;
    }
}
