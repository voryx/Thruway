<?php


namespace Thruway\Common;

use Thruway\Logging\Logger;

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

    /**
     * Encode and get derived key
     *
     * @param string $key
     * @param string $salt
     * @param int $iterations
     * @param int $keyLen
     * @return string
     */
    public static function getDerivedKey($key, $salt, $iterations = 1000, $keyLen = 32)
    {
        if (function_exists("hash_pbkdf2")) {
            $key = hash_pbkdf2('sha256', $key, $salt, $iterations, $keyLen, true);
        } else {
            // PHP v5.4 compatibility
            $key = static::compat_pbkdf2('sha256', $key, $salt, $iterations, $keyLen, true);
        }

        return base64_encode($key);
    }

    /**
     * Generate a PBKDF2 key derivation of a supplied password
     *
     * This is a hash_pbkdf2() implementation for PHP versions 5.3 and 5.4.
     * @link http://www.php.net/manual/en/function.hash-pbkdf2.php
     * @see https://gist.github.com/rsky/5104756
     *
     * @param string $algo
     * @param string $password
     * @param string $salt
     * @param int $iterations
     * @param int $length
     * @param bool $rawOutput
     *
     * @return string
     */
    public static function compat_pbkdf2($algo, $password, $salt, $iterations, $length = 0, $rawOutput = false)
    {
        // check for hashing algorithm
        if (!in_array(strtolower($algo), hash_algos())) {
            trigger_error(sprintf(
                '%s(): Unknown hashing algorithm: %s',
                __FUNCTION__, $algo
            ), E_USER_WARNING);
            return false;
        }

        // check for type of iterations and length
        foreach ([4 => $iterations, 5 => $length] as $index => $value) {
            if (!is_numeric($value)) {
                trigger_error(sprintf(
                    '%s() expects parameter %d to be long, %s given',
                    __FUNCTION__, $index, gettype($value)
                ), E_USER_WARNING);
                return null;
            }
        }

        // check iterations
        $iterations = (int)$iterations;
        if ($iterations <= 0) {
            trigger_error(sprintf(
                '%s(): Iterations must be a positive integer: %d',
                __FUNCTION__, $iterations
            ), E_USER_WARNING);
            return false;
        }

        // check length
        $length = (int)$length;
        if ($length < 0) {
            trigger_error(sprintf(
                '%s(): Iterations must be greater than or equal to 0: %d',
                __FUNCTION__, $length
            ), E_USER_WARNING);
            return false;
        }

        // check salt
        if (strlen($salt) > PHP_INT_MAX - 4) {
            trigger_error(sprintf(
                '%s(): Supplied salt is too long, max of INT_MAX - 4 bytes: %d supplied',
                __FUNCTION__, strlen($salt)
            ), E_USER_WARNING);
            return false;
        }

        // initialize
        $derivedKey = '';
        $loops      = 1;
        if ($length > 0) {
            $loops = (int)ceil($length / strlen(hash($algo, '', $rawOutput)));
        }

        // hash for each blocks
        for ($i = 1; $i <= $loops; $i++) {
            $digest = hash_hmac($algo, $salt . pack('N', $i), $password, true);
            $block  = $digest;
            for ($j = 1; $j < $iterations; $j++) {
                $digest = hash_hmac($algo, $digest, $password, true);
                $block ^= $digest;
            }
            $derivedKey .= $block;
        }

        if (!$rawOutput) {
            $derivedKey = bin2hex($derivedKey);
        }

        if ($length > 0) {
            return substr($derivedKey, 0, $length);
        }

        return $derivedKey;
    }

    /**
     * Changes the Precision for PHP configs that default to less than 16
     */
    static public function checkPrecision()
    {
        if (ini_get('precision') < 16) {
            Logger::notice(null, 'Changing PHP precision from ' . ini_get('precision') . ' to 16');
            ini_set('precision', 16);
        }
    }
}