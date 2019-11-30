<?php

namespace Thruway\Event;

use Thruway\Module\RealmModuleInterface;

if (interface_exists(\Symfony\Contracts\EventDispatcher\EventDispatcherInterface::class)) {
    interface EventDispatcherInterface extends \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
    {
        public function addRealmSubscriber(RealmModuleInterface $subscriber);

        public function backwardsCompatibleDispatch($event, $eventName = null);
    }
} else {
    interface EventDispatcherInterface extends \Symfony\Component\EventDispatcher\EventDispatcherInterface
    {
        public function addRealmSubscriber(RealmModuleInterface $subscriber);

        public function backwardsCompatibleDispatch($event, $eventName = null);
    }
}
