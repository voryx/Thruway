<?php

namespace Thruway;


use Thruway\Authentication\AllPermissiveAuthorizationManager;
use Thruway\Authentication\AuthorizationManagerInterface;
use Thruway\Event\ConnectionCloseEvent;
use Thruway\Event\ConnectionOpenEvent;
use Thruway\Event\MessageEvent;
use Thruway\Exception\InvalidRealmNameException;
use Thruway\Exception\RealmNotFoundException;
use Thruway\Logging\Logger;
use Thruway\Manager\ManagerDummy;
use Thruway\Manager\ManagerInterface;
use Thruway\Message\HelloMessage;
use Thruway\Module\RealmModuleInterface;

/**
 * Class Realm Manager
 *
 * @package Thruway
 */
class RealmManager extends Module\RouterModule implements RealmModuleInterface
{

    /** @var array */
    private $realms;

    /** @var \Thruway\Manager\ManagerInterface */
    private $manager;

    /** @var boolean */
    private $allowRealmAutocreate;

    /** @var \Thruway\Authentication\AuthenticationManagerInterface */
    private $defaultAuthenticationManager;

    /** @var AuthorizationManagerInterface */
    private $defaultAuthorizationManager;

    /**
     * Constructor
     *
     * @param \Thruway\Manager\ManagerInterface $manager
     */
    public function __construct(ManagerInterface $manager = null)
    {
        $this->realms                      = [];
        $this->manager                     = $manager ?: new ManagerDummy();
        $this->allowRealmAutocreate        = true;
        $this->defaultAuthorizationManager = new AllPermissiveAuthorizationManager();

    }

    /**
     * Events on the Router's dispatcher
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
          "connection_open"  => ['handleConnectionOpen', 10],
          "connection_close" => ['handleConnectionClose', 10]
        ];
    }

    /**
     * Events on the Session's dispatcher
     *
     * @return array
     */
    public function getSubscribedRealmEvents()
    {
        return [
          "PreHelloMessageEvent" => ["handlePreHelloMessage", 10]
        ];
    }

    /**
     * @param \Thruway\Event\ConnectionOpenEvent $event
     */
    public function handleConnectionOpen(ConnectionOpenEvent $event)
    {
        $event->session->dispatcher->addRealmSubscriber($this);
    }

    /**
     * @param \Thruway\Event\ConnectionCloseEvent $event
     */
    public function handleConnectionClose(ConnectionCloseEvent $event)
    {

    }

    /**
     * @param \Thruway\Event\MessageEvent $event
     * @throws \Exception
     */
    public function handlePreHelloMessage(MessageEvent $event)
    {
        Logger::info($this, "Got prehello...");
        /** @var HelloMessage $msg */
        $msg     = $event->message;
        $session = $event->session;

        $session->setHelloMessage($msg);
        try {
            $realm = $this->getRealm($msg->getRealm());

            $realm->addSession($session);
        } catch (\Exception $e) {
            // TODO: Test this
            $errorUri    = "wamp.error.unknown";
            $description = $e->getMessage();
            if ($e instanceof InvalidRealmNameException || $e instanceof RealmNotFoundException) {
                $errorUri = "wamp.error.no_such_realm";
            }
            $session->abort(['description' => $description], $errorUri);
        }
    }


    /**
     * Get Realm by realm name
     *
     * @param string $realmName
     * @throws \Thruway\Exception\InvalidRealmNameException
     * @throws \Thruway\Exception\RealmNotFoundException
     * @return \Thruway\Realm
     */
    public function getRealm($realmName)
    {
        if (!array_key_exists($realmName, $this->realms)) {
            if ($this->getAllowRealmAutocreate()) {
                Logger::debug($this, "Creating new realm \"".$realmName."\"");
                $realm = new Realm($realmName);
                $realm->setAuthorizationManager($this->getDefaultAuthorizationManager());
                $realm->setManager($this->manager);

                $this->addRealm($realm);
            } else {
                throw new RealmNotFoundException();
            }
        }

        return $this->realms[$realmName];
    }

    /**
     * Add new realm
     *
     * @param \Thruway\Realm $realm
     * @throws \Thruway\Exception\InvalidRealmNameException
     * @throws \Exception
     */
    public function addRealm(Realm $realm)
    {
        $realmName = $realm->getRealmName();

        if (!static::validRealmName($realm->getRealmName())) {
            throw new InvalidRealmNameException;
        }

        if (array_key_exists($realm->getRealmName(), $this->realms)) {
            throw new \Exception("There is already a realm \"".$realm->getRealmName()."\"");
        }

        Logger::debug($this, "Adding realm \"".$realmName."\"");

        if ($realm->getManager() instanceof ManagerDummy) {
            /** remind people that we don't setup the manager for them if they
             * are creating their own realms */
            Logger::info($this, "Realm \"".$realmName."\" is using ManagerDummy");
        }

        $this->realms[$realm->getRealmName()] = $realm;
    }

    /**
     * Validate realm name
     *
     * @param string $name
     * @return boolean
     */
    public static function validRealmName($name)
    {
        // check to see if this is a valid name
        // TODO maybe use similar checks to Autobahn|Py
        if (strlen($name) < 1) {
            return false;
        }
        //throw new \UnexpectedValueException("Realm name too short: " . $realmName);
        if ($name == "WAMP1") {
            return false;
        }

        //throw new \UnexpectedValueException("Realm name \"WAMP1\" is reserved.");

        return true;
    }

    /**
     * Get list realms
     *
     * @return array
     */
    public function getRealms()
    {
        return $this->realms;
    }

    /**
     * Set option allow auto create realm if not exist
     *
     * @param boolean $allowRealmAutocreate
     */
    public function setAllowRealmAutocreate($allowRealmAutocreate)
    {
        $this->allowRealmAutocreate = $allowRealmAutocreate;
    }

    /**
     * Get option allow auto create realm
     *
     * @return boolean
     */
    public function getAllowRealmAutocreate()
    {
        return $this->allowRealmAutocreate;
    }

    /**
     * @return AuthorizationManagerInterface
     */
    public function getDefaultAuthorizationManager()
    {
        return $this->defaultAuthorizationManager;
    }

    /**
     * @param AuthorizationManagerInterface $defaultAuthorizationManager
     */
    public function setDefaultAuthorizationManager($defaultAuthorizationManager)
    {
        $this->defaultAuthorizationManager = $defaultAuthorizationManager;
    }

}
