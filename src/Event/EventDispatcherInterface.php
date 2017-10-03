<?php

namespace Thruway\Event;

use Thruway\Module\RealmModuleInterface;

interface EventDispatcherInterface extends \Symfony\Component\EventDispatcher\EventDispatcherInterface
{
    public function addRealmSubscriber(RealmModuleInterface $subscriber);
}
