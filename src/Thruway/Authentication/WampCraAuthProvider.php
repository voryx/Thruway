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
        $helloMsg    = array_shift($args);
        $sessionInfo = array_shift($args);

        if (!$helloMsg instanceof HelloMessage
            || !$sessionInfo
            || !isset($helloMsg->getDetails()->authid)
            || !$this->getUserDb() instanceof WampCraUserDbInterface
        ) {
            return ["ERROR"];
        }

        $authid = $helloMsg->getDetails()->authid;
        $user   = $this->getUserDb()->get($authid);

        if (!$user) {
            return ["FAILURE"];
        }

        // create a challenge
        $nonce        = bin2hex(mcrypt_create_iv(22, MCRYPT_DEV_URANDOM));
        $authRole     = "user";
        $authMethod   = "wampcra";
        $authProvider = "userdb";
        $now          = new \DateTime();
        $timeStamp    = $now->format($now::ISO8601);
        $sessionId    = $sessionInfo['sessionId'];

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

        return ["CHALLENGE", (object)$challengeDetails];

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

        $challenge = $this->getChallengeFromExtra($extra);

        if (!$challenge
            || !isset($challenge->authid)
            || !$this->getUserDb() instanceof WampCraUserDbInterface
        ) {
            return ["FAILURE"];
        }

        $authid = $challenge->authid;
        $user   = $this->getUserDb()->get($authid);

        if (!$user) {
            return ["FAILURE"];
        }

        $keyToUse = $user['key'];
        $token    = base64_encode(hash_hmac('sha256', json_encode($challenge), $keyToUse, true));

        if ($token != $signature) {
            return ["FAILURE"];
        }

        $authDetails = [
            "authmethod"   => "wampcra",
            "authrole"     => "user",
            "authid"       => $challenge->authid,
            "authprovider" => $challenge->authprovider
        ];

        return ["SUCCESS", $authDetails];

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
     * Gets the Challenge Message from the extra object
     * @param $extra
     * @return bool | \stdClass
     */
    private function getChallengeFromExtra($extra)
    {
        return (is_object($extra)
            && isset($extra->challenge_details)
            && is_object($extra->challenge_details)
            && isset($extra->challenge_details->challenge))
            ? json_decode($extra->challenge_details->challenge)
            : false;
    }
}
