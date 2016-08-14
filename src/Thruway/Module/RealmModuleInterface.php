<?php

namespace Thruway\Module;

/**
 * Interface RealmModuleInterface
 * @package Thruway\Module
 */
interface RealmModuleInterface
{
    /** @return array */
    public function getSubscribedRealmEvents();
}
