<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/7/14
 * Time: 11:58 AM
 */

namespace AutobahnPHP\Peer;


use AutobahnPHP\AbstractSession;
use AutobahnPHP\ClientSession;
use AutobahnPHP\Message\AbortMessage;
use AutobahnPHP\Message\ChallengeMessage;
use AutobahnPHP\Message\ErrorMessage;
use AutobahnPHP\Message\GoodbyeMessage;
use AutobahnPHP\Message\HelloMessage;
use AutobahnPHP\Message\Message;
use AutobahnPHP\Message\WelcomeMessage;
use AutobahnPHP\Role\AbstractRole;
use AutobahnPHP\Role\Callee;
use AutobahnPHP\Role\Caller;
use AutobahnPHP\Role\Publisher;
use AutobahnPHP\Role\Subscriber;
use AutobahnPHP\Session;
use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;

/**
 * Class Client
 * @package AutobahnPHP
 */
class Client extends AbstractPeer implements EventEmitterInterface
{
    use EventEmitterTrait;

    /**
     * @var
     */
    private $roles;

    /**
     * @var Callee
     */
    private $callee;

    /**
     * @var Caller
     */
    private $caller;

    /**
     * @var Publisher
     */
    private $publisher;

    /**
     * @var Subscriber
     */
    private $subscriber;

    /**
     * @param null $onChallenge
     */
    function __construct($onChallenge = null)
    {

        $this->roles = array();
    }

    /**
     * @param AbstractRole $role
     * @return $this
     */
    public function addRole(AbstractRole $role)
    {
        switch ($role) {
            case ($role instanceof Publisher):
                $this->publisher = $role;
                break;
            case ($role instanceof Subscriber):
                $this->subscriber = $role;
                break;
            case ($role instanceof Callee):
                $this->callee = $role;
                break;
            case ($role instanceof Caller):
                $this->caller = $role;
                break;
        }

        array_push($this->roles, $role);

        return $this;
    }

    /**
     * @param ClientSession $session
     */
    public function startSession(ClientSession $session)
    {
        $details = [
            "roles" => [
                "publisher" => new \stdClass(),
                "subscriber" => new \stdClass(),
                "caller" => new \stdClass(),
                "callee" => new \stdClass(),
            ]
        ];

        $session->sendMessage(new HelloMessage($session->getRealm(), $details, array()));
    }

    /**
     * @param \AutobahnPHP\AbstractSession $session
     * @param Message $msg
     */
    public function onMessage(AbstractSession $session, Message $msg)
    {

        echo "Client onMessage!\n";

        switch ($msg) {
            case ($msg instanceof WelcomeMessage):
                $this->processWelcome($session, $msg);
                break;
            case ($msg instanceof AbortMessage):
                $this->processAbort($session, $msg);
                break;
            case ($msg instanceof ErrorMessage):
                $this->processError($session, $msg);
                break;
            case ($msg instanceof GoodbyeMessage):
                $this->processGoodbye($session, $msg);
                break;
            //advanced
            case ($msg instanceof ChallengeMessage):
                $this->processChallenge($session, $msg);
                break;
            default:
                $this->processOther($session, $msg);
        }
    }

    /**
     * @param ClientSession $session
     * @param WelcomeMessage $msg
     */
    public function processWelcome(ClientSession $session, WelcomeMessage $msg)
    {
        //TODO: I'm sure that there are some other things that we need to do here
        $session->setSessionId($msg->getSessionId());

        $this->emit('open', [$session]);
    }

    /**
     * @param ClientSession $session
     * @param AbortMessage $msg
     */
    public function processAbort(ClientSession $session, AbortMessage $msg)
    {
        //TODO:  Implement this
    }

    /**
     * @param ClientSession $session
     * @param ChallengeMessage $msg
     */
    public function processChallenge(ClientSession $session, ChallengeMessage $msg)
    {
        // $this->emit('challenge', array($session, $msg->getAuthMethod(), array()));


    }

    /**
     * @param ClientSession $session
     * @param ErrorMessage $msg
     */
    public function processError(ClientSession $session, ErrorMessage $msg)
    {
        //TODO:  Implement this
    }

    /**
     * @param ClientSession $session
     * @param GoodbyeMessage $msg
     */
    public function processGoodbye(ClientSession $session, GoodbyeMessage $msg)
    {
        //TODO:  Implement this
    }

    /**
     * @param ClientSession $session
     * @param Message $msg
     */
    public function processOther(ClientSession $session, Message $msg)
    {
        /* @var $role AbstractRole */
        foreach ($this->roles as $role) {
            if ($role->handlesMessage($msg)) {
                $role->onMessage($session, $msg);
                break;
            }
        }
    }

    /**
     * @return Callee
     */
    public function getCallee()
    {
        return $this->callee;
    }


    /**
     * @return Caller
     */
    public function getCaller()
    {
        return $this->caller;
    }


    /**
     * @return Publisher
     */
    public function getPublisher()
    {
        return $this->publisher;
    }


    /**
     * @return Subscriber
     */
    public function getSubscriber()
    {
        return $this->subscriber;
    }


    /**
     * @return array
     */
    public function getRoles()
    {
        return $this->roles;
    }



} 