<?php

namespace Thruway\Authentication;

use Thruway\Common\Utils;
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
        Logger::debug($this, "Got challenge");
        Logger::debug($this, "Challenge Message: " . json_encode($msg));


        if (!in_array($msg->getAuthMethod(), $this->getAuthMethods())) {
            //throw new \Exception("method isn't in methods");
            return false;
        }

        $details = $msg->getDetails();
        if (!is_object($details)) {
            Logger::debug($this, "No details sent with challenge");
            return false;
        }

        if (isset($details->challenge)) {
            $challenge = $details->challenge;
        } else {
            Logger::debug($this, "No challenge for wampcra?");
            return false;
        }

        $keyToUse = $this->key;
        if (isset($details->salt)) {
            // we need a salted key
            $salt   = $details->salt;
            $keyLen = 32;
            if (isset($details->keylen)) {
                if (is_numeric($details->keylen)) {
                    $keyLen = $details->keylen;
                } else {
                    Logger::error($this, "keylen is not numeric.");
                }
            }
            $iterations = 1000;
            if (isset($details->iterations)) {
                if (is_numeric($details->iterations)) {
                    $iterations = $details->iterations;
                } else {
                    Logger::error($this, "iterations is not numeric.");
                }
            }

            $keyToUse = Utils::getDerivedKey($this->key, $salt, $iterations, $keyLen);
        }

        $token = base64_encode(hash_hmac('sha256', $challenge, $keyToUse, true));

        $authMessage = new AuthenticateMessage($token);

        Logger::debug($this, "returning: " . json_encode($authMessage));

        return $authMessage;
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