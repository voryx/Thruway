<?php

namespace Thruway;

use Thruway\Authentication\AllPermissiveAuthorizationManager;
use Thruway\Authentication\AuthenticationDetails;
use Thruway\Authentication\AuthorizationManagerInterface;
use Thruway\Common\Utils;
use Thruway\Exception\InvalidRealmNameException;
use Thruway\Logging\Logger;
use Thruway\Manager\ManagerDummy;
use Thruway\Message\AbortMessage;
use Thruway\Message\ActionMessageInterface;
use Thruway\Message\AuthenticateMessage;
use Thruway\Message\ErrorMessage;
use Thruway\Message\GoodbyeMessage;
use Thruway\Message\HelloMessage;
use Thruway\Message\Message;
use Thruway\Message\PublishMessage;
use Thruway\Message\WelcomeMessage;
use Thruway\Role\Broker;
use Thruway\Role\Dealer;
use Thruway\Transport\DummyTransport;

/**
 * Class Realm
 *
 * @package Thruway
 */
class Realm
{

    /**
     * @var string
     */
    private $realmName;

    /**
     * @var \SplObjectStorage
     */
    private $sessions;

    /**
     * @var \Thruway\Role\AbstractRole[]
     */
    private $roles;

    /**
     * @var \Thruway\Manager\ManagerInterface
     */
    private $manager;

    /**
     * @var \Thruway\Role\Broker
     */
    private $broker;

    /**
     * @var \Thruway\Role\Dealer
     */
    private $dealer;

    /**
     * @var \Thruway\Authentication\AuthenticationManagerInterface
     */
    private $authenticationManager;

    /**
     * @var AuthorizationManagerInterface
     */
    private $authorizationManager;

    /**
     * The metaSession is used as a dummy session to send meta events from
     *
     * @var \Thruway\Session
     */
    private $metaSession;

    /**
     * Constructor
     *
     * @param string $realmName
     */
    public function __construct($realmName)
    {
        $this->realmName             = $realmName;
        $this->sessions              = new \SplObjectStorage();
        $this->broker                = new Broker();
        $this->dealer                = new Dealer();
        $this->roles                 = [$this->broker, $this->dealer];
        $this->authenticationManager = null;

        $this->setAuthorizationManager(new AllPermissiveAuthorizationManager());
        $this->setManager(new ManagerDummy());
    }

    /**
     * Handle process received message
     *
     * @param \Thruway\Session $session
     * @param \Thruway\Message\Message $msg
     */
    public function onMessage(Session $session, Message $msg)
    {

        if ($msg instanceof GoodByeMessage):
            Logger::info($this, "Received a GoodBye, so shutting the session down");
            $session->sendMessage(new GoodbyeMessage(new \stdClass(), "wamp.error.goodbye_and_out"));
            $session->shutdown();
        elseif ($session->isAuthenticated()):
            $this->processAuthenticated($session, $msg);
        elseif ($msg instanceof AbortMessage):
            $this->processAbort($session, $msg);
        elseif ($msg instanceof HelloMessage):
            $this->processHello($session, $msg);
        elseif ($msg instanceof AuthenticateMessage):
            $this->processAuthenticate($session, $msg);
        else:
            Logger::error($this, "Unhandled message sent to unauthenticated realm: " . $msg->getMsgCode());
            $session->abort(new \stdClass(), "wamp.error.not_authorized");
        endif;
    }

    /**
     * Process AbortMessage
     *
     * @param \Thruway\Session $session
     * @param \Thruway\Message\Message $msg
     */
    private function processAbort(Session $session, Message $msg)
    {
        $session->shutdown();
    }

