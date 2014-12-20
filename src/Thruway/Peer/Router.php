<?php

namespace Thruway\Peer;

use Thruway\Authentication\AllPermissiveAuthorizationManager;
use Thruway\Authentication\AuthorizationManagerInterface;
use Thruway\Exception\InvalidRealmNameException;
use Thruway\Exception\RealmNotFoundException;
use Thruway\Logging\Logger;
use Thruway\Manager\ManagerDummy;
use Thruway\Manager\ManagerInterface;
use Thruway\Message\AbortMessage;
use Thruway\Message\HelloMessage;
use Thruway\Message\Message;
use Thruway\Module\ModuleInterface;
use Thruway\RealmManager;
use Thruway\Session;
use Thruway\Transport\InternalClientTransportProvider;
use Thruway\Transport\TransportProviderInterface;
use Thruway\Transport\TransportInterface;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;

/**
 * Class Router
 *
 * @package Thruway\Peer
 */
class Router extends AbstractPeer implements RouterInterface
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
     * @var AuthorizationManagerInterface
     */
    private $authorizationManager;

    /**
     * @var \React\EventLoop\LoopInterface
     */
    private $loop;

    /**
     * Constructor
     *
     * @param \React\EventLoop\LoopInterface $loop
     * @param \Thruway\Manager\ManagerInterface $manager
     */
    public function __construct(LoopInterface $loop = null, ManagerInterface $manager = null)
    {
        $this->checkPrecision();

        $this->loop               = $loop ? $loop : Factory::create();
        $this->manager            = $manager ? $manager : new ManagerDummy();
        $this->realmManager       = new RealmManager($this->manager);
        $this->transportProviders = [];
        $this->sessions           = new \SplObjectStorage();

        $this->setAuthorizationManager(new AllPermissiveAuthorizationManager());

        Logger::debug($this, "New router created");
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
        Logger::info($this, "New Session started " . json_encode($transport->getTransportDetails()) . "");

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
     *
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
        Logger::info($this, "Starting router");
        if ($this->loop === null) {
            throw new \Exception("Loop is null");
        }

        if (count($this->transportProviders) == 0) {
            throw new \Exception("No transport providers specified.");
        }

        foreach ($this->transportProviders as $transportProvider) {
            Logger::info($this, "Starting transport provider " . get_class($transportProvider));
            $transportProvider->setManager($this->manager);
            $transportProvider->startTransportProvider($this, $this->loop);
        }

        $this->setupManager();

        Logger::info($this, "Starting loop");
        $this->loop->run();
    }

    /**
     * Handle close transport
     *
     * @param \Thruway\Transport\TransportInterface $transport
     */
    public function onClose(TransportInterface $transport)
    {
        Logger::debug($this, "onClose from " . json_encode($transport->getTransportDetails()));

        /* @var  $session \Thruway\Session */
        $session = $this->sessions[$transport];

        $session->onClose();

        $this->sessions->detach($transport);
    }

    /**
     * Set authentication manager
     *
     * @param \Thruway\Authentication\AuthenticationManagerInterface $authenticationManager
     */
    public function setAuthenticationManager($authenticationManager)
    {
        $this->authenticationManager = $authenticationManager;
        $this->realmManager->setDefaultAuthenticationManager($this->authenticationManager);
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
        $this->getRealmManager()->setDefaultAuthorizationManager($this->getAuthorizationManager());
    }

    /**
     * Set manager
     *
     * @param \Thruway\Manager\ManagerInterface $manager
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
     * Get loop
     *
     * @return \React\EventLoop\LoopInterface
     */
    public function getLoop()
    {
        return $this->loop;
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
     * Set realm manager
     *
     * @param \Thruway\RealmManager $realmManager
     */
    public function setRealmManager($realmManager)
    {
        $this->realmManager = $realmManager;
    }

    /**
     * Get realm manager
     *
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
     * Registers a Module
     *
     * @param ModuleInterface $module
     */
    public function registerModule(ModuleInterface $module)
    {
        $module->setLoop($this->getLoop());
        $module->setRouter($this);
        $this->addInternalClient($module);
        $module->onInitialize();
    }

    /**
     * Register Multiple Modules
     *
     * @param array $modules
     */
    public function registerModules(Array $modules)
    {
        foreach ($modules as $module) {
            $this->registerModule($module);
        }
    }

    /**
     * Add a client that uses the internal transport provider
     *
     * @param ClientInterface $client
     */
    public function addInternalClient(ClientInterface $client)
    {
        $internalTransport = new InternalClientTransportProvider($client);
        $this->addTransportProvider($internalTransport);
    }

}

