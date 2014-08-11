<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 8/6/14
 * Time: 11:35 PM
 */

class UserDb implements \Thruway\Authentication\WampCraUserDbInterface {
    private $users;

    function __construct()
    {
        $this->users = array();
    }

    function add($userName, $password, $salt = null) {
        if ($salt !== null) {
            $key = \Thruway\Authentication\WampCraAuthProvider::getDerivedKey($password, $salt);
        } else {
            $key = $password;
        }

        $this->users[$userName] = array("authid" => $userName, "key" => $key, "salt" => $salt);
    }

    function get($authId) {
        if (isset($this->users[$authId])) {
            return $this->users[$authId];
        } else {
            return false;
        }
    }


} 