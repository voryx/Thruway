<?php

namespace Thruway\Authentication;

use Thruway\Message\HelloMessage;

/**
 * Class WampCraAuthProvider
 *
 * @package Thruway\Authentication
 */
class WampCraAuthProvider extends AbstractAuthProviderClient
{

    /**
     * @var \Thruway\Authentication\WampCraUserDbInterface
     */
    private $userDb;

    /**
     * @return string
     */
    public function getMethodName()
    {
        return 'wampcra';
    }

    /**
     * The arguments given by the server are the actual hello message ($args[0])
     * and some session information ($args[1])
     *
     * The session information is an associative array that contains the sessionId and realm
     *
     * @param array $args
     * @return array
     */
    public function processHello(array $args)
    {
        if (count($args) < 2) {
            return ["ERROR"];
        }
        if ($args[0] instanceof HelloMessage) {
            $helloMsg = $args[0];

            $authid = "";
            if (isset($helloMsg->getDetails()->authid)) {
                $authid = $helloMsg->getDetails()->authid;
            } else {
                return ["ERROR"];
            }

            // lookup the user
            if ($this->getUserDb() === null) {
                return ["FAILURE"];
            }

            $user = $this->getUserDb()->get($authid);
            if ($user === null) {
                return ["FAILURE"];
            }

            // create a challenge
            $nonce        = bin2hex(mcrypt_create_iv(22, MCRYPT_DEV_URANDOM));
            $authRole     = "user";
            $authMethod   = "wampcra";
            $authProvider = "nunya";
            $now          = new \DateTime();
            $timeStamp    = $now->format($now::ISO8601);
            $sessionId    = $args[1]['sessionId'];

            $challenge = [
                "authid"       => $authid,
                "authrole"     => $authRole,
                "authprovider" => $authProvider,
                "authmethod"   => $authMethod,
                "nonce"        => $nonce,
                "timestamp"    => $timeStamp,
                "session"      => $sessionId
            ];

            $serializedChallenge = json_encode($challenge);

            $challengeDetails = [
                "challenge"        => $serializedChallenge,
                "challenge_method" => $this->getMethodName()
            ];

            if ($user['salt'] !== null) {
                // we are using salty password
                $saltInfo = [
                    "salt"       => $user['salt'],
                    "keylen"     => 32,
                    "iterations" => 1000
                ];

                $challengeDetails = array_merge($challengeDetails, $saltInfo);
            }

            return [
                "CHALLENGE",
                $challengeDetails
            ];
        }

        return ["FAILURE"];
    }

    /**
     * Process authenticate
     *
     * @param mixed $signature
     * @param mixed $extra
     * @return array
     */
    public function processAuthenticate($signature, $extra = null)
    {
        if (is_array($extra)) {
            if (isset($extra['challenge_details'])) {
                $challengeDetails = $extra['challenge_details'];
                if (is_array($challengeDetails)) {
                    if (isset($challengeDetails['challenge'])) {
                        $challenge      = $challengeDetails['challenge'];
                        $challengeArray = json_decode($challenge);

                        // lookup the user
                        if ($this->getUserDb() === null) {
                            return ["FAILURE"];
                        }

                        if (!isset($challengeArray->authid)) {
                            return ["FAILURE"];
                        }

                        $authid = $challengeArray->authid;

                        $user = $this->getUserDb()->get($authid);
                        if ($user === null) {
                            return ["FAILURE"];
                        }

                        $keyToUse = $user['key'];

                        $token = base64_encode(hash_hmac('sha256', $challenge, $keyToUse, true));

                        if ($token == $signature) {
                            return [
                                "SUCCESS",
                                [
                                    "authmethod"   => "wampcra",
                                    "authrole"     => "user",
                                    "authid"       => $challengeArray->authid,
                                    "authprovider" => $challengeArray->authprovider
                                ]
                            ];
                        }
                    }
                }
            }
        }

        return ["FAILURE"];
    }

    /**
     * Set userDB
     * 
     * @param \Thruway\Authentication\WampCraUserDbInterface $userDb
     */
    public function setUserDb($userDb)
    {
        $this->userDb = $userDb;
    }

    /**
     * Get UserDB
     * 
     * @return \Thruway\Authentication\WampCraUserDbInterface
     */
    public function getUserDb()
    {
        return $this->userDb;
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

}
