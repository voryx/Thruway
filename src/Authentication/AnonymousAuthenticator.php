<?php

namespace Thruway\Authentication;

use Thruway\Event\MessageEvent;
use Thruway\Message\HelloMessage;
use Thruway\Message\WelcomeMessage;
use Thruway\Module\RealmModuleInterface;

/**
 * Class AnonymousAuthenticator
 * @package Thruway\Authentication
 */
class AnonymousAuthenticator implements RealmModuleInterface

{
    /**
     * Listen for Realm events
     * @return array
     */
    public function getSubscribedRealmEvents()
    {
        return ['HelloMessageEvent' => ['handleHelloMessageEvent', 0]];
    }

    /**
     * @param \Thruway\Event\MessageEvent $event
     */
    public function handleHelloMessageEvent(MessageEvent $event)
    {

        $session = $event->session;

        /** @var HelloMessage $msg */
        $msg = $event->message;

        if ($session->isAuthenticated()) {
            return;
        }

        $session->setAuthenticated(true);

        // still set admin on trusted transports
        $authDetails = AuthenticationDetails::createAnonymous();
        if ($session->getTransport() !== null && $session->getTransport()->isTrusted()) {
            $authDetails->addAuthRole('admin');
        }
        $session->setAuthenticationDetails($authDetails);

        $session->sendMessage(
          new WelcomeMessage($session->getSessionId(), $msg->getDetails())
        );
    }
}
