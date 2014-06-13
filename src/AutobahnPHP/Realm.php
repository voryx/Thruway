<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/9/14
 * Time: 11:04 PM
 */

namespace AutobahnPHP;


use AutobahnPHP\Message\AuthenticateMessage;
use AutobahnPHP\Message\ChallengeMessage;
use AutobahnPHP\Message\ErrorMessage;
use AutobahnPHP\Message\HelloMessage;
use AutobahnPHP\Message\Message;
use AutobahnPHP\Message\PublishMessage;
use AutobahnPHP\Message\SubscribedMessage;
use AutobahnPHP\Message\SubscribeMessage;
use AutobahnPHP\Message\UnsubscribeMessage;
use AutobahnPHP\Message\WelcomeMessage;
use AutobahnPHP\Role\AbstractRole;
use AutobahnPHP\Role\Broker;
use AutobahnPHP\Role\Dealer;

class Realm
{


    private $realmName;
    private $sessions;

    private $roles;

    /**
     * @var TopicManager
     */
    private $topicManager;

    function __construct($realmName)
    {
        $this->realmName = $realmName;
        $this->sessions = new \SplObjectStorage();
        $this->topicManager = new TopicManager();
        $this->subscriptions = new \SplObjectStorage();
        $this->roles = array(new Broker(), new Dealer());
    }

    public function onMessage(Session $session, Message $msg)
    {

        if (!$session->isAuthenticated()) {
            if ($msg instanceof HelloMessage) {
                echo "got hello";
                // send welcome message
                if ($this->sessions->contains($session)) {
                    echo "Connection tried to rejoin realm when it is already joined to the realm.";
                    $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg));
                    // TODO should shut down session here
                } else {
                    $this->sessions->attach($session);
                    $session->setRealm($this);
                    $session->setState(Session::STATE_UP);

                    if ($session->getAuthenticationProvider()) {
                        foreach ($msg->getAuthMethods() as $authMethod) {
                            if ($session->getAuthenticationProvider()->supports($authMethod)) {
                                $session->sendMessage(new ChallengeMessage($authMethod));
                                break;
                            }
                        }
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
            } else {
                if ($msg instanceof AuthenticateMessage) {

                    // @todo really check to see if the user is authenticated
                    $authenticationProvider = $session->getAuthenticationProvider();
                    if ($authenticationProvider && $authenticationProvider->authenticate($msg->getSignature())) {

                        $session->setAuthenticated(true);

                        // TODO: this will probably be pulled apart so that
                        // applications can actually create their own roles
                        // and attach them to realms - but for now...
                        $roles = array("broker" => new \stdClass, "dealer" => new \stdClass);

                        $session->sendMessage(
                            new WelcomeMessage(
                                $session->getSessionId(),
                                array(
                                    "authid" => $authenticationProvider->getAuthenticationId(),
                                    "authmethod" => $authenticationProvider->getAuthenticationMethod(),
                                    "authrole" => $authenticationProvider->getAuthenticationRole(),
                                    "roles" => $roles,
                                )
                            )
                        );
                    } else {
                        //Send some message that says they were unable to authenticate
                        echo "Unhandled message sent to authenticate\n";
                    }


                } else {
                    echo "Unhandled message sent to unauthenticated realm: " . $msg->getMsgCode() . "\n";
                }
            }
        } else {
            // this is actually the job of the broker - should be broken out
            // if (brokerMessage thing) $broker->handleIt();

            /* @var $role AbstractRole */
            foreach ($this->roles as $role) {
                if ($role->handlesMessage($msg)) {
                    $role->onMessage($session, $msg);
                    break;
                }
            }


//             elseif ($msg instanceof GoodbyeMessage) {
//                // clean up
//                // unsubscribe everything
//                $conn->unsubscribeAll();
//                // leave it up to the Wamp2Server to send the goodbye and shutdown the transport
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
}