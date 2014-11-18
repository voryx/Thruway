<?php

namespace Thruway\Authentication;


use Thruway\Logging\Logger;
use Thruway\Message\AuthenticateMessage;
use Thruway\Message\ChallengeMessage;

/**
 * Class ClientWampCraAuthenticator
 */
class ClientWampCraAuthenticator implements ClientAuthenticationInterface
{

    /**
     * @var string|int
     */
    public $authid;

    /**
     * @var string
     */
    public $derivedKey;

    /**
     * @var string
     */
    public $key;

    /**
     * Constructor
     *
     * @param string|int $authid
     * @param string $key
     */
    public function __construct($authid, $key = null)
    {
        $this->authid     = $authid;
        $this->derivedKey = null;
        $this->key        = $key;
    }

    /**
     * Get Authenticate message from challenge message
     *
     * @param \Thruway\Message\ChallengeMessage $msg
     * @return \Thruway\Message\AuthenticateMessage|boolean
     */
    public function getAuthenticateFromChallenge(ChallengeMessage $msg)
    {
        Logger::info($this, "Got challenge");
        Logger::debug($this, "Challenge Message: " . json_encode($msg));

        if (!in_array($msg->getAuthMethod(), $this->getAuthMethods())) {
            //throw new \Exception("method isn't in methods");
            return false;
        }

        if (!is_array($msg->getDetails())) {
            Logger::info($this, "No details sent with challenge");
            return false;
        }

        $challenge = '';
        if (isset($msg->getDetails()['challenge'])) {
            $challenge = $msg->getDetails()['challenge'];
        } else {
            Logger::info($this, "No challenge for wampcra?");
            return false;
        }

        $keyToUse = $this->key;
        if (isset($msg->getDetails()['salt'])) {
            // we need a salted key
            $salt   = $msg->getDetails()['salt'];
            $keyLen = 32;
            if (isset($msg->getDetails()['keylen'])) {
                if (is_numeric($msg->getDetails()['keylen'])) {
                    $keyLen = $msg->getDetails()['keylen'];
                } else {
                    Logger::error($this, "keylen is not numeric.");
                }
            }
            $iterations = 1000;
            if (isset($msg->getDetails()['iterations'])) {
                if (is_numeric($msg->getDetails()['iterations'])) {
                    $iterations = $msg->getDetails()['iterations'];
                } else {
                    Logger::error($this, "iterations is not numeric.");
                }
            }

            $keyToUse = $this->getDerivedKey($this->key, $salt, $iterations, $keyLen);
        }

        $token = base64_encode(hash_hmac('sha256', $challenge, $keyToUse, true));

        $authMessage = new AuthenticateMessage($token);

        Logger::debug($this, "returning: " . json_encode($authMessage));

        return $authMessage;
    }

    /**
     * Get Derived Key
     *
     * @param string $key
     * @param string $salt
     * @param int $iterations
     * @param int $keyLen
     * @return string
     */
    private function getDerivedKey($key, $salt, $iterations = 1000, $keyLen = 32)
    {
        return base64_encode(hash_pbkdf2('sha256', $key, $salt, $iterations, $keyLen, true));;
    }

    /**
     * Get authentication ID
     *
     * @return string
     */
    public function getAuthId()
    {
        return $this->authid;
    }

    /**
     * Set authentication ID
     *
     * @param string $authid
     */
    public function setAuthId($authid)
    {
        $this->authid = $authid;
    }

    /**
     * Get list authenticate methods
     *
     * @return array
     */
    public function getAuthMethods()
    {
        return ['wampcra'];
    }

}