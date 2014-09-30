<?php

namespace Thruway\Peer;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use React\Promise\Deferred;
use React\Promise\Promise;
use Thruway\ClientAuthenticationInterface;
use Thruway\ClientSession;
use Thruway\Manager\ManagerDummy;
use Thruway\Manager\ManagerInterface;
use Thruway\Message\AbortMessage;
use Thruway\Message\AuthenticateMessage;
use Thruway\Message\ChallengeMessage;
use Thruway\Message\GoodbyeMessage;
use Thruway\Message\HelloMessage;
use Thruway\Message\Message;
use Thruway\Message\WelcomeMessage;
use Thruway\Realm;
use Thruway\Role\AbstractRole;
use Thruway\Role\Callee;
use Thruway\Role\Caller;
use Thruway\Role\Publisher;
use Thruway\Role\Subscriber;
use Thruway\Session;
use Thruway\Transport\AbstractTransportProvider;
use Thruway\Transport\TransportInterface;
use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;

/**
 * Class Client
 *
 * @package Thruway\Peer
 */
class Client extends AbstractPeer implements EventEmitterInterface
{

    /**
     * Implements EventEmitterInterface
     *
     * @uses EventEmitterTrait
     */
    use EventEmitterTrait;

    /**
     * @var array
     */
    private $roles;

    /**
     * @var \Thruway\ClientAuthenticationInterface[]
     */
    private $clientAuthenticators;

    /**
     * @var string
     */
    private $authId;

    /**
     * @var \Thruway\Role\Callee
     */
    private $callee;

    /**
     * @var \Thruway\Role\Caller
     */
    private $caller;

    /**
     * @var \Thruway\Role\Publisher
     */
    private $publisher;

    /**
     * @var \Thruway\Role\Subscriber
     */
    private $subscriber;

    /**
     * @var \Thruway\Transport\AbstractTransportProvider
     */
    private $transportProvider;

    /**
     * @var \Thruway\ClientSession
     */
    protected $session;

    /**
     * @var \React\EventLoop\LoopInterface
     */
    private $loop;

    /**
     * @var string
     */
    private $realm;

    /**
     * @var array
     */
    private $authMethods = [];

    /**
     * @var \Thruway\Transport\TransportInterface
     */
    private $transport;

    /**
     * @var int
     */
    private $retryTimer = 0;

    /**
     * @var array
     */
    private $reconnectOptions;

    /**
     * @var int
     */
    private $retryAttempts = 0;

    /**
     * @var boolean
     */
    private $attemptRetry = true;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param string $realm
     * @param \React\EventLoop\LoopInterface $loop
     */
    function __construct($realm, LoopInterface $loop = null)
    {
        $this->transportProvider = null;
        $this->roles             = [];
        $this->realm             = $realm;
        $this->authMethods       = [];

        if ($loop === null) {
            $loop = Factory::create();
        }

        $this->loop = $loop;

        $this->reconnectOptions = [
            "max_retries"         => 15,
            "initial_retry_delay" => 1.5,
            "max_retry_delay"     => 300,
            "retry_delay_growth"  => 1.5,
            "retry_delay_jitter"  => 0.1 //not implemented
        ];

        $this->manager = new ManagerDummy();

        $this->session = null;

        $this->on('open', [$this, 'onSessionStart']);

        $this->clientAuthenticators = [];
        $this->authId               = "anonymous";

        $this->setLogger(new NullLogger());
    }


    /**
     * This is meant to be overridden so that the client can do its
     * thing
     *
     * @param \Thruway\AbstractSession $session
     * @param \Thruway\Transport\TransportInterface $transport
     */
    public function onSessionStart($session, $transport)
    {

    }

    /**
     * Add transport provider
     *
     * @param \Thruway\Transport\AbstractTransportProvider $transportProvider
     * @throws \Exception
     */
    public function addTransportProvider(AbstractTransportProvider $transportProvider)
    {
        if ($this->transportProvider !== null) {
            throw new \Exception("You can only have one transport provider for a client");
        }
        $this->transportProvider = $transportProvider;
    }

