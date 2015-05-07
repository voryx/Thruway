<?php


class RealmManagerTest extends PHPUnit_Framework_TestCase {

    /**
     * @expectedException \Thruway\Exception\RealmNotFoundException
     */
    public function testRealmNotFound() {
        $realmManager = new \Thruway\RealmManager();

        $realmManager->setAllowRealmAutocreate(false);
        $realmManager->getRealm("some_realm");
    }

    /**
     * @throws Exception
     * @throws \Thruway\Exception\InvalidRealmNameException
     *
     * @expectedException \Exception
     */
    public function testAddRealmWithSameNameAsExisting() {
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