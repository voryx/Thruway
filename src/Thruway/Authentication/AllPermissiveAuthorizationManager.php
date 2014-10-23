<?php


namespace Thruway\Authentication;


use Thruway\Message\ActionMessageInterface;
use Thruway\Realm;
use Thruway\Session;

/**
 * Class AllPermissiveAuthorizationManager
 * @package Thruway\Authentication
 */
class AllPermissiveAuthorizationManager  implements AuthorizationManagerInterface {
    /**
     * Check to see if an action is authorized on a specific uri given the
     * context of the session attempting the action
     *
     * actionMsg should be an instance of: register, call, subscribe, or publish messages
     *
     * @param Session $session
     * @param ActionMessageInterface $actionMsg
     * @return boolean
     */
    public function isAuthorizedTo(Session $session, ActionMessageInterface $actionMsg)
    {
        return true;
    }

    /**
     * Used as a factory to create new authorization managers
     *
     * @param $realmName
     * @param $loop
     * @return mixed
     */
    static public function create($realmName, $loop = null)
    {
        return new AllPermissiveAuthorizationManager();
    }
}