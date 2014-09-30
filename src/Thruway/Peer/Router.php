<?php

namespace Thruway\Peer;

use Thruway\Authentication\AuthenticationManagerInterface;
use Thruway\Exception\InvalidRealmNameException;
use Thruway\Exception\RealmNotFoundException;
use Thruway\Manager\ManagerDummy;
use Thruway\Manager\ManagerInterface;
use Thruway\Message\AbortMessage;
use Thruway\Message\HelloMessage;
use Thruway\Message\Message;
use Thruway\RealmManager;
use Thruway\Session;
use Thruway\Transport\TransportProviderInterface;
use Thruway\Transport\TransportInterface;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;

/**
 * Class Router
 *
 * @package Thruway\Peer
 */
class Router extends AbstractPeer
{

    /**
     * @var \Thruway\Transport\TransportProviderInterface[]
     */
    private $transportProviders;

    /**
     * @var \Thruway\RealmManager
     */
    private $realmManager;

    /**
     * @var \SplObjectStorage
     */
    private $sessions;

    /**
     * @var \Thruway\Authentication\AuthenticationManagerInterface
     */
    private $authenticationManager;

    /**
     * Constructor
     *
     * @param \React\EventLoop\LoopInterface $loop
     * @param \Thruway\Manager\ManagerInterface $manager
     */
    function __construct(LoopInterface $loop = null, ManagerInterface $manager = null)
    {
        // initially we are just going to start with a dummy manager
        $this->manager            = $manager ? $manager : new ManagerDummy();
        $this->realmManager       = new RealmManager($this->manager);
        $this->transportProviders = [];
        $this->sessions           = new \SplObjectStorage();

        $this->manager->debug("New router created");

        if ($loop === null) {
            $this->manager->debug("No loop given, creating our own instance");
            $loop = Factory::create();
        }

        $this->loop = $loop;

    }

    /**
     * Handle open transport
     *
     * @param \Thruway\Transport\TransportInterface $transport
     */
    public function onOpen(TransportInterface $transport)
    {
        $session = new Session($transport, $this->manager);

        // give the session the loop, just in case it wants to set a timer or something
        $session->setLoop($this->getLoop());

        // TODO: add a little more detail to this (what kind and address maybe?)
        $this->manager->info("New Session started " . json_encode($transport->getTransportDetails()) . "");

        $this->sessions->attach($transport, $session);

    }

    /**
     * Handle transport recived message
     *
     * @param \Thruway\Transport\TransportInterface $transport
     * @param \Thruway\Message\Message $msg
     * @return void
     */
    public function onMessage(TransportInterface $transport, Message $msg)
    {
        /* @var $session \Thruway\Session */
        $session = $this->sessions[$transport];

        // see if the session is in a realm
        if ($session->getRealm() === null) {
            if ($msg instanceof AbortMessage) {
                $session->shutdown();
                return;
            }
            // hopefully this is a HelloMessage or we have no place for this message to go
            if ($msg instanceof HelloMessage) {
                try {
                    $realm = $this->realmManager->getRealm($msg->getRealm());

                    $realm->onMessage($session, $msg);
                } catch (\Exception $e) {
                    // TODO: Test this
                    $errorUri    = "wamp.error.unknown";
                    $description = $e->getMessage();
                    if ($e instanceof InvalidRealmNameException || $e instanceof RealmNotFoundException) {
                        $errorUri = "wamp.error.no_such_realm";
                    }
                    $session->abort(['description' => $description], $errorUri);
                }
            } else {
                $session->abort(new \stdClass(), "wamp.error.unknown");
            }
        } else {
            $realm = $session->getRealm();

            $realm->onMessage($session, $msg);
        }
    }

    /**
     * Add transport provider
     * @param \Thruway\Transport\TransportProviderInterface $transportProvider
     */
    public function addTransportProvider(TransportProviderInterface $transportProvider)
    {
        array_push($this->transportProviders, $transportProvider);
    }

