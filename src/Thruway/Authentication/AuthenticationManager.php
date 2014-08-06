<?php
namespace Thruway\Authentication;

use Thruway\Message\AbortMessage;
use Thruway\Message\AuthenticateMessage;
use Thruway\Message\ChallengeMessage;
use Thruway\Message\ErrorMessage;
use Thruway\Message\HelloMessage;
use Thruway\Message\Message;
use Thruway\Message\WelcomeMessage;
use Thruway\Peer\Client;
use Thruway\Realm;
use Thruway\Session;
use Thruway\Transport\InternalClientTransport;

class AuthenticationManager extends Client implements AuthenticationManagerInterface
{

    private $authMethods;

    /**
     * @var bool
     */
    private $ready;

    function __construct()
    {
        parent::__construct('thruway.auth');

        $this->authMethods = array();
        $this->ready = false;
    }

    public function onSessionStart($session, $transport)
    {
        $this->getCallee()->register($session, 'thruway.auth.registermethod', array($this, 'registerAuthMethod'))
            ->then(
                function () {
                    $this->setReady(true);
                }
            );
    }

    /**
     * Override to make sure we do nothing
     */
    public function start()
    {

    }

//            new WelcomeMessage(
//                $session->getSessionId(),
//                array(
//                    "authid" => $authenticationProvider->getAuthenticationId(),
//                    "authmethod" => $authenticationProvider->getAuthenticationMethod(),
//                    "authrole" => $authenticationProvider->getAuthenticationRole(),
//                    "roles" => $roles,
//                )
//            )


    /**
     * Handles all messages for authentication (Hello and Authenticate)
     * This is called by the Realm to handle authentication
     *
     * @param Realm $realm
     * @param Session $session
     * @param Message $msg
     * @throws \Exception
     */
    public function onAuthenticationMessage(Realm $realm, Session $session, Message $msg)
    {
        if ($session->isAuthenticated()) {
            throw new \Exception("Message sent to authentication manager for already authenticated session.");
        }

        // internal transport does not need any authentication
        if ($session->getTransport() instanceof InternalClientTransport) {
            $authDetails = new AuthenticationDetails();
            $authDetails->setAuthMethod('internalClient');
            $authDetails->setAuthId('internal');

            // set the authid if the hello has one
            if ($msg instanceof HelloMessage) {
                $details = $msg->getDetails();
                if (isset($details)) {
                    if (isset($details['authid'])) {
                        $authDetails->setAuthId($details['authid']);
                    }
                }
            }

            $session->setAuthenticated(true);

            $session->sendMessage(
                new WelcomeMessage(
                    $session->getSessionId(), array(
                        'authid' => $authDetails->getAuthId(),
                        'authmethod' => $authDetails->getAuthMethod()
                    )
                )
            );
            return;
        }

        if (!$this->readyToAuthenticate()) {
            $session->sendMessage(new AbortMessage(new \stdClass(), 'thruway.authenticator.not_ready'));
            return;
        }

        if ($msg instanceof HelloMessage) {
            if ($session->getAuthenticationDetails() !== null) {
                // Todo: probably shouldn't be so dramatic here
                throw new \Exception(
                    "Hello message sent to authentication manager when there is already authentication details attached."
                );
            }

            $this->handleHelloMessage($realm, $session, $msg);
        } else {
            if ($msg instanceof AuthenticateMessage) {
                $this->handleAuthenticateMessage($realm, $session, $msg);

                //$session->sendMessage(new WelcomeMessage($session->getSessionId(), new \stdClass()));
            } else {
                throw new \Exception("Invalid message type sent to AuthenticationManager.");
            }
        }
    }

    public function handleHelloMessage(Realm $realm, Session $session, HelloMessage $msg)
    {
        $requestedMethods = $msg->getAuthMethods();

        $sentMessage = false;

        // go through our authMethods and see which one matches first
        foreach ($this->authMethods as $authMethod => $authMethodInfo) {
            if (in_array($authMethod, $requestedMethods)
                && (in_array($realm->getRealmName(), $authMethodInfo['auth_realms'])
                    || in_array("*", $authMethodInfo['auth_realms']))
            ) {

                // we can agree on something
                $authDetails = new AuthenticationDetails();

                $authDetails->setAuthMethod($authMethod);
                $helloDetails = $msg->getDetails();
                if (isset($helloDetails['authid'])) {
                    $authDetails->setAuthId($helloDetails['authid']);
                }

                $session->setAuthenticationDetails($authDetails);

                $sessionInfo = array(
                    "sessionId" => $session->getSessionId(),
                    "realm" => $realm->getRealmName()
                );

                $this->session->call(
                    $authMethodInfo['handlers']['onhello'],
                    array(
                        $msg,
                        $sessionInfo
                    )
                )->then(
                    function ($res) use ($session, $msg) {
                        // this is handling the return of the onhello RPC call
                        if (!is_array($res)) {
                            $session->sendMessage(
                                ErrorMessage::createErrorMessageFromMessage(
                                    $msg
                                )
                            );

                            return;
                        };

                        if (count($res) < 2) {
                            $session->sendMessage(
                                ErrorMessage::createErrorMessageFromMessage(
                                    $msg
                                )
                            );
                            return;
                        }

                        if ($res[0] == "CHALLENGE") {
                            // TODO: validate challenge message
                            $authMethod = $res[1]['challenge_method'];
                            $challenge = $res[1]['challenge'];

                            $session->getAuthenticationDetails()->setChallenge($challenge);

                            $session->sendMessage(
                                new ChallengeMessage(
                                    $authMethod,
                                    array('challenge' => $challenge)
                                )
                            );
                        } else {
                            if ($res[0] == "NOCHALLENGE") {
                                $session->sendMessage(
                                    new WelcomeMessage(
                                        $session->getSessionId(),
                                        array(
                                            "authid" => $res[1]["authid"],
                                            "authmethod" => $session->getAuthenticationDetails()->getAuthMethod()
                                        )
                                    )
                                );
                            } else {
                                if ($res[0] == "ERROR") {
                                    $session->sendMessage(new AbortMessage(new \stdClass(), "authentication_failure"));
                                } else {
                                    $session->sendMessage(new AbortMessage(new \stdClass(), "authentication_failure"));
                                }
                            }
                        }
                    }
                );
                $sentMessage = true;
            }
        }

        /*
         * If we've gotten this far without sending a message, it means that no auth methods were sent by the client or the auth method sent
         * by the client hasn't been registered for this realm, so we need to check if there are any auth providers registered for the realm.
         * If there are auth provides registered then Abort. Otherwise we can send a welcome message.
         */
        if (!$sentMessage) {
            if ($this->realmHasAuthProvider($realm->getRealmName())) {
                $session->sendMessage(new AbortMessage(new \stdClass(), "realm_authorization_failure"));
            } else {
                //Logged in as anonymous
                $roles = array("broker" => new \stdClass, "dealer" => new \stdClass);
                $session->sendMessage(
                    new WelcomeMessage($session->getSessionId(), array("roles" => $roles))
                );
            }
        }
    }

