<?php

namespace Thruway;

use Thruway\Authentication\AuthenticationDetails;
use Thruway\Manager\ManagerDummy;
use Thruway\Message\AbortMessage;
use Thruway\Message\AuthenticateMessage;
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
     * The metaSession is used as a dummy session to send meta events from
     *
     * @var Session
     */
    private $metaSession;

    /**
     * Constructor
     *
     * @param string $realmName
     */
    function __construct($realmName)
    {
        $this->realmName             = $realmName;
        $this->sessions              = new \SplObjectStorage();
        $this->broker                = new Broker();
        $this->dealer                = new Dealer();
        $this->roles                 = [$this->broker, $this->dealer];
        $this->authenticationManager = null;

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
            $this->manager->info("Received a GoodBye, so shutting the session down");
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
            $this->manager->error("Unhandled message sent to unauthenticated realm: " . $msg->getMsgCode());
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
        $handled = false;
        foreach ($this->roles as $role) {
            if ($role->handlesMessage($msg)) {
                $role->onMessage($session, $msg);
                $handled = true;
                break;
            }
        }

        if (!$handled) {
            $this->manager->warning("Unhandled message sent to \"{$this->getRealmName()}\"");
        }
    }

    /**
     * Process HelloMessage
     *
     * @param \Thruway\Session $session
     * @param \Thruway\Message\HelloMessage $msg
     */
    private function processHello(Session $session, HelloMessage $msg)
    {
        $this->manager->debug("got hello");
        // send welcome message
        if ($this->sessions->contains($session)) {
            $this->manager->error(
                "Connection tried to rejoin realm when it is already joined to the realm."
            );
            // shutdown session here because it is obvious we are just on a different
            // page than the client - maybe we should send abort?
            $session->shutdown();
        } else {
            $this->sessions->attach($session);
            $session->setRealm($this);
            $session->setState(Session::STATE_UP); // this should probably be after authentication

            if ($this->getAuthenticationManager() !== null) {
                try {
                    $this->getAuthenticationManager()->onAuthenticationMessage($this, $session, $msg);
                } catch (\Exception $e) {

                }
            } else {
                $session->setAuthenticated(true);

                $session->setAuthenticationDetails(AuthenticationDetails::createAnonymous());

                // the broker and dealer should give us this information
                $roles = ["broker" => new \stdClass, "dealer" => new \stdClass];
                $session->sendMessage(
                    new WelcomeMessage($session->getSessionId(), ["roles" => $roles])
                );
            }
        }
    }

    /**
     * Process AuthenticateMessage
     *
     * @param Session $session
     * @param AuthenticateMessage $msg
     */
    private function processAuthenticate(Session $session, AuthenticateMessage $msg)
    {
        if ($this->getAuthenticationManager() !== null) {
            try {
                $this->getAuthenticationManager()->onAuthenticationMessage($this, $session, $msg);
            } catch (\Exception $e) {
                $session->abort(new \stdClass(), "thruway.error.internal");
                $this->manager->error("Authenticate sent to realm without auth manager.");
            }
        } else {
            $session->abort(new \stdClass(), "thruway.error.internal");
            $this->manager->error("Authenticate sent to realm without auth manager.");
        }
    }

    /**
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
     * @param string $realmName
     */
    public function setRealmName($realmName)
    {
        $this->realmName = $realmName;
    }

    /**
     * @return mixed
     */
    public function getRealmName()
    {
        return $this->realmName;
    }

    /**
     * @param \Thruway\Session $session
     */
    public function leave(Session $session)
    {

        $this->manager->debug("Leaving realm {$session->getRealm()->getRealmName()}");

        if ($this->getAuthenticationManager() !== null) {
            $this->getAuthenticationManager()->onSessionClose($session);
        }

        foreach ($this->roles as $role) {
            $role->leave($session);
        }
        $this->sessions->detach($session);
    }

    /**
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
     * @return \Thruway\Manager\ManagerInterface
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @param \Thruway\Authentication\AuthenticationManagerInterface $authenticationManager
     */
    public function setAuthenticationManager($authenticationManager)
    {
        $this->authenticationManager = $authenticationManager;
    }

    /**
     * @return \Thruway\Authentication\AuthenticationManagerInterface
     */
    public function getAuthenticationManager()
    {
        return $this->authenticationManager;
    }

    /**
     * @return \SplObjectStorage
     */
    public function getSessions()
    {
        return $this->sessions;
    }

    /**
     * @return \Thruway\Role\Broker
     */
    public function getBroker()
    {
        return $this->broker;
    }

    /**
     * @return \Thruway\Role\Dealer
     */
    public function getDealer()
    {
        return $this->dealer;
    }

    public function publishMeta($topicName, $arguments, $argumentsKw = null, $options = null)
    {
        if ($this->metaSession === null) {
            // setup a new metaSession
            $s = new Session(new DummyTransport());
            $this->metaSession = $s;
        }

        $this->getBroker()->onMessage($this->metaSession,
            new PublishMessage(
                Session::getUniqueId(),
                $options,
                $topicName,
                $arguments,
                $argumentsKw
            ));
    }
}
