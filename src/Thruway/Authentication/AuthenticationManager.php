<?php

namespace Thruway\Authentication;

use Thruway\Message\AuthenticateMessage;
use Thruway\Message\ChallengeMessage;
use Thruway\Message\HelloMessage;
use Thruway\Message\Message;
use Thruway\Message\WelcomeMessage;
use Thruway\Peer\Client;
use Thruway\Realm;
use Thruway\Session;
use Thruway\Transport\InternalClientTransport;

/**
 * Class AuthenticationManager
 *
 * @package Thruway\Authentication
 */
class AuthenticationManager extends Client implements AuthenticationManagerInterface
{
    /**
     * List authentication methods
     *
     * @var array
     */
    private $authMethods;

    /**
     * Is authentication manager ready?
     *
     * @var boolean
     */
    private $ready;

    /**
     * Constructor
     */
    function __construct()
    {
        parent::__construct('thruway.auth');

        $this->authMethods = [];
        $this->ready       = false;
    }

    /**
     * Handles session started
     *
     * @param \Thruway\AbstractSession $session
     * @param \Thruway\Transport\TransportProviderInterface $transport
     */
    public function onSessionStart($session, $transport)
    {
        $this->getCallee()->register(
            $session,
            'thruway.auth.registermethod',
            [$this, 'registerAuthMethod'],
            ['discloseCaller' => true]
        )
            ->then(
                function () {
                    $this->setReady(true);
                },
                function () {
                    $this->setReady(false);
                    $this->getManager()->error("registration of registerAuthMethod failed.");
                }
            );
    }

    /**
     * Override to make sure we do nothing
     */
    public function start()
    {

    }

    /**
     * Handles all messages for authentication (Hello and Authenticate)
     * This is called by the Realm to handle authentication
     *
     * @param \Thruway\Realm $realm
     * @param \Thruway\Session $session
     * @param \Thruway\Message\Message $msg
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

            $session->setAuthenticationDetails($authDetails);

            $session->setAuthenticated(true);

            $session->sendMessage(
                new WelcomeMessage(
                    $session->getSessionId(), [
                        'authid'     => $authDetails->getAuthId(),
                        'authmethod' => $authDetails->getAuthMethod()
                    ]
                )
            );
            return;
        }

        if (!$this->readyToAuthenticate()) {
            $session->abort(new \stdClass(), 'thruway.authenticator.not_ready');
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

    /**
     * Handle HelloMessage
     *
     * @param \Thruway\Realm $realm
     * @param \Thruway\Session $session
     * @param \Thruway\Message\HelloMessage $msg
     */
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

                $sessionInfo = [
                    "sessionId" => $session->getSessionId(),
                    "realm"     => $realm->getRealmName()
                ];