    /**
     * Start router
     *
     * @throws \Exception
     */
    public function start()
    {
        $this->manager->debug("Starting router");
        if ($this->loop === null) {
            throw new \Exception("Loop is null");
        }

        if (count($this->transportProviders) == 0) {
            throw new \Exception("No transport providers specified.");
        }

        foreach ($this->transportProviders as $transportProvider) {
            $this->manager->debug("Starting transport provider " . get_class($transportProvider));
            $transportProvider->startTransportProvider($this, $this->loop);
        }

        $this->setupManager();

        $this->manager->debug("Starting loop");
        $this->loop->run();
    }

    /**
     * Handle close transport
     *
     * @param \Thruway\Transport\TransportInterface $transport
     */
    public function onClose(TransportInterface $transport)
    {
        $this->manager->debug("onClose from " . json_encode($transport->getTransportDetails()));

        /** @var  $session Session */
        $session = $this->sessions[$transport];

        $session->onClose();

        $this->sessions->detach($transport);
    }

    /**
     * @param AuthenticationManagerInterface $authenticationManager
     */
    public function setAuthenticationManager($authenticationManager)
    {
        $this->authenticationManager = $authenticationManager;
        $this->realmManager->setDefaultAuthenticationManager($this->authenticationManager);
    }

    /**
     * @return AuthenticationManagerInterface
     */
    public function getAuthenticationManager()
    {
        return $this->authenticationManager;
    }


    /**
     * @param ManagerInterface $manager
     * @throws \Exception
     */
    public function setManager($manager)
    {
//        $this->manager = $manager;
        throw new \Exception('Manager needs to be set in the constructor');
    }

    /**
     * Setting up manger
     */
    public function setupManager()
    {
        // setup the config for the manager
        $this->manager->addCallable("sessions.count", [$this, "managerGetSessionCount"]);
        //$this->manager->addCallable("sessions.list", array($this, "managerGetSessionList"));
        $this->manager->addCallable("sessions.get", [$this, "managerGetSessions"]);
        $this->manager->addCallable("realms.get", [$this, "managerGetRealms"]);
    }

    /**
     * @return \React\EventLoop\LoopInterface
     */
    public function getLoop()
    {
        return $this->loop;
    }

    /**
     * @return ManagerInterface
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * Get session by session ID
     *
     * @param int $sessionId
     * @return \Thruway\Session|boolean
     */
    public function getSessionBySessionId($sessionId)
    {
        /** @var Session $session */
        $this->sessions->rewind();
        while ($this->sessions->valid()) {
            $session = $this->sessions->getInfo();
            if ($session->getSessionId() == $sessionId) {
                return $session;
            }
            $this->sessions->next();
        }
        return false;
    }

    /**
     * @param \Thruway\RealmManager $realmManager
     */
    public function setRealmManager($realmManager)
    {
        $this->realmManager = $realmManager;
    }

    /**
     * @return \Thruway\RealmManager
     */
    public function getRealmManager()
    {
        return $this->realmManager;
    }


    /**
     * Count number sessions
     *
     * @return array
     */
    public function managerGetSessionCount()
    {
        return [count($this->sessions)];
    }

    /**
     * Get list sessions
     *
     * @return array
     */
    public function managerGetSessions()
    {
        $theSessions = [];

        foreach ($this->sessions as $key) {
            /* @var $session \Thruway\Session */
            $session = $this->sessions[$key];

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

        return [$theSessions];
    }

    /**
     * Get list realms
     *
     * @return array
     */
    public function managerGetRealms()
    {
        $theRealms = [];

        foreach ($this->realmManager->getRealms() as $realm) {
            /* @var $realm \Thruway\Realm */
            $theRealms[] = [
                "name" => $realm->getRealmName()
            ];
        }

        return [$theRealms];
    }

    /**
     * Get list transports
     *
     * @return array
     */
    public function managerGetTransports()
    {

    }

    /**
     *
     * @param array $args
     */
    public function managerPruneSession($args)
    {

    }

}
