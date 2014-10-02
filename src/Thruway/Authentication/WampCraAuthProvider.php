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
     * @var  WampCraUserDbInterface
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
     * @return array<string|array>
     */
    public function processHello(array $args)
    {
        if (count($args) < 2) {
            return ["ERROR"];
        }
        if ($args[0] instanceof HelloMessage) {
            $helloMsg = $args[0];

            $authid = "";
            if (isset($helloMsg->getDetails()['authid'])) {
                $authid = $helloMsg->getDetails()['authid'];
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
    }

    /**
     * Process authenticate
     *
     * @param mixed $signature
     * @param mixed $extra
     * @return array<string|array>
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
     * @param WampCraUserDbInterface $userDb
     */
    public function setUserDb($userDb)
    {
        $this->userDb = $userDb;
    }

    /**
     * @return WampCraUserDbInterface
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
        return base64_encode(hash_pbkdf2('sha256', $key, $salt, $iterations, $keyLen, true));;
    }

} 