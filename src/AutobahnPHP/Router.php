<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/7/14
 * Time: 11:58 AM
 */

namespace AutobahnPHP;

use AutobahnPHP\Message\HelloMessage;
use AutobahnPHP\Message\Message;

class Router extends AbstractPeer
{

    /**
     * @var RealmManager
     */
    private $realmManager;

    private $authenticationProvider;

    function __construct()
    {
        $this->realmManager = new RealmManager();
    }


    public function onMessage(Session $session, Message $msg)
    {
        // see if the session is in a realm
        if ($session->getRealm() === null) {
            // hopefully this is a HelloMessage or we have no place for this message to go
            if ($msg instanceof HelloMessage) {
                if (RealmManager::validRealmName($msg->getRealm())) {
                    $session->setAuthenticationProvider($this->authenticationProvider);
                    $realm = $this->realmManager->getRealm($msg->getRealm());
                    $realm->onMessage($session, $msg);
                } else {
                    // TODO send bad realm error back and shutdown
                    $session->shutdown();
                }
            } else {
                $session->shutdown();
            }
        } else {
            $realm = $session->getRealm();

            $realm->onMessage($session, $msg);
        }
    }

    /**
     * @return mixed
     */
    public function getAuthenticationProvider()
    {
        return $this->authenticationProvider;
    }

    /**
     * @param mixed $authenticationProvider
     */
    public function setAuthenticationProvider($authenticationProvider)
    {
        $this->authenticationProvider = $authenticationProvider;
    }


}