                $this->session->call(
                    $authMethodInfo['handlers']['onhello'],
                    [
                        $msg,
                        $sessionInfo
                    ]
                )->then(
                    function ($res) use ($session, $msg) {
                        // this is handling the return of the onhello RPC call
                        if (count($res) < 2) {
                            $session->abort(new \stdClass(), "thruway.auth.invalid_response_to_hello");
                            return;
                        }

                        if ($res[0] == "CHALLENGE") {
                            // TODO: validate challenge message
                            $authMethod = $res[1]['challenge_method'];
                            $challenge  = $res[1]['challenge'];

                            $session->getAuthenticationDetails()->setChallenge($challenge);
                            $session->getAuthenticationDetails()->setChallengeDetails($res[1]);

                            $session->sendMessage(
                                new ChallengeMessage(
                                    $authMethod,
                                    $session->getAuthenticationDetails()->getChallengeDetails()
                                )
                            );
                        } else {
                            if ($res[0] == "NOCHALLENGE") {
                                $session->sendMessage(
                                    new WelcomeMessage(
                                        $session->getSessionId(),
                                        [
                                            "authid"     => $res[1]["authid"],
                                            "authmethod" => $session->getAuthenticationDetails()->getAuthMethod()
                                        ]
                                    )
                                );
                            } else {
                                if ($res[0] == "ERROR") {
                                    $session->abort(new \stdClass(), "authentication_failure");
                                } else {
                                    $session->abort(new \stdClass(), "authentication_failure");
                                }
                            }
                        }
                    },
                    function () use ($session) {
                        $this->getManager()->error("onhello rejected the promise");
                        $session->abort("thruway.error.unknown");
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
                $session->abort(new \stdClass(), "wamp.error.not_authorized");
            } else {
                //Logged in as anonymous

                $session->setAuthenticationDetails(AuthenticationDetails::createAnonymous());

                $roles = ["broker" => new \stdClass, "dealer" => new \stdClass];
                $session->sendMessage(
                    new WelcomeMessage($session->getSessionId(), ["roles" => $roles])
                );
                $session->setAuthenticated(true);
            }
        }
    }

    /**
     * Handle Authenticate message
     *
     * @param \Thruway\Realm $realm
     * @param \Thruway\Session $session
     * @param \Thruway\Message\AuthenticateMessage $msg
     * @throws \Exception
     */
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
                    [
                        'authmethod' => $authMethod,
                        'challenge'  => $session->getAuthenticationDetails()->getChallenge(),
                        'extra'      => [
                            'challenge_details' => $session->getAuthenticationDetails()->getChallengeDetails()
                        ],
                        'signature'  => $msg->getSignature(),
                        'authid'     => $session->getAuthenticationDetails()->getAuthId()
                    ]
                )->then(
                    function ($res) use ($session) {
//                        if (!is_array($res)) {
//                            return;
//                        }
                        if (count($res) < 1) {
                            return;
                        }

                        // we should figure out a way to have the router send the welcome
                        // message so that the roles and extras that go along with it can be
                        // filled in
                        if ($res[0] == "SUCCESS") {
                            $welcomeDetails = ["roles" => []];

                            if (isset($res[1])) {
                                if (is_array($res[1])) {
                                    $welcomeDetails = array_merge($welcomeDetails, $res[1]);
                                }
                            }

                            if (isset($res[1]) && isset($res[1]['authid'])) {
                                $session->getAuthenticationDetails()->setAuthId($res[1]['authid']);
                            }

                            $session->setAuthenticated(true);
                            $session->sendMessage(
                                new WelcomeMessage(
                                    $session->getSessionId(),
                                    $welcomeDetails
                                )
                            );
                        } else {
                            $session->abort(new \stdClass(), "bad.login");
                        }
                    },
                    function () use ($session) {
                        $this->getManager()->error("onauthenticate rejected the promise");
                        $session->abort("thruway.error.unknown");
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
     * @param array $kwargs
     * @param array $details
     * @return array
     */
    public function registerAuthMethod(array $args, $kwargs, $details)
    {
        $details = (array)$details;

        // TODO: should return different error
        if (!is_array($args)) {
            return ["Received non-array arguments in registerAuthMethod"];
        }

        if (count($args) < 2) {
            return ["Not enough arguments sent to registerAuthMethod"];
        }

        $authMethod = $args[0];

        $methodInfo = $args[1];

        $authRealms = $args[2];

        // TODO: validate this stuff
        if (isset($this->authMethods[$authMethod])) {
            // error - there is alreay a registered authMethod of this name
            return ["ERROR", "Method registration already exists"];
        }

        if (!isset($methodInfo['onhello'])) {
            return ["ERROR", "Authentication provider must provide \"onhello\" handler"];
        }

        if (!isset($methodInfo['onauthenticate'])) {
            return ["ERROR", "Authentication provider must provide \"onauthenticate\" handler"];
        }

        if (!isset($details['caller'])) {
            return ["ERROR", "Invocation must provide \"caller\" detail on registration"];
        }


        $this->authMethods[$authMethod] = [
            'authMethod'  => $authMethod,
            'handlers'    => $methodInfo,
            'auth_realms' => $authRealms,
            'session_id'  => $details['caller']
        ];

        return ["SUCCESS"];
    }

    /**
     * @param boolean $ready
     */
    public function setReady($ready)
    {
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
     * @param string $realmName
     * @return boolean
     */
    private function realmHasAuthProvider($realmName)
    {
        foreach ($this->authMethods as $authMethod) {
            foreach ($authMethod['auth_realms'] as $authRealm) {
                if ($authRealm === "*" || $authRealm === $realmName) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * This allows the AuthenticationManager to clean out auth methods that were registered by
     * sessions that are dieing. Otherwise the method could be hijacked by another client in the
     * thruway.auth realm.
     *
     * @param \Thruway\Session $session
     */
    public function onSessionClose(Session $session)
    {
        if ($session->getRealm() && $session->getRealm()->getRealmName() == "thruway.auth") {
            // session is closing in the auth domain
            // check and see if there are any registrations that came from this session
            $sessionId = $session->getSessionId();

            foreach ($this->authMethods as $methodName => $method) {
                if (isset($method['session_id']) && $method['session_id'] == $sessionId) {
                    unset($this->authMethods[$methodName]);
                }
            }
        }
    }

    /**
     * Get list supported authentication methods
     *
     * @return array
     */
    public function getAuthMethods()
    {
        return $this->authMethods;
    }

}