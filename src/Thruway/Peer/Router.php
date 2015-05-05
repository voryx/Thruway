<?php

namespace Thruway\Peer;

use Thruway\Authentication\AllPermissiveAuthorizationManager;
use Thruway\Authentication\AuthorizationManagerInterface;
use Thruway\Common\Utils;
use Thruway\Event\ConnectionCloseEvent;
use Thruway\Event\EventDispatcher;
use Thruway\Event\EventDispatcherInterface;
use Thruway\Event\EventSubscriberInterface;
use Thruway\Event\ConnectionOpenEvent;
use Thruway\Event\RouterStartEvent;
use Thruway\Event\RouterStopEvent;
use Thruway\Exception\InvalidRealmNameException;
use Thruway\Exception\RealmNotFoundException;
use Thruway\Logging\Logger;
use Thruway\Message\AbortMessage;
use Thruway\Message\HelloMessage;
use Thruway\Message\Message;
use Thruway\Module\RouterModuleInterface;
use Thruway\RealmManager;
use Thruway\Session;
use Thruway\Transport\InternalClientTransportProvider;
use Thruway\Transport\RouterTransportProviderInterface;
use Thruway\Transport\TransportInterface;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;

/**
 * Class Router
 *
 * @package Thruway\Peer
 */
class Router implements RouterInterface, EventSubscriberInterface
{
    /** @var bool */
    protected $started = false;

    /**
     * @var \Thruway\RealmManager
     */
    private $realmManager;

    /**
     * @var array
     */
    private $sessions = [];

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

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var RouterModuleInterface[] */
    private $modules = [];

    /**
     * Constructor
     *
     * @param \React\EventLoop\LoopInterface $loop
     */
    public function __construct(LoopInterface $loop = null)
    {
        Utils::checkPrecision();

        $this->loop            = $loop ? $loop : Factory::create();
        $this->realmManager    = new RealmManager();
        $this->eventDispatcher = new EventDispatcher();
        $this->eventDispatcher->addSubscriber($this);

        $this->registerModule($this->realmManager);

        $this->setAuthorizationManager(new AllPermissiveAuthorizationManager());

        Logger::debug($this, "New router created");
    }

    /**
     * @inheritdoc
     */
    public function createNewSession(TransportInterface $transport)
    {
        $session = new Session($transport);
        $session->setLoop($this->getLoop());

        return $session;
    }

    /**
     * Handle transport received message
     *
     * @param \Thruway\Transport\TransportInterface $transport
     * @param \Thruway\Message\Message $msg
     * @return void
     */
    public function onMessage(TransportInterface $transport, Message $msg)
    {
        return;
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
                $session->setHelloMessage($msg);
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
     * Start router
     *
     * @param bool $runLoop
     * @throws \Exception
     */
    public function start($runLoop = true)
    {
        Logger::info($this, "Starting router");
        if ($this->loop === null) {
            throw new \Exception("Loop is null");
        }

        $this->started = true;

        $this->eventDispatcher->dispatch("router.start", new RouterStartEvent());

        if ($runLoop) {
            Logger::info($this, "Starting loop");
            $this->loop->run();
        }
    }

    /**
     * @inheritdoc
     */
    public function stop($gracefully = true)
    {
        $this->getEventDispatcher()->dispatch('router.stop', new RouterStopEvent());
    }

    /**
     * Handle close transport
     *
     * @param \Thruway\Transport\TransportInterface $transport
     */
    public function onClose(TransportInterface $transport)
    {
        Logger::debug($this, "onClose from ".json_encode($transport->getTransportDetails()));

        /* @var  $session \Thruway\Session */
        $session = $this->sessions[$transport];


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
     * Get loop
     *
     * @return \React\EventLoop\LoopInterface
     */
    public function getLoop()
    {
        return $this->loop;
    }

    /**
     * Get session by session ID
     *
     * @param int $sessionId
     * @return \Thruway\Session|boolean
     */
    public function getSessionBySessionId($sessionId)
    {
        if (!is_scalar($sessionId)) {
            return false;
        }

        return isset($this->sessions[$sessionId]) ? $this->sessions[$sessionId] : false;
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
     * Registers a RouterModule
     *
     * @param RouterModuleInterface $module
     */
    public function registerModule(RouterModuleInterface $module)
    {
        $module->initModule($this, $this->getLoop());
        $this->eventDispatcher->addSubscriber($module);
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
     * Add a transport provider
     *
     * @param RouterTransportProviderInterface $transportProvider
     */
    public function addTransportProvider(RouterTransportProviderInterface $transportProvider)
    {
        $this->registerModule($transportProvider);
    }

    /**
     * Add a client that uses the internal transport provider
     *
     * @param ClientInterface $client
     */
    public function addInternalClient(ClientInterface $client)
    {
        $internalTransport = new InternalClientTransportProvider($client);
        $this->registerModule($internalTransport);
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * @param \Thruway\Event\ConnectionOpenEvent $event
     */
    public function handleConnectionOpen(ConnectionOpenEvent $event)
    {
        $this->sessions[$event->session->getSessionId()] = $event->session;
    }

    /**
     * @param \Thruway\Event\ConnectionCloseEvent $event
     */
    public function handleConnectionClose(ConnectionCloseEvent $event)
    {
        unset($this->sessions[$event->session->getSessionId()]);
        // TODO: should this be a message dispatched from the Transport?
        $event->session->onClose();
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
          "connection_open"  => ['handleConnectionOpen', 10],
          "connection_close" => ['handleConnectionClose', 10]
        ];
    }
}