    public function handleAuthenticateMessage(Realm $realm, Session $session, AuthenticateMessage $msg)
    {
        if ($session->getAuthenticationDetails() === null) {
            throw new \Exception('Authenticate with no previous auth details');
        }

        $authMethod = $session->getAuthenticationDetails()->getAuthMethod();

        // find the auth method
        foreach ($this->authMethods as $am => $authMethodInfo) {
            if ($authMethod == $am) {
                // found it
                // now we send our authenticate information to the RPC
                $this->getCaller()->call(
                    $this->session,
                    $authMethodInfo['handlers']['onauthenticate'],
                    array(
                        'authmethod' => $authMethod,
                        'challenge' => $session->getAuthenticationDetails()->getChallenge(),
                        'signature' => $msg->getSignature()
                    )
                )->then(
                    function ($res) use ($session) {
                        if (!is_array($res)) {
                            return;
                        }
                        if (count($res) < 1) {
                            return;
                        }

                        if ($res[0] == "SUCCESS") {
                            $session->setAuthenticated(true);
                            $session->sendMessage(
                                new WelcomeMessage(
                                    $session->getSessionId(),
                                    array(
                                        "roles" => array()
                                        /* autobahn.js expects roles, even though it's not called for in the spec*/
                                    )
                                )
                            );
                        } else {
                            $session->sendMessage(new AbortMessage(new \stdClass(), "bad.login"));
                        }
                    }
                );
            }
        }
    }

    /**
     * This is called via WAMP. It is registered as thruway.auth.registermethod
     * it takes arguments in an array - ["methodName", ["realm1", "realm2", "*"],
     *
     *
     * @param array $args
     * @return array
     */
    public function registerAuthMethod(array $args)
    {
        // TODO: should return different error
        if (!is_array($args)) {
            return array("Received non-array arguments in registerAuthMethod");
        }

        if (count($args) < 2) {
            return array("Not enough arguments sent to registerAuthMethod");
        }

        echo "Trying to register auth method \"" . $args[0] . "\"";

        $authMethod = $args[0];

        $methodInfo = $args[1];

        $authRealms = $args[2];

        // TODO: validate this stuff
        if (isset($this->authMethods[$authMethod])) {
            // error - there is alreay a registered authMethod of this name
            return array("ERROR", "Method registration already exists");
        }

        if (!isset($methodInfo['onhello'])) {
            return array("ERROR", "Authentication provider must provide \"onhello\" handler");
        }

        if (!isset($methodInfo['onauthenticate'])) {
            return array("ERROR", "Authentication provider must provide \"onauthenticate\" handler");
        }


        $this->authMethods[$authMethod] = array(
            'authMethod' => $authMethod,
            'handlers' => $methodInfo,
            'auth_realms' => $authRealms,
        );

        return array("SUCCESS");
    }

    /**
     * @param boolean $ready
     */
    public function setReady($ready)
    {
        echo "Authentication Manager is now ready.\n";
        $this->ready = $ready;
    }

    /**
     * @return boolean
     */
    public function getReady()
    {
        return $this->ready;
    }

    /**
     * @return boolean
     */
    public function readyToAuthenticate()
    {
        return $this->getReady();
    }

    /**
     * Checks to see if a realm has a registered auth provider
     * @param $realmName
     * @return bool
     */
    private function realmHasAuthProvider($realmName)
    {
        $return = false;

        foreach ($this->authMethods as $authMethod) {
            foreach ($authMethod['auth_realms'] as $authRealm) {
                if ($authRealm === "*" || $authRealm === $realmName) {
                    $return = true;
                    echo "Tried to access realm: {$realmName}, but it expects an authmethod from the client\n";
                    break;
                }
            }
        }

        return $return;
    }

}