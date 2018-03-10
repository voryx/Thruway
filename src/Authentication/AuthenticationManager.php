<?php

namespace Thruway\Authentication;

use React\EventLoop\LoopInterface;
use Thruway\Event\MessageEvent;
use Thruway\Event\NewRealmEvent;
use Thruway\Logging\Logger;
use Thruway\Message\AuthenticateMessage;
use Thruway\Message\ChallengeMessage;
use Thruway\Message\HelloMessage;
use Thruway\Message\Message;
use Thruway\Message\WelcomeMessage;
use Thruway\Module\RealmModuleInterface;
use Thruway\Module\RouterModuleClient;
use Thruway\Peer\RouterInterface;
use Thruway\Realm;
use Thruway\Session;

/**
 * Class AuthenticationManager
 *
 * @package Thruway\Authentication
 */
class AuthenticationManager extends RouterModuleClient implements RealmModuleInterface

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
    public function __construct()
    {
        parent::__construct('thruway.auth');

        $this->authMethods = [];
        $this->ready       = false;
    }

    /**
     * Listen for Router events
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'new_realm' => ['handleNewRealm', 10]
        ];
    }

    /**
     * Listen for Realm events
     * @return array
     */
    public function getSubscribedRealmEvents()
    {
        return [
            'HelloMessageEvent'        => ['handleMessageEvent', 99],
            'AuthenticateMessageEvent' => ['handleMessageEvent', 100],
        ];
    }

    /**
     * @param \Thruway\Event\NewRealmEvent $event
     */
    public function handleNewRealm(NewRealmEvent $event)
    {
        $event->realm->addModule($this);
    }

    /**
     * @param \Thruway\Event\MessageEvent $event
     */
    public function handleMessageEvent(MessageEvent $event)
    {
        $msg     = $event->message;
        $session = $event->session;

        $this->processMessage($session->getRealm(), $session, $msg);

        $event->stopPropagation();
    }

    /**
     * Gets called when the module is initialized in the router
     *
     * @inheritdoc
     */
    public function initModule(RouterInterface $router, LoopInterface $loop)
    {
        parent::initModule($router, $loop);
    }

    /**
     * Handles session started
     *
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Transport\TransportProviderInterface $transport
     */
    public function onSessionStart($session, $transport)
    {
        $session->register('thruway.auth.registermethod', [$this, 'registerAuthMethod'], ['disclose_caller' => true])
            ->then(
                function () {
                    $this->setReady(true);
                },
                function () {
                    $this->setReady(false);
                    Logger::error($this, 'registration of registerAuthMethod failed.');
                }
            );
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
    private function processMessage(Realm $realm, Session $session, Message $msg)
    {
        if ($session->isAuthenticated()) {
            throw new \Exception('Message sent to authentication manager for already authenticated session.');
        }

        // trusted transports do not need any authentication
        if ($session->getTransport()->isTrusted()) {
            $authDetails = new AuthenticationDetails();
            $authDetails->setAuthMethod('internalClient');
            $authDetails->setAuthId('internal');

            // set the authid if the hello has one
            if ($msg instanceof HelloMessage) {
                $details = $msg->getDetails();
                if (isset($details) && isset($details->authid)) {
                    $authDetails->setAuthId($details->authid);
                }
            }

            $authDetails->addAuthRole('authenticated_user');
            $authDetails->addAuthRole('admin');

            $session->setAuthenticationDetails($authDetails);
            $session->setAuthenticated(true);

            $details             = new \stdClass();
            $details->authid     = $authDetails->getAuthId();
            $details->authmethod = $authDetails->getAuthMethod();
            $details->authrole   = $authDetails->getAuthRole();
            $details->authroles  = $authDetails->getAuthRoles();

            $session->sendMessage(new WelcomeMessage($session->getSessionId(), $details));

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
                    'Hello message sent to authentication manager when there is already authentication details attached.'
                );
            }

            $this->handleHelloMessage($realm, $session, $msg);
        } else {
            if ($msg instanceof AuthenticateMessage) {
                $this->handleAuthenticateMessage($realm, $session, $msg);
            } else {
                throw new \Exception('Invalid message type sent to AuthenticationManager.');
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
        $sentMessage      = false;

        // Go through the authmethods and try to send a response message
        foreach ($this->authMethods as $authMethod => $authMethodInfo) {
            if (in_array($authMethod, $requestedMethods)
                && (in_array($realm->getRealmName(), $authMethodInfo['auth_realms'])
                    || in_array("*", $authMethodInfo['auth_realms']))
            ) {
                $this->onHelloAuthHandler($authMethod, $authMethodInfo, $realm, $session, $msg);
                $sentMessage = true;
            }
        }

        //If we already replied with a message, we don't have to do anything else
        if ($sentMessage) {
            return;
        }

        // If no authentication providers are registered for this realm send an abort message
        if ($this->realmHasAuthProvider($realm->getRealmName())) {
            $session->abort(new \stdClass(), 'wamp.error.not_authorized');

            return;
        }

        //If we've gotten this far, it means that the user needs to be Logged in as anonymous
        $session->setAuthenticationDetails(AuthenticationDetails::createAnonymous());

        $details = new \stdClass();

        $session->sendMessage(new WelcomeMessage($session->getSessionId(), $details));
        $session->setAuthenticated(true);

    }

    /**
     * Call the RPC URI that has been registered to handle Authentication Hello Messages
     *
     * @param $authMethod
     * @param $authMethodInfo
     * @param Realm $realm
     * @param Session $session
     * @param HelloMessage $msg
     */
    private function onHelloAuthHandler($authMethod, $authMethodInfo, Realm $realm, Session $session, HelloMessage $msg)
    {

        $authDetails = new AuthenticationDetails();
        $authDetails->setAuthMethod($authMethod);

        $helloDetails = $msg->getDetails();
        if (isset($helloDetails->authid)) {
            $authDetails->setAuthId($helloDetails->authid);
        }

        $session->setAuthenticationDetails($authDetails);

        $sessionInfo = ['sessionId' => $session->getSessionId(), 'realm' => $realm->getRealmName()];

        $onHelloSuccess = function ($res) use ($realm, $session, $msg) {
            // this is handling the return of the onhello RPC call

            if (isset($res[0]) && $res[0] === 'FAILURE') {
                $this->abortSessionUsingResponse($session, $res);

                return;
            }

            if (count($res) < 2) {
                $session->abort(new \stdClass(), 'thruway.auth.invalid_response_to_hello');

                return;
            }

            switch ($res[0]) {
                case 'CHALLENGE':
                    // TODO: validate challenge message
                    $authMethod = $res[1]->challenge_method;
                    $challenge  = $res[1]->challenge;

                    $session->getAuthenticationDetails()->setChallenge($challenge);
                    $session->getAuthenticationDetails()->setChallengeDetails($res[1]);

                    $challengeDetails = $session->getAuthenticationDetails()->getChallengeDetails();
                    $session->sendMessage(new ChallengeMessage($authMethod, $challengeDetails));
                    break;

                case 'NOCHALLENGE':
                    $details             = $res[1];
                    $details->authmethod = $session->getAuthenticationDetails()->getAuthMethod();

                    $this->sendWelcomeMessage($session, $details);
                    break;

                default:
                    $session->abort(new \stdClass(), "thruway.error.authentication_failure");
            }

        };

        $onHelloError = function () use ($session) {
            Logger::error($this, 'onhello rejected the promise');
            $session->abort(new \stdClass(), 'thruway.error.unknown');
        };

        $onHelloAuthHandler = $authMethodInfo['handlers']->onhello;

        //Make the OnHello Call
        $this->session->call($onHelloAuthHandler, [$msg, $sessionInfo])
            ->then($onHelloSuccess, $onHelloError);

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
                $this->onAuthenticateHandler($authMethod, $authMethodInfo, $realm, $session, $msg);
            }
        }
    }

    /**
     * Call the handler that was registered to handle the Authenticate Message
     *
     * @param $authMethod
     * @param $authMethodInfo
     * @param Realm $realm
     * @param Session $session
     * @param AuthenticateMessage $msg
     */
    private function onAuthenticateHandler($authMethod, $authMethodInfo, Realm $realm, Session $session, AuthenticateMessage $msg)
    {

        $onAuthenticateSuccess = function ($res) use ($realm, $session) {

            if (count($res) < 1) {
                $session->abort(new \stdClass(), 'thruway.error.authentication_failure');

                return;
            }

            // we should figure out a way to have the router send the welcome
            // message so that the roles and extras that go along with it can be
            // filled in
            if ($res[0] === 'SUCCESS') {
                $this->sendWelcomeMessage($session, $res[1]);
            } elseif (isset($res[0]) && $res[0] === 'FAILURE') {
                $this->abortSessionUsingResponse($session, $res);
            } else {
                $session->abort(new \stdClass(), 'thruway.error.authentication_failure');
            }
        };

        $onAuthenticateError = function () use ($session) {
            Logger::error($this, 'onauthenticate rejected the promise');
            $session->abort(new \stdClass(), 'thruway.error.unknown');
        };

        $extra                    = new \stdClass();
        $extra->challenge_details = $session->getAuthenticationDetails()->getChallengeDetails();

        $arguments                = new \stdClass();
        $arguments->extra         = $extra;
        $arguments->authid        = $session->getAuthenticationDetails()->getAuthId();
        $arguments->challenge     = $session->getAuthenticationDetails()->getChallenge();
        $arguments->signature     = $msg->getSignature();
        $arguments->authmethod    = $authMethod;
        $arguments->hello_message = $session->getHelloMessage();

        // now we send our authenticate information to the RPC
        $onAuthenticateHandler = $authMethodInfo['handlers']->onauthenticate;

        $this->session->call($onAuthenticateHandler, [$arguments])
            ->then($onAuthenticateSuccess, $onAuthenticateError);

    }

    /**
     * @param $session
     * @param $authenticatedDetails
     */
    private function sendWelcomeMessage(Session $session, \stdClass $authenticatedDetails)
    {
        $welcomeDetails = new \stdClass();

        $authenticationDetails = $session->getAuthenticationDetails();

        if (isset($authenticatedDetails->authid)) {
            $authenticationDetails->setAuthId($authenticatedDetails->authid);
        } else {
            $authenticationDetails->setAuthId('authenticated_user');
        }

        $authRole = 'authenticated_user';
        $authenticationDetails->addAuthRole($authRole);
        if (isset($authenticatedDetails->authroles)) {
            $authenticationDetails->addAuthRole($authenticatedDetails->authroles);
        }

        if (isset($authenticatedDetails->authrole)) {
            $authenticationDetails->addAuthRole($authenticatedDetails->authrole);
        }

        if (isset($authenticatedDetails->_thruway_authextra)) {
            $authenticationDetails->setAuthExtra($authenticatedDetails->_thruway_authextra);
        }

        if (isset($authenticatedDetails) && \is_object($authenticationDetails)) {
            $authenticatedDetails->authrole  = $authenticationDetails->getAuthRole();
            $authenticatedDetails->authroles = $authenticationDetails->getAuthRoles();
            $authenticatedDetails->authid    = $authenticationDetails->getAuthId();
            foreach ((array)$authenticatedDetails as $k => $v) {
                $welcomeDetails->$k = $v;
            }
        }

        $session->setAuthenticated(true);
        $session->sendMessage(new WelcomeMessage($session->getSessionId(), $welcomeDetails));
    }

    /**
     * This is called via a WAMP RPC URI. It is registered as thruway.auth.registermethod
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

        // TODO: should return different error
        if (!is_array($args)) {
            return ['Received non-array arguments in registerAuthMethod'];
        }

        if (count($args) < 2) {
            return ['Not enough arguments sent to registerAuthMethod'];
        }

        list($authMethod, $methodInfo, $authRealms) = $args;

        // TODO: validate this stuff
        if (isset($this->authMethods[$authMethod])) {
            // error - there is alreay a registered authMethod of this name
            return ['ERROR', 'Method registration already exists'];
        }

        if (!isset($methodInfo->onhello)) {
            return ['ERROR', 'Authentication provider must provide "onhello" handler'];
        }

        if (!isset($methodInfo->onauthenticate)) {
            return ['ERROR', 'Authentication provider must provide "onauthenticate" handler'];
        }

        if (!isset($details->caller)) {
            return ['ERROR', 'Invocation must provide "caller" detail on registration'];
        }

        $this->authMethods[$authMethod] = [
            'authMethod'  => $authMethod,
            'handlers'    => $methodInfo,
            'auth_realms' => $authRealms,
            'session_id'  => $details->caller
        ];

        return ['SUCCESS'];
    }

    /**
     * Checks to see if a realm has a registered auth provider
     *
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
        if ($session->getRealm() && $session->getRealm()->getRealmName() === 'thruway.auth') {
            // session is closing in the auth domain
            // check and see if there are any registrations that came from this session
            $sessionId = $session->getSessionId();

            foreach ($this->authMethods as $methodName => $method) {
                if (isset($method['session_id']) && $method['session_id'] === $sessionId) {
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

    /**
     * Set ready flag
     *
     * @param boolean $ready
     */
    public function setReady($ready)
    {
        $this->ready = $ready;
    }

    /**
     * Get ready flag
     *
     * @return boolean
     */
    public function getReady()
    {
        return $this->ready;
    }

    /**
     * Check ready to authenticate
     *
     * @return boolean
     */
    public function readyToAuthenticate()
    {
        return $this->getReady();
    }

    /**
     * Send an abort message to the session if the Authenticator sent a FAILURE response
     * Returns true if the abort was sent, false otherwise
     *
     * @param Session $session
     * @param $response
     * @return bool
     * @throws \Exception
     */
    private function abortSessionUsingResponse(Session $session, $response)
    {
        // $response needs to be a failure
        if (!isset($response[0]) || $response[0] !== 'FAILURE') {
            return false;
        }

        if (!isset($response[1]) || !is_object($response[1])) {
            // there are no other details to send - just fail it
            $session->abort(new \stdClass(), 'thruway.error.authentication_failure');
            return true;
        }

        $details = new \stdClass();
        if (isset($response[1]->details) && is_object($response[1]->details)) {
            $details = $response[1]->details;
        }

        $abortUri = 'thruway.error.authentication_failure';
        if (isset($response[1]->abort_uri) && is_scalar($response[1]->abort_uri)) {
            $abortUri = $response[1]->abort_uri;
        }

        $session->abort($details, $abortUri);
        return true;
    }
}
