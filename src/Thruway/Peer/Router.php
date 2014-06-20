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
use Thruway\Message\HelloMessage;
use Thruway\Message\Message;
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
     * @var ManagerInterface
     */
    private $manager;

    /**
     *
     */
    function __construct(LoopInterface $loop = null)
    {
        $this->realmManager = new RealmManager();
        $this->transportProviders = array();
        $this->sessions = new \SplObjectStorage();

        if ($loop === null) {
            $loop = Factory::create();
        }

        $this->loop = $loop;

        // initially we are just going to start with a dummy manager
        $this->manager = new ManagerDummy();
    }

    public function onOpen(TransportInterface $transport) {
        $session = new Session($transport);

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
        if ($this->loop === null) {
            throw new \Exception("Loop is null");
        }

        if (count($this->transportProviders) == 0) {
            throw new \Exception("No transport providers specified.");
        }

        /** @var $transportProvider AbstractTransportProvider */
        foreach ($this->transportProviders as $transportProvider) {
            $transportProvider->startTransportProvider($this, $this->loop);
        }

        $this->setupManager();

        $this->loop->run();
    }

    public function onClose(TransportInterface $transport) {
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
        $this->manager = $manager;

    }

    public function setupManager() {
        // setup the config for the manager
        $this->manager->addCallable("sessions.count", array($this, "managerGetSessionCount"));

    }

    /**
     * @return \Thruway\ManagerInterface
     */
    public function getManager()
    {
        return $this->manager;
    }

    public function managerGetSessionCount() {
        return array(count($this->sessions));
    }
}