    /**
     * Set reconnect options
     *
     * @param array $reconnectOptions
     */
    public function setReconnectOptions($reconnectOptions)
    {
        $this->reconnectOptions = array_merge($this->reconnectOptions, $reconnectOptions);
    }

    /**
     * Add client authenticator
     *
     * @param \Thruway\ClientAuthenticationInterface $ca
     */
    public function addClientAuthenticator(ClientAuthenticationInterface $ca)
    {
        array_push($this->clientAuthenticators, $ca);
    }

    /**
     * Start the transport
     *
     * @param boolean $startLoop
     */
    public function start($startLoop = true)
    {
        $this->transportProvider->startTransportProvider($this, $this->loop);

        if ($startLoop) {
            $this->loop->run();
        }
    }

    /**
     * Handle open transport
     *
     * @param TransportInterface $transport
     */
    public function onOpen(TransportInterface $transport)
    {
        $this->retryTimer    = 0;
        $this->retryAttempts = 0;
        $this->transport     = $transport;
        $session             = new ClientSession($transport, $this);

        $session->setLoop($this->getLoop());

        $this->session = $session;

        $session->setState(Session::STATE_DOWN);

        $this->startSession($session);
    }

    /**
     * Start client session
     *
     * @param \Thruway\ClientSession $session
     */
    public function startSession(ClientSession $session)
    {
        $details = [
            "roles" => [
                "publisher"  => new \stdClass(),
                "subscriber" => new \stdClass(),
                "caller"     => new \stdClass(),
                "callee"     => new \stdClass(),
            ]
        ];

        /** @var ClientAuthenticationInterface $ca */
        foreach ($this->clientAuthenticators as $ca) {
            $this->authMethods = array_merge($this->authMethods, $ca->getAuthMethods());
        }

        $details["authmethods"] = $this->authMethods;
        $details["authid"]      = $this->authId;

        $this->addRole(new Callee($this->getLogger()))
            ->addRole(new Caller())
            ->addRole(new Publisher())
            ->addRole(new Subscriber());

        $session->setRealm($this->realm);

        $session->sendMessage(new HelloMessage($session->getRealm(), $details, []));
    }

    /**
     * Add role
     *
     * @param \Thruway\Role\AbstractRole $role
     * @return \Thruway\Peer\Client
     */
    public function addRole(AbstractRole $role)
    {

        if ($role instanceof Publisher):
            $this->publisher = $role;
        elseif ($role instanceof Subscriber):
            $this->subscriber = $role;
        elseif ($role instanceof Callee):
            $this->callee = $role;
        elseif ($role instanceof Caller):
            $this->caller = $role;
        endif;

        array_push($this->roles, $role);

        return $this;
    }

    /**
     * Handle process message
     *
     * @param \Thruway\Transport\TransportInterface $transport
     * @param \Thruway\Message\Message $msg
     */
    public function onMessage(TransportInterface $transport, Message $msg)
    {

        $this->manager->debug("Client onMessage!");

        $session = $this->session;

        if ($msg instanceof WelcomeMessage):
            $this->processWelcome($session, $msg);
        elseif ($msg instanceof AbortMessage):
            $this->processAbort($session, $msg);
        elseif ($msg instanceof GoodbyeMessage):
            $this->processGoodbye($session, $msg);
        elseif ($msg instanceof ChallengeMessage): //advanced
        {
            $this->processChallenge($session, $msg);
        } else:
            $this->processOther($session, $msg);
        endif;


    }

    /**
     * Process Welcome message
     *
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Message\WelcomeMessage $msg
     */
    public function processWelcome(ClientSession $session, WelcomeMessage $msg)
    {
        $this->getLogger()->info("We have been welcomed...");
        //TODO: I'm sure that there are some other things that we need to do here
        $session->setSessionId($msg->getSessionId());
        $this->emit('open', [$session, $this->transport]);

        $session->setState(Session::STATE_UP);
    }

    /**
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Message\AbortMessage $msg
     */
    public function processAbort(ClientSession $session, AbortMessage $msg)
    {
        $this->emit('error', [$msg->getResponseURI()]);
        $session->shutdown();
    }

