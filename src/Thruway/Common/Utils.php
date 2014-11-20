<?php


namespace Thruway\Common;

/**
 * Class Utils for common methods
 * @package Thruway\Util
 */
class Utils
{
    /**
     * Generate a unique id for sessions and requests
     * @return mixed
     */
    public static function getUniqueId()
    {
        $filter      = 0x1fffffffffffff; // 53 bits
        $randomBytes = openssl_random_pseudo_bytes(8);
        list($high, $low) = array_values(unpack("N2", $randomBytes));
        return abs(($high << 32 | $low) & $filter);
    }

    /**
     * Strict URI Test
     *
     * @param $uri
     * @return boolean
     */
    public static function uriIsValid($uri)
    {
        return !!preg_match('/^([0-9a-z_]+\.)*([0-9a-z_]+)$/', $uri);
    }

}