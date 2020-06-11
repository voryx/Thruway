<?php

namespace Thruway\Tests\Unit;

class RealmManagerTest extends \Thruway\Tests\TestCase {

    public function testRealmNotFound() {
        $this->expectException('\Thruway\Exception\RealmNotFoundException');
        $realmManager = new \Thruway\RealmManager();

        $realmManager->setAllowRealmAutocreate(false);
        $realmManager->getRealm("some_realm");
    }

    /**
     * @throws \Exception
     * @throws \Thruway\Exception\InvalidRealmNameException
     *
     */
    public function testAddRealmWithSameNameAsExisting() {
        $this->expectException('\Exception');
        $realmManager = new \Thruway\RealmManager();
        $realmManager->initModule(new \Thruway\Peer\Router(), \React\EventLoop\Factory::create());

        $realm1 = new \Thruway\Realm("test_realm");
        $realm2 = new \Thruway\Realm("test_realm");

        $realmManager->addRealm($realm1);

        $this->assertEquals(1, count($realmManager->getRealms()));

        $realmManager->addRealm($realm2);
    }

    public function testWAMP1ValidRealmName() {
        $this->assertFalse(\Thruway\RealmManager::validRealmName("WAMP1"));
    }
} 