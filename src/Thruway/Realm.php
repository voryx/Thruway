<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/9/14
 * Time: 11:04 PM
 */

namespace Thruway;


use Thruway\Authentication\AuthenticationDetails;
use Thruway\Authentication\AuthenticationManagerInterface;
use Thruway\Manager\ManagerDummy;
use Thruway\Manager\ManagerInterface;
use Thruway\Message\AbortMessage;
use Thruway\Message\AuthenticateMessage;
use Thruway\Message\ErrorMessage;
use Thruway\Message\GoodbyeMessage;
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
    }

    /**
     * @param Session $session
     * @param Message $msg
     */
    public function onMessage(Session $session, Message $msg)
    {

        if ($msg instanceof GoodByeMessage):
            $this->manager->info("Received a GoodBye, so shutting the session down");
            $session->shutdown();
        elseif ($session->isAuthenticated()):
            $this->processAuthenticated($session, $msg);
        elseif ($msg instanceof HelloMessage):
            $this->processHello($session, $msg);
        elseif ($msg instanceof AuthenticateMessage):
            $this->processAuthenticate($session, $msg);
        else:
            $this->manager->error("Unhandled message sent to unauthenticated realm: " . $msg->getMsgCode());
            $session->sendMessage(new AbortMessage(new \stdClass(), "wamp.error.not_authorized"));
            $session->shutdown();
        endif;

    }


    /**
     * Process All Messages if the session has been authenticated
     *
     * @param Session $session
     * @param Message $msg
     */
    private function processAuthenticated(Session $session, Message $msg)
    {
        $handled = false;
        /* @var $role AbstractRole */
        foreach ($this->roles as $role) {
            if ($role->handlesMessage($msg)) {
                $role->onMessage($session, $msg);
                $handled = true;
                break;
            }
        }

        if (!$handled) {
            $this->manager->warning("Unhandled message sent to \"{$this->getRealmName()}\"");
        }
    }

    /**
     * @param Session $session
     * @param HelloMessage $msg
     */
    private function processHello(Session $session, HelloMessage $msg)
    {
        $this->manager->debug("got hello");
        // send welcome message
        if ($this->sessions->contains($session)) {
            $this->manager->error(
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

                $session->setAuthenticationDetails(AuthenticationDetails::createAnonymous());

                // the broker and dealer should give us this information
                $roles = array("broker" => new \stdClass, "dealer" => new \stdClass);
                $session->sendMessage(
                    new WelcomeMessage($session->getSessionId(), array("roles" => $roles))
                );
            }
        }
    }

    /**
     * @param Session $session
     * @param AuthenticateMessage $msg
     */
    private function processAuthenticate(Session $session, AuthenticateMessage $msg)
    {
        if ($this->getAuthenticationManager() !== null) {
            $this->getAuthenticationManager()->onAuthenticationMessage($this, $session, $msg);
        } else {
            // TODO: should shut down here probably
            $this->manager->error("Authenticate sent to realm without auth manager.");
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

        $this->manager->debug("Leaving realm {$session->getRealm()->getRealmName()}");

        if ($this->getAuthenticationManager() !== null) {
            $this->getAuthenticationManager()->onSessionClose($session);
        }

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

    /**
     * @return \SplObjectStorage
     */
    public function getSessions()
    {
        return $this->sessions;
    }

    /**
     * @return Broker
     */
    public function getBroker()
    {
        return $this->broker;
    }

    /**
     * @return Dealer
     */
    public function getDealer()
    {
        return $this->dealer;
    }
}
