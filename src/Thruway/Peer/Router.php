<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/7/14
 * Time: 11:58 AM
 */

namespace Thruway\Peer;

use Thruway\Authentication\AuthenticationDetails;
use Thruway\Authentication\AuthenticationManagerInterface;
use Thruway\Manager\ManagerDummy;
use Thruway\Manager\ManagerInterface;
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
     * @var \SplObjectStorage
     */
    private $sessions;

    /**
     * @var AuthenticationManagerInterface
     */
    private $authenticationManager;

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

        $manager->debug("New router created");

        $this->realmManager = new RealmManager($manager);
        $this->transportProviders = array();
        $this->sessions = new \SplObjectStorage();

        if ($loop === null) {
            $manager->debug("No loop given, creating our own instance");
            $loop = Factory::create();
        }

        $this->loop = $loop;

        $authenticationManager = null;
    }

    public function onOpen(TransportInterface $transport)
    {
        $session = new Session($transport, $this->manager);

        // give the session the loop, just in case it wants to set a timer or something
        $session->setLoop($this->getLoop());

        // TODO: add a little more detail to this (what kind and address maybe?)
        $this->manager->info("New Session started " . json_encode($transport->getTransportDetails()) . "");

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

    public function addTransportProvider(AbstractTransportProvider $transportProvider)
    {
        array_push($this->transportProviders, $transportProvider);
    }

    public function start()
    {
        $this->manager->debug("Starting router");
        if ($this->loop === null) {
            throw new \Exception("Loop is null");
        }

        if (count($this->transportProviders) == 0) {
            throw new \Exception("No transport providers specified.");
        }

        /** @var $transportProvider AbstractTransportProvider */
        foreach ($this->transportProviders as $transportProvider) {
            $this->manager->debug("Starting transport provider " . get_class($transportProvider));
            $transportProvider->startTransportProvider($this, $this->loop);
        }

        $this->setupManager();

        $this->manager->debug("Starting loop");
        $this->loop->run();
    }

    public function onClose(TransportInterface $transport)
    {
        $this->manager->debug("onClose from " . json_encode($transport->getTransportDetails()));

        /** @var  $session Session */
        $session = $this->sessions[$transport];

        if ($this->getAuthenticationManager() !== null) {
            $this->getAuthenticationManager()->onSessionClose($session);
        }

        $session->onClose();

        $this->sessions->detach($transport);
    }

    /**
     * @param AuthenticationManagerInterface $authenticationManager
     */
    public function setAuthenticationManager($authenticationManager)
    {
        $this->authenticationManager = $authenticationManager;
        $this->realmManager->setDefaultAuthenticationManager($this->authenticationManager);
    }

    /**
     * @return AuthenticationManagerInterface
     */
    public function getAuthenticationManager()
    {
        return $this->authenticationManager;
    }


  /**
   * @param ManagerInterface $manager
   * @throws \Exception
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
     * @return \React\EventLoop\ExtEventLoop|\React\EventLoop\LibEventLoop|\React\EventLoop\LibEvLoop|\React\EventLoop\LoopInterface|\React\EventLoop\StreamSelectLoop
     */
    public function getLoop()
    {
        return $this->loop;
    }



    /**
     * @return ManagerInterface
     */
    public function getManager()
    {
        return $this->manager;
    }

    public function getSessionBySessionId($sessionId) {
        /** @var Session $session */
        $this->sessions->rewind();
        while ($this->sessions->valid()) {
            $session = $this->sessions->getInfo();
            if ($session->getSessionId() == $sessionId) {
                return $session;
            }
            $this->sessions->next();
        }
        return false;
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

            $sessionRealm = null;
            // just in case the session is not in a realm yet
            if ($session->getRealm() !== null) {
                $sessionRealm = $session->getRealm()->getRealmName();
            }

            if ($session->getAuthenticationDetails() !== null) {
                /** @var AuthenticationDetails $authDetails */
                $authDetails = $session->getAuthenticationDetails();
                $auth = array(
                    "authid" => $authDetails->getAuthId(),
                    "authmethod" => $authDetails->getAuthMethod()
                );
            } else {
                $auth = new \stdClass();
            }

            $theSessions[] = [
                "id" => $session->getSessionId(),
                "transport" => $session->getTransport()->getTransportDetails(),
                "messagesSent" => $session->getMessagesSent(),
                "sessionStart" => $session->getSessionStart(),
                "realm" => $sessionRealm,
                "auth" => $auth
            ];
        }

        return array($theSessions);
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

        return array($theRealms);
    }

    public function managerGetTransports()
    {

    }

    public function managerPruneSession($args) {

    }
}
