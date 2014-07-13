<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/7/14
 * Time: 11:58 AM
 */

namespace Thruway\Peer;

use Thruway\ManagerDummy;
use Thruway\ManagerInterface;
use Thruway\Message\GoodbyeMessage;
use Thruway\Message\HelloMessage;
use Thruway\Message\Message;
use Thruway\Realm;
use Thruway\RealmManager;
use Thruway\Session;
use Thruway\Transport\AbstractTransportProvider;
use Thruway\Transport\TransportInterface;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;

/**
 * Class Router
 * @package Thruway\Peer
 */
class Router extends AbstractPeer
{

    private $transportProviders;

    /**
     * @var RealmManager
     */
    private $realmManager;

    /**
     * @var
     */
    private $authenticationProvider;

    /**
     * @var \SplObjectStorage
     */
    private $sessions;

    /**
     *
     */
    function __construct(LoopInterface $loop = null, ManagerInterface $manager = null)
    {
        // initially we are just going to start with a dummy manager
        if ($manager === null) {
            $manager = new ManagerDummy();
        }
        $this->manager = $manager;

        $manager->logDebug("New router created");

        $this->realmManager = new RealmManager($manager);
        $this->transportProviders = array();
        $this->sessions = new \SplObjectStorage();

        if ($loop === null) {
            $manager->logDebug("No loop given, creating our own instance");
            $loop = Factory::create();
        }

        $this->loop = $loop;


    }

    public function onOpen(TransportInterface $transport)
    {
        $session = new Session($transport, $this->manager);

        // TODO: add a little more detail to this (what kind and address maybe?)
        $this->manager->logInfo("New Session started " . json_encode($transport->getTransportDetails()) . "");

        $this->sessions->attach($transport, $session);
    }

    public function onMessage(TransportInterface $transport, Message $msg)
    {
        /** @var $session Session */
        $session = $this->sessions[$transport];

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

    public function addTransportProvider(AbstractTransportProvider $transportProvider)
    {
        array_push($this->transportProviders, $transportProvider);
    }

    public function start()
    {
        $this->manager->logDebug("Starting router");
        if ($this->loop === null) {
            throw new \Exception("Loop is null");
        }

        if (count($this->transportProviders) == 0) {
            throw new \Exception("No transport providers specified.");
        }

        /** @var $transportProvider AbstractTransportProvider */
        foreach ($this->transportProviders as $transportProvider) {
            $this->manager->logDebug("Starting transport provider " . get_class($transportProvider));
            $transportProvider->startTransportProvider($this, $this->loop);
        }

        $this->setupManager();

        $this->manager->logDebug("Starting loop");
        $this->loop->run();
    }

    public function onClose(TransportInterface $transport)
    {
        $this->manager->logDebug("onClose from " . json_encode($transport->getTransportDetails()));

        /** @var  $session Session */
        $session = $this->sessions[$transport];

        $session->onClose();

        $this->sessions->detach($transport);
    }

    /**
     * @param \Thruway\ManagerInterface $manager
     */
    public function setManager($manager)
    {
//        $this->manager = $manager;
        throw new \Exception('Manager needs to be set in the constructor');
    }

    public function setupManager()
    {
        // setup the config for the manager
        $this->manager->addCallable("sessions.count", array($this, "managerGetSessionCount"));
        //$this->manager->addCallable("sessions.list", array($this, "managerGetSessionList"));
        $this->manager->addCallable("sessions.get", array($this, "managerGetSessions"));
        $this->manager->addCallable("realms.get", array($this, "managerGetRealms"));
    }

    /**
     * @return \Thruway\ManagerInterface
     */
    public function getManager()
    {
        return $this->manager;
    }

    public function managerGetSessionCount()
    {
        return array(count($this->sessions));
    }

    public function managerGetSessions()
    {
        $theSessions = array();

        /** @var $session Session */
        /** @var $transport TransportInterface */
        foreach ($this->sessions as $key) {
            $session = $this->sessions[$key];
            $theSessions[] = [
                "id" => $session->getSessionId(),
                "transport" => $session->getTransport()->getTransportDetails(),
                "messagesSent" => $session->getMessagesSent(),
                "sessionStart" => $session->getSessionStart(),
                "realm" => $session->getRealm()->getRealmName()
            ];
        }

        return $theSessions;
    }

    public function managerGetRealms()
    {
        $theRealms = [];

        /** @var $realm Realm */
        foreach ($this->realmManager->getRealms() as $realm) {
            $theRealms[] = [
                "name" => $realm->getRealmName()
            ];
        }

        return $theRealms;
    }

    public function managerGetTransports()
    {

    }
}