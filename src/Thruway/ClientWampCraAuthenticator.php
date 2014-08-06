<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 8/5/14
 * Time: 8:11 PM
 */

namespace Thruway;


use Thruway\Message\AuthenticateMessage;
use Thruway\Message\ChallengeMessage;

class ClientWampCraAuthenticator implements ClientAuthenticationInterface {
    public $authid;

    public $derivedKey;

    public $key;

    function __construct($authid, $key = null)
    {
        $this->authid = $authid;
        $this->derivedKey = null;
        $this->key = $key;
    }

    public function getAuthenticateFromChallenge(ChallengeMessage $msg)
    {
        echo "Got challenge:\n";
        echo $msg->getSerializedMessage();
        echo "\n";
        if ( ! in_array($msg->getAuthMethod(), $this->getAuthMethods())) {
            //throw new \Exception("method isn't in methods");
            return false;
        }

        if ( ! is_array($msg->getDetails())) {
            echo "No details sent with challenge.\n";
            return false;
        }

        $challenge = '';
        if (isset($msg->getDetails()['challenge'])) {
            $challenge = $msg->getDetails()['challenge'];
        } else {
            echo "No challenge for wampcra?\n";
            return false;
        }

        $keyToUse = $this->key;
        if (isset($msg->getDetails()['salt'])) {
            // we need a salted key
            $salt = $msg->getDetails()['salt'];
            $keyLen = 32;
            if (isset($msg->getDetails()['keylen'])) {
                if (is_numeric($msg->getDetails()['keylen'])) {
                    $keyLen = $msg->getDetails()['keylen'];
                } else {
                    echo "keylen is not numeric.\n";
                }
            }
            $iterations = 1000;
            if (isset($msg->getDetails()['iterations'])) {
                if (is_numeric($msg->getDetails()['iterations'])) {
                    $iterations = $msg->getDetails()['iterations'];
                } else {
                    echo "iterations is not numeric.\n";
                }
            }

            $keyToUse = $this->getDerivedKey($this->key, $salt, $iterations, $keyLen);
        }

        $token = base64_encode(hash_hmac('sha256', $challenge, $keyToUse, true));

        $authMessage = new AuthenticateMessage($token);

        echo "returning: " . $authMessage->getSerializedMessage() . "\n";

        return $authMessage;
    }

    private function getDerivedKey($key, $salt, $iterations = 1000, $keyLen = 32) {
        return base64_encode(hash_pbkdf2('sha256', $key, $salt, $iterations, $keyLen, true));;
    }

    public function getAuthId()
    {
        return $this->authid;
    }

    public function setAuthId($authid)
    {
        $this->authid = $authid;
    }

    public function getAuthMethods()
    {
        return array("wampcra");
    }



} 