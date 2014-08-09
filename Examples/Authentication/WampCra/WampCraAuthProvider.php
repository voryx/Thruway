<?php


namespace WampCra;


use Thruway\Authentication\AbstractAuthProviderClient;
use Thruway\Message\HelloMessage;

class WampCraAuthProvider extends AbstractAuthProviderClient {

    private $userDb;

    /**
     * @return string
     */
    public function getMethodName() {
        return 'wampcra';
    }

    /**
     * The arguments given by the server are the actual hello message ($args[0])
     * and some session information ($args[1])
     *
     * The session information is an associative array that contains the sessionId and realm
     *
     * @param array $args
     * @return array|void
     */
    public function processHello(array $args) {
        if (count($args) < 2) {
            return array("ERROR");
        }
        if ($args[0] instanceof HelloMessage) {
            $helloMsg = $args[0];

            $authid = "";
            if (isset($helloMsg->getDetails()['authid'])) {
                $authid = $helloMsg->getDetails()['authid'];
            } else {
                return array("ERROR");
            }

            // lookup the user
            if ($this->getUserDb() === null) {
                return array("FAILURE");
            }

            $user = $this->getUserDb()->get($authid);
            if ($user === null) {
                return array("FAILURE");
            }



            // create a challenge
            $nonce = bin2hex(mcrypt_create_iv(22, MCRYPT_DEV_URANDOM));
            $authRole = "user";
            $authMethod = "wampcra";
            $authProvider = "nunya";

            $now = new \DateTime();

            $timeStamp = $now->format($now::ISO8601);

            $sessionId = $args[1]['sessionId'];

            $challenge = array(
                "authid" => $authid,
                "authrole" => $authRole,
                "authprovider" => $authProvider,
                "authmethod" => $authMethod,
                "nonce" => $nonce,
                "timestamp" => $timeStamp,
                "session" => $sessionId
            );

            $serializedChallenge = json_encode($challenge);

            $challengeDetails = array(
                "challenge" => $serializedChallenge,
                "challenge_method" => $this->getMethodName()
            );

            if ($user['salt'] !== null) {
                // we are using salty password
                $saltInfo = array(
                    "salt" => $user['salt'],
                    "keylen" => 32,
                    "iterations" => 1000
                );

                $challengeDetails = array_merge($challengeDetails, $saltInfo);
            }

            return array(
                "CHALLENGE",
                $challengeDetails
            );
        }
    }

    public function processAuthenticate($signature, $extra = NULL) {
        if (is_array($extra)) {
            if (isset($extra['challenge_details'])) {
                $challengeDetails = $extra['challenge_details'];
                if (is_array($challengeDetails)) {
                    if (isset($challengeDetails['challenge'])) {
                        $challenge = $challengeDetails['challenge'];
                        $challengeArray = json_decode($challenge);

                        // lookup the user
                        if ($this->getUserDb() === null) {
                            return array("FAILURE");
                        }

                        if ( ! isset($challengeArray->authid)) {
                            return array("FAILURE");
                        }

                        $authid = $challengeArray->authid;

                        $user = $this->getUserDb()->get($authid);
                        if ($user === null) {
                            return array("FAILURE");
                        }

                        $keyToUse = $user['key'];

                        $token = base64_encode(hash_hmac('sha256', $challenge, $keyToUse, true));

                        echo "Sig should be: " . $token . "\n";

                        if ($token == $signature) {
                            return array("SUCCESS",array(
                                "authmethod" => "wampcra",
                                "authrole" => "user",
                                "authid" => $challengeArray->authid,
                                "authprovider" => $challengeArray->authprovider
                            ));
                        }
                    }
                }
            }
        }

        return array("FAILURE");
    }

    /**
     * @param mixed $userDb
     */
    public function setUserDb($userDb)
    {
        $this->userDb = $userDb;
    }

    /**
     * @return mixed
     */
    public function getUserDb()
    {
        return $this->userDb;
    }



} 