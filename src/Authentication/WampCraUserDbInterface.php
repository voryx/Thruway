<?php

namespace Thruway\Authentication;

/**
 * Interface of Wamp CraUserDb
 *
 * @package Thruway\Authentication
 */

interface WampCraUserDbInterface
{
    /**
     * This should take a authid string as the argument and return
     * an associative array with authid, key, and salt.
     *
     * If salt is non-null, the key is the salted version of the password.
     *
     * @param string $authid
     * @return mixed
     */
    public function get($authid);

}
