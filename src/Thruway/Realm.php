<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/9/14
 * Time: 11:04 PM
 */

namespace Thruway;


use Thruway\Authentication\AuthenticationManagerInterface;
use Thruway\Manager\ManagerDummy;
use Thruway\Manager\ManagerInterface;
use Thruway\Message\AuthenticateMessage;
use Thruway\Message\ErrorMessage;
use Thruway\Message\HelloMessage;
use Thruway\Message\Message;
use Thruway\Message\WelcomeMessage;
use Thruway\Role\AbstractRole;
use Thruway\Role\Broker;
use Thruway\Role\Dealer;

/**
 * Class Realm
 * @package Thruway
 */
class Realm
{


    /**
     * @var
     */
    private $realmName;
    /**
     * @var \SplObjectStorage
     */
    private $sessions;
    /**
     * @var array
     */
    private $roles;

    /**
     * @var ManagerInterface
     */
    private $manager;

    /**
     * @var Broker
     */
    private $broker;

    /**
     * @var Dealer
     */
    private $dealer;

    /**
     * @var AuthenticationManagerInterface
     */
    private $authenticationManager;

    /**
     * @var array
     */
    private $authMethods;

    /**
     * @param $realmName
     */
    function __construct($realmName)
    {
        $this->realmName = $realmName;
        $this->sessions = new \SplObjectStorage();

        $this->broker = new Broker();

        $this->dealer = new Dealer();

        $this->setManager(new ManagerDummy());

        $this->roles = array($this->broker, $this->dealer);

        $this->authenticationManager = null;

        $this->authMethods = array();
    }

    /**
     * @param Session $session
     * @param Message $msg
     */
    public function onMessage(Session $session, Message $msg)
    {
        if (!$session->isAuthenticated()) {
            if ($msg instanceof HelloMessage) {
                $this->manager->logDebug("got hello");
                // send welcome message
                if ($this->sessions->contains($session)) {
                    $this->manager->logError(
                        "Connection tried to rejoin realm when it is already joined to the realm."
                    );
                    $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg));
                    // TODO should shut down session here
                } else {
                    $this->sessions->attach($session);
                    $session->setRealm($this);
                    $session->setState(Session::STATE_UP); // this should probably be after authentication

                    if ($this->getAuthenticationManager() !== null) {
                        $this->getAuthenticationManager()->onAuthenticationMessage($this, $session, $msg);
                    } else {
                        $session->setAuthenticated(true);
                        // TODO: this will probably be pulled apart so that
                        // applications can actually create their own roles
                        // and attach them to realms - but for now...
                        $roles = array("broker" => new \stdClass, "dealer" => new \stdClass);
                        $session->sendMessage(
                            new WelcomeMessage($session->getSessionId(), array("roles" => $roles))
                        );
                    }
                }
            } else if ($msg instanceof AuthenticateMessage) {
                if ($this->getAuthenticationManager() !== null) {
                    $this->getAuthenticationManager()->onAuthenticationMessage($this, $session, $msg);
                } else {
                    // TODO: should shut down here probably
                    $this->manager->logError("Authenticate sent to realm without auth manager.");
                }
            } else {
                $this->manager->logError("Unhandled message sent to unauthenticated realm: " . $msg->getMsgCode());
            }
        } else {
            /* @var $role AbstractRole */
            foreach ($this->roles as $role) {
                if ($role->handlesMessage($msg)) {
                    $role->onMessage($session, $msg);
                    break;
                }
            }
        }
    }

    /**
     * @param mixed $realmName
     */
    public function setRealmName($realmName)
    {
        $this->realmName = $realmName;
    }

    /**
     * @return mixed
     */
    public function getRealmName()
    {
        return $this->realmName;
    }

    /**
     * @param Session $session
     */
    public function leave(Session $session)
    {

        $this->manager->logDebug("Leaving realm {$session->getRealm()->getRealmName()}");
        foreach ($this->roles as $role) {
            $role->leave($session);
        }
    }

    /**
     * @param ManagerInterface $manager
     */
    public function setManager($manager)
    {
        $this->manager = $manager;

        $this->broker->setManager($manager);
        $this->dealer->setManager($manager);

        $manager->addCallable(
            "realm.{$this->getRealmName()}.registrations",
            function () {
                return $this->dealer->managerGetRegistrations();
            }
        );
    }

    /**
     * @return ManagerInterface
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @param AuthenticationManagerInterface $authenticationManager
     */
    public function setAuthenticationManager($authenticationManager)
    {
        $this->authenticationManager = $authenticationManager;
    }

    /**
     * @return AuthenticationManagerInterface
     */
    public function getAuthenticationManager()
    {
        return $this->authenticationManager;
    }

    public function addAuthMethod($method) {
        array_push($this->authMethods, $method);
    }

    /**
     * @param array $authMethods
     */
    public function setAuthMethods($authMethods)
    {
        $this->authMethods = $authMethods;
    }

    /**
     * @return array
     */
    public function getAuthMethods()
    {
        return $this->authMethods;
    }



}