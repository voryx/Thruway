<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 8/6/14
 * Time: 11:35 PM
 */

class UserDb {
    private $users;

    function __construct()
    {
        $this->users = array();
    }

    function add($userName, $password, $salt = null) {
        if ($salt !== null) {
            $key = static::getDerivedKey($password, $salt);
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

    public static function getDerivedKey($key, $salt, $iterations = 1000, $keyLen = 32) {
        return base64_encode(hash_pbkdf2('sha256', $key, $salt, $iterations, $keyLen, true));;
    }
} 