<?php

namespace Thruway;


use Thruway\Authentication\AllPermissiveAuthorizationManager;
use Thruway\Authentication\AuthorizationManagerInterface;
use Thruway\Exception\InvalidRealmNameException;
use Thruway\Exception\RealmNotFoundException;
use Thruway\Logging\Logger;
use Thruway\Manager\ManagerDummy;
use Thruway\Manager\ManagerInterface;

/**
 * Class Realm Manager
 *
 * @package Thruway
 */
class RealmManager
{

    /**
     * @var array
     */
    private $realms;

    /**
     * @var \Thruway\Manager\ManagerInterface
     */
    private $manager;

    /**
     * @var boolean
     */
    private $allowRealmAutocreate;

    /**
     * @var \Thruway\Authentication\AuthenticationManagerInterface
     */
    private $defaultAuthenticationManager;

    /**
     * @var AuthorizationManagerInterface
     */
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
                Logger::debug($this, "Creating new realm \"" . $realmName . "\"");
                $realm = new Realm($realmName);
                $realm->setAuthenticationManager($this->getDefaultAuthenticationManager());
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
            throw new \Exception("There is already a realm \"" . $realm->getRealmName() . "\"");
        }

        Logger::debug($this, "Adding realm \"" . $realmName . "\"");

        if ($realm->getManager() instanceof ManagerDummy) {
            /** remind people that we don't setup the manager for them if they
             * are creating their own realms */
            Logger::info($this, "Realm \"" . $realmName . "\" is using ManagerDummy");
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
     * Set default authentication manager
     *
     * @param \Thruway\Authentication\AuthenticationManagerInterface $defaultAuthenticationManager
     */
    public function setDefaultAuthenticationManager($defaultAuthenticationManager)
    {
        $this->defaultAuthenticationManager = $defaultAuthenticationManager;
    }

    /**
     * Get default authentication manager
     *
     * @return \Thruway\Authentication\AuthenticationManagerInterface
     */
    public function getDefaultAuthenticationManager()
    {
        return $this->defaultAuthenticationManager;
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
