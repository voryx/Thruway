<?php

namespace Thruway\Event;

use Thruway\Module\RealmModuleInterface;

class EventDispatcher extends \Symfony\Component\EventDispatcher\EventDispatcher implements EventDispatcherInterface
{
    public function addRealmSubscriber(RealmModuleInterface $subscriber)
    {
        $events = $subscriber->getSubscribedRealmEvents();
        foreach ($events as $eventName => $event) {
            $this->addListener($eventName, [$subscriber, $event[0]], $event[1]);
        }
    }
}
