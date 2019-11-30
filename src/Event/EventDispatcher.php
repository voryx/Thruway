<?php

namespace Thruway\Event;

use Symfony\Component\EventDispatcher\LegacyEventDispatcherProxy;
use Thruway\Module\RealmModuleInterface;

/**
 * To support both older and newer versions of Symfony, we rely on an "backwardsCompatibleDispatch" method
 * which uses the new parameter order and translates the call.
 * Once support for Symfony < 4.3 is dropped, we can simply use ->dispatch() with the LegacyEventDispatcherProxy
 */
if (class_exists(\Symfony\Component\EventDispatcher\Event::class) && !class_exists(LegacyEventDispatcherProxy::class)) {
    /**
     * Symfony < 4.3, add support for new parameter order without using the LegacyEventDispatcherProxy
     */
    class EventDispatcher extends \Symfony\Component\EventDispatcher\EventDispatcher implements EventDispatcherInterface
    {
        public function addRealmSubscriber(RealmModuleInterface $subscriber)
        {
            $events = $subscriber->getSubscribedRealmEvents();
            foreach ($events as $eventName => $event) {
                $this->addListener($eventName, [$subscriber, $event[0]], $event[1]);
            }
        }

        public function backwardsCompatibleDispatch($event, $eventName = null)
        {
            return $this->dispatch($eventName, $event);
        }
    }
} else {
    /**
     * Symfony >= 4.3, only accept the new parameter order.
     * Both orders can be accepted for ^4.3 by using the LegacyEventDispatcherProxy
     */
    class EventDispatcher extends \Symfony\Component\EventDispatcher\EventDispatcher implements EventDispatcherInterface
    {
        public function addRealmSubscriber(RealmModuleInterface $subscriber)
        {
            $events = $subscriber->getSubscribedRealmEvents();
            foreach ($events as $eventName => $event) {
                $this->addListener($eventName, [$subscriber, $event[0]], $event[1]);
            }
        }

        public function backwardsCompatibleDispatch($event, $eventName = null)
        {
            if (class_exists(\Symfony\Component\EventDispatcher\Event::class)) {
                // Symfony >=4.3 and < 5.0, use the proxy
                return LegacyEventDispatcherProxy::decorate($this)->dispatch($event, $eventName);
            }
            // Symfony >=5.0, just forward the call
            return $this->dispatch($event, $eventName);
        }
    }
}