    /**
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Message\ChallengeMessage $msg
     */
    public function processChallenge(ClientSession $session, ChallengeMessage $msg)
    {

        $authMethod = $msg->getAuthMethod();

        // look for authenticator
        /** @var ClientAuthenticationInterface $ca */
        foreach ($this->clientAuthenticators as $ca) {
            if (in_array($authMethod, $ca->getAuthMethods())) {
                $authenticateMsg = $ca->getAuthenticateFromChallenge($msg);
                $session->sendMessage($authenticateMsg);
                return;
            }
        }

        $this->emit('challenge', [$session, $msg]);
    }

    /**
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Message\GoodbyeMessage $msg
     */
    public function processGoodbye(ClientSession $session, GoodbyeMessage $msg)
    {
        if (!$session->isGoodbyeSent()) {
            $goodbyeMsg = new GoodbyeMessage([], "wamp.error.goodbye_and_out");
            $session->sendMessage($goodbyeMsg);
            $session->setGoodbyeSent(true);
        }
    }

    /**
     * @param \Thruway\ClientSession $session
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
     * Handle end session
     *
     * @param \Thruway\ClientSession $session
     */
    public function onSessionEnd($session)
    {

    }

    /**
     * Handle close session
     *
     * @param mixed $reason
     */
    public function onClose($reason)
    {

        if (isset($this->session)) {
            $this->onSessionEnd($this->session);
            $this->session->onClose();
            $this->session = null;
            $this->emit('close', [$reason]);
        }

        $this->roles      = [];
        $this->callee     = null;
        $this->caller     = null;
        $this->subscriber = null;
        $this->publisher  = null;

        $this->retryConnection();

    }

    /**
     * Retry connecting to the transport
     */
    public function retryConnection()
    {
        $options = $this->reconnectOptions;

        if ($this->attemptRetry === false) {
            return;
        }

        if ($options['max_retries'] <= $this->retryAttempts) {
            return;
        }

        $this->retryAttempts++;

        if ($this->retryTimer >= $options['max_retry_delay']) {
            $this->retryTimer = $options['max_retry_delay'];
        } elseif ($this->retryTimer == 0) {
            $this->retryTimer = $options['initial_retry_delay'];
        } else {
            $this->retryTimer = $this->retryTimer * $options['retry_delay_growth'];
        }

        $this->loop->addTimer(
            $this->retryTimer,
            function () {
                $this->transportProvider->startTransportProvider($this, $this->loop);
            }
        );
    }


    /**
     * Set attempt retry
     *
     * @param boolean $attemptRetry
     */
    public function setAttemptRetry($attemptRetry)
    {
        $this->attemptRetry = $attemptRetry;
    }


    /**
     * @return \Thruway\Role\Callee
     */
    public function getCallee()
    {
        return $this->callee;
    }


    /**
     * @return \Thruway\Role\Caller
     */
    public function getCaller()
    {
        return $this->caller;
    }


    /**
     * @return \Thruway\Role\Publisher
     */
    public function getPublisher()
    {
        return $this->publisher;
    }

    /**
     * @return \Thruway\Role\Subscriber
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

    /**
     * @param \Thruway\Manager\ManagerInterface $manager
     */
    public function setManager($manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return \Thruway\Manager\ManagerInterface
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @return \React\EventLoop\ExtEventLoop|\React\EventLoop\LibEventLoop|\React\EventLoop\LibEvLoop|LoopInterface|\React\EventLoop\StreamSelectLoop
     */
    public function getLoop()
    {
        return $this->loop;
    }

    /**
     * @param string $authId
     */
    public function setAuthId($authId)
    {
        $this->authId = $authId;
    }

    /**
     * @return string
     */
    public function getAuthId()
    {
        return $this->authId;
    }

    /**
     * @return array
     */
    public function getAuthMethods()
    {
        return $this->authMethods;
    }

    /**
     * @param array $authMethods
     */
    public function setAuthMethods($authMethods)
    {
        $this->authMethods = $authMethods;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

}