    /**
     * Process All Messages if the session has been authenticated
     *
     * @param \Thruway\Session $session
     * @param \Thruway\Message\Message $msg
     */
    private function processAuthenticated(Session $session, Message $msg)
    {
        // authorization stuff here
        if ($msg instanceof ActionMessageInterface) {
            if (!$this->getAuthorizationManager()->isAuthorizedTo($session, $msg)) {
                Logger::alert($this,
                    "Permission denied: " . $msg->getActionName() . " " . $msg->getUri() . " for " . $session->getAuthenticationDetails()->getAuthId());

                // we are not to send messages in response to publish messages unless
                // they set acknowledge = true
                if ($msg instanceof PublishMessage) {
                    if (!$msg->acknowledge()) return;
                }

                $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg, "wamp.error.not_authorized"));

                return;
            }
        }

        $handled = false;
        foreach ($this->roles as $role) {
            if ($role->handlesMessage($msg)) {
                $role->onMessage($session, $msg);
                $handled = true;
                break;
            }
        }

        if (!$handled) {
            Logger::warning($this, "Unhandled message sent to \"{$this->getRealmName()}\"");
        }
    }

    /**
     * Process HelloMessage
     *
     * @param \Thruway\Session $session
     * @param \Thruway\Message\HelloMessage $msg
     * @throws InvalidRealmNameException
     */
    private function processHello(Session $session, HelloMessage $msg)
    {
        if ($this->getRealmName() != $msg->getRealm()) {
            throw new InvalidRealmNameException();
        }
        Logger::debug($this, "Got Hello");
        // send welcome message
        if ($this->sessions->contains($session)) {
            Logger::error($this,
                "Connection tried to rejoin realm when it is already joined to the realm."
            );
            // shutdown session here because it is obvious we are just on a different
            // page than the client - maybe we should send abort?
            $session->shutdown();
        } else {
            $this->sessions->attach($session);
            $session->setRealm($this);

            $details = $msg->getDetails();

            if (is_object($details) && isset($details->roles) && is_object($details->roles)) {
                $session->setRoleFeatures($details->roles);
            }

            $session->setState(Session::STATE_UP); // this should probably be after authentication

            if ($this->getAuthenticationManager() !== null) {
                try {
                    $this->getAuthenticationManager()->onAuthenticationMessage($this, $session, $msg);
                } catch (\Exception $e) {

                }
            } else {
                $session->setAuthenticated(true);

                // still set admin on trusted transports
                $authDetails = AuthenticationDetails::createAnonymous();
                if ($session->getTransport() !== null && $session->getTransport()->isTrusted()) {
                    $authDetails->addAuthRole('admin');
                }
                $session->setAuthenticationDetails($authDetails);

                // the broker and dealer should give us this information
                $details = new \stdClass();
                $this->addRolesToDetails($details);
                $session->sendMessage(
                    new WelcomeMessage($session->getSessionId(), $details)
                );
            }
        }
    }

    /**
     * Process AuthenticateMessage
     *
     * @param \Thruway\Session $session
     * @param \Thruway\Message\AuthenticateMessage $msg
     */
    private function processAuthenticate(Session $session, AuthenticateMessage $msg)
    {
        if ($this->getAuthenticationManager() !== null) {
            try {
                $this->getAuthenticationManager()->onAuthenticationMessage($this, $session, $msg);
            } catch (\Exception $e) {
                $session->abort(new \stdClass(), "thruway.error.internal");
                Logger::error($this, "Authenticate sent to realm without auth manager.");
            }
        } else {
            $session->abort(new \stdClass(), "thruway.error.internal");
            Logger::error($this, "Authenticate sent to realm without auth manager.");
        }
    }

    /**
     * Get list sessions
     *
     * @return array
     */
    public function managerGetSessions()
    {
        $theSessions = [];

        /* @var $session \Thruway\Session */
        foreach ($this->sessions as $session) {

            $sessionRealm = null;
            // just in case the session is not in a realm yet
            if ($session->getRealm() !== null) {
                $sessionRealm = $session->getRealm()->getRealmName();
            }

            if ($session->getAuthenticationDetails() !== null) {
                $authDetails = $session->getAuthenticationDetails();
                $auth        = [
                    "authid"     => $authDetails->getAuthId(),
                    "authmethod" => $authDetails->getAuthMethod()
                ];
            } else {
                $auth = new \stdClass();
            }

            $theSessions[] = [
                "id"           => $session->getSessionId(),
                "transport"    => $session->getTransport()->getTransportDetails(),
                "messagesSent" => $session->getMessagesSent(),
                "sessionStart" => $session->getSessionStart(),
                "realm"        => $sessionRealm,
                "auth"         => $auth
            ];
        }

        return $theSessions;
    }

    /**
     * Get realm name
     *
     * @return mixed
     */
    public function getRealmName()
    {
        return $this->realmName;
    }

    /**
     * Process on session leave
     *
     * @param \Thruway\Session $session
     */
    public function leave(Session $session)
    {

        Logger::debug($this, "Leaving realm {$session->getRealm()->getRealmName()}");

        if ($this->getAuthenticationManager() !== null) {
            $this->getAuthenticationManager()->onSessionClose($session);
        }

        foreach ($this->roles as $role) {
            $role->leave($session);
        }
        $this->sessions->detach($session);
    }

    /**
     * Set manager
     *
     * @param \Thruway\Manager\ManagerInterface $manager
     */
    public function setManager($manager)
    {
        $this->manager = $manager;

        $this->broker->setManager($manager);
        $this->dealer->setManager($manager);

        $manager->addCallable(
            "realm.{$this->getRealmName()}.registrations", function () {
            return $this->dealer->managerGetRegistrations();
        }
        );
    }

    /**
     * Get manager
     *
     * @return \Thruway\Manager\ManagerInterface
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * Set authentication manager
     *
     * @param \Thruway\Authentication\AuthenticationManagerInterface $authenticationManager
     */
    public function setAuthenticationManager($authenticationManager)
    {
        $this->authenticationManager = $authenticationManager;
    }

    /**
     * Get authentication manager
     *
     * @return \Thruway\Authentication\AuthenticationManagerInterface
     */
    public function getAuthenticationManager()
    {
        return $this->authenticationManager;
    }

    /**
     * @return AuthorizationManagerInterface
     */
    public function getAuthorizationManager()
    {
        return $this->authorizationManager;
    }

    /**
     * @param AuthorizationManagerInterface $authorizationManager
     */
    public function setAuthorizationManager($authorizationManager)
    {
        $this->authorizationManager = $authorizationManager;
    }

    /**
     * Get list session
     *
     * @return \SplObjectStorage
     */
    public function getSessions()
    {
        return $this->sessions;
    }

    /**
     * @param $details
     * @return \stdClass
     */
    public function addRolesToDetails($details)
    {
        // if details is null - we will create it
        if ($details === null) {
            $details = new \stdClass();
        }

        // if details is not an object - we pass it through
        if (is_object($details)) {
            $details->roles = (object)[
                "broker" => (object)["features" => $this->getBroker()->getFeatures()],
                "dealer" => (object)["features" => $this->getDealer()->getFeatures()]
            ];
        } else {
            Logger::warning($this, "non-object sent to addRolesToDetails - returning as is");
        }

        return $details;
    }

    /**
     * Get broker
     *
     * @return \Thruway\Role\Broker
     */
    public function getBroker()
    {
        return $this->broker;
    }

    /**
     * Get dealer
     *
     * @return \Thruway\Role\Dealer
     */
    public function getDealer()
    {
        return $this->dealer;
    }

    /**
     * Publish meta
     *
     * @param string $topicName
     * @param mixed $arguments
     * @param mixed $argumentsKw
     * @param mixed $options
     */
    public function publishMeta($topicName, $arguments, $argumentsKw = null, $options = null)
    {
        if ($this->metaSession === null) {
            // setup a new metaSession
            $s                 = new Session(new DummyTransport());
            $this->metaSession = $s;
        }

        $this->getBroker()->onMessage($this->metaSession,
            new PublishMessage(
                Utils::getUniqueId(),
                $options,
                $topicName,
                $arguments,
                $argumentsKw
            ));
    }
}
