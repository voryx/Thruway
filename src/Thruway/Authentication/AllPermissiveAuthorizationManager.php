<?php


namespace Thruway\Authentication;


use Thruway\Realm;

/**
 * Class AllPermissiveAuthorizationManager
 * @package Thruway\Authentication
 */
class AllPermissiveAuthorizationManager  implements AuthorizationManagerInterface {
    /**
     * Check to see if an action is authorized on a specific uri given the
     * context of the Realm and AuthenticationDetails of the session
     * attempting the action
     *
     * action should be one of: ['register', 'call', 'subscribe', 'publish']
     *
     * @param $action
     * @param $uri
     * @param Realm $realm
     * @param AuthenticationDetails $authenticationDetails
     * @return boolean
     */
    public function isAuthorizedTo($action, $uri, Realm $realm, AuthenticationDetails $authenticationDetails)
    {
        return true;
    }

} 