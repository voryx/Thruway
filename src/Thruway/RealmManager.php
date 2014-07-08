<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/9/14
 * Time: 11:04 PM
 */

namespace Thruway;


class RealmManager
{
    private $realms;

    private $manager;

    function __construct(ManagerInterface $manager = null)
    {
        $this->realms = array();

        $this->manager = $manager;

    }

    /**
     * @param string
     * @throws \UnexpectedValueException
     * @return Realm
     */
    public function getRealm($realmName)
    {
        if (!static::validRealmName($realmName)) {
            throw new \Exception("Bad realm name");
        }

        if (!array_key_exists($realmName, $this->realms)) {
            $this->manager->logDebug("Creating new realm \"" . $realmName . "\"");
            $this->realms[$realmName] = new Realm($realmName);
            $this->realms[$realmName]->setManager($this->manager);

        }

        return $this->realms[$realmName];
    }

    static public function validRealmName($name)
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
     * @return array
     */
    public function getRealms()
    {
        return $this->realms;
    }


} 