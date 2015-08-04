<?php

namespace Thruway;


use Thruway\Authentication\AuthenticationDetails;
use Thruway\Common\Utils;
use Thruway\Event\EventDispatcher;
use Thruway\Event\LeaveRealmEvent;
use Thruway\Event\MessageEvent;
use Thruway\Manager\ManagerDummy;
use Thruway\Manager\ManagerInterface;
use Thruway\Message\HelloMessage;
use Thruway\Message\Message;
use Thruway\Module\RealmModuleInterface;
use Thruway\Transport\TransportInterface;

/**
 * Class Session
 *
 * @package Thruway
 */
class Session extends AbstractSession implements RealmModuleInterface
{

    /** @var \Thruway\Authentication\AuthenticationDetails */
    private $authenticationDetails;

    /** @var int */
    private $messagesSent;

    /** @var \DateTime */
    private $sessionStart;

    /** @var \Thruway\Manager\ManagerInterface */
    private $manager;

    /** @var int */
    private $pendingCallCount;

    /** @var \stdClass|null */
    private $roleFeatures;

    /** @var HelloMessage */
    private $helloMessage;

    /** @var EventDispatcher */
    public $dispatcher;

    /**
     * Constructor
     *
     * @param \Thruway\Transport\TransportInterface $transport
     * @param \Thruway\Manager\ManagerInterface $manager
     */
    public function __construct(TransportInterface $transport, ManagerInterface $manager = null)
    {
        $this->transport             = $transport;
        $this->state                 = static::STATE_PRE_HELLO;
        $this->sessionId             = Utils::getUniqueId();
        $this->realm                 = null;
        $this->messagesSent          = 0;
        $this->sessionStart          = new \DateTime();
        $this->authenticationDetails = null;
        $this->pendingCallCount      = 0;
        $this->dispatcher            = new EventDispatcher();

        $this->dispatcher->addRealmSubscriber($this);

        if ($manager === null) {
            $manager = new ManagerDummy();
        }

        $this->setManager($manager);

    }

    /**
     * Events that we'll be listening on
     *
     * @return array
     */
    public function getSubscribedRealmEvents()
    {
        return [
          "SendAbortMessageEvent"        => ["handleSendMessage", 10],
          "SendAuthenticateMessageEvent" => ["handleSendMessage", 10],
          "SendChallengeMessageEvent"    => ["handleSendMessage", 10],
          "SendErrorMessageEvent"        => ["handleSendMessage", 10],
          "SendEventMessageEvent"        => ["handleSendMessage", 10],
          "SendGoodbyeMessageEvent"      => ["handleSendMessage", 10],
          "SendInterruptMessageEvent"    => ["handleSendMessage", 10],
          "SendInvocationMessageEvent"   => ["handleSendMessage", 10],
          "SendPublishedMessageEvent"    => ["handleSendMessage", 10],
          "SendRegisteredMessageEvent"   => ["handleSendMessage", 10],
          "SendResultMessageEvent"       => ["handleSendMessage", 10],
          "SendSubscribedMessageEvent"   => ["handleSendMessage", 10],
          "SendUnregisteredMessageEvent" => ["handleSendMessage", 10],
          "SendUnsubscribedMessageEvent" => ["handleSendMessage", 10],
          "SendWelcomeMessageEvent"      => ["handleSendMessage", 10]
        ];
    }

    /**
     * @param \Thruway\Event\MessageEvent $event
     */
    public function handleSendMessage(MessageEvent $event)
    {
        $this->sendMessageToTransport($event->message);
    }

    /**
     * Send message
     *
     * @param \Thruway\Message\Message $msg
     * @return mixed|void
     */
    public function sendMessage(Message $msg)
    {
        $this->dispatchMessage($msg, "Send");
    }

    /**
     * @param \Thruway\Message\Message $msg
     */
    private function sendMessageToTransport(Message $msg)
    {
        $this->messagesSent++;
        $this->transport->sendMessage($msg);
    }

    /**
     * Handle close session
     */
    public function onClose()
    {
        if ($this->realm !== null) {
            // only send the leave metaevent if we actually made it into the realm
            if ($this->isAuthenticated()) {
                // metaevent
                $this->getRealm()->publishMeta('wamp.metaevent.session.on_leave', [$this->getMetaInfo()]);
            }
            $this->dispatcher->dispatch("LeaveRealm", new LeaveRealmEvent($this->realm, $this));

            unset($this->dispatcher);

            $this->realm = null;
        }
    }


    /**
     * Set manager
     *
     * @param \Thruway\Manager\ManagerInterface $manager
     * @throws \InvalidArgumentException
     */
    public function setManager(ManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Get manager
     *
     * @return \Thruway\Manager\ManagerInterface
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * Get number sent messages
     *
     * @return int
     */
    public function getMessagesSent()
    {
        return $this->messagesSent;
    }

    /**
     * Get time session start at
     *
     * @return \DateTime
     */
    public function getSessionStart()
    {
        return $this->sessionStart;
    }

    /**
     * Set authentication details
     *
     * @param \Thruway\Authentication\AuthenticationDetails $authenticationDetails
     */
    public function setAuthenticationDetails($authenticationDetails)
    {
        $this->authenticationDetails = $authenticationDetails;
    }

    /**
     * Get authentication details
     *
     * @return \Thruway\Authentication\AuthenticationDetails
     */
    public function getAuthenticationDetails()
    {
        return $this->authenticationDetails;
    }

    /**
     * Set authenticated state
     *
     * @param boolean $authenticated
     */
    public function setAuthenticated($authenticated)
    {
        // generally, there is no provisions in the WAMP specs to change from
        // authenticated to unauthenticated
        if ($this->authenticated && !$authenticated) {
            $this->getManager()->error("Session changed from authenticated to unauthenticated");
        }

        // make sure the metaevent is only sent when changing from
        // not-authenticate to authenticated
        if ($authenticated && !$this->authenticated) {
            // metaevent
            $this->getRealm()->publishMeta('wamp.metaevent.session.on_join', [$this->getMetaInfo()]);
        }
        parent::setAuthenticated($authenticated);

    }

    /**
     * Get meta info
     *
     * @return array
     */
    public function getMetaInfo()
    {
        if ($this->getAuthenticationDetails() instanceof AuthenticationDetails) {
            $authId     = $this->getAuthenticationDetails()->getAuthId();
            $authMethod = $this->getAuthenticationDetails()->getAuthMethod();
            $authRole   = $this->getAuthenticationDetails()->getAuthRole();
            $authRoles  = $this->getAuthenticationDetails()->getAuthRoles();
        } else {
            $authId     = "anonymous";
            $authMethod = "anonymous";
            $authRole   = "anonymous";
            $authRoles  = [];
        }

        return [
          "realm"         => $this->getRealm()->getRealmName(),
          "authprovider"  => null,
          "authid"        => $authId,
          "authrole"      => $authRole,
          "authroles"     => $authRoles,
          "authmethod"    => $authMethod,
          "session"       => $this->getSessionId(),
          "role_features" => $this->getRoleFeatures()
        ];
    }

    /**
     * @return int
     */
    public function getPendingCallCount()
    {
        return $this->pendingCallCount;
    }

    /**
     * @param int $pendingCallCount
     */
    public function setPendingCallCount($pendingCallCount)
    {
        $this->pendingCallCount = $pendingCallCount;
    }

    /**
     * @return int
     */
    public function incPendingCallCount()
    {
        return $this->pendingCallCount++;
    }

    /**
     * @return int
     */
    public function decPendingCallCount()
    {
        // if we are already at zero - something is wrong
        if ($this->pendingCallCount == 0) {
            $this->getManager()->alert('Session pending call count wants to go negative.');

            return 0;
        }

        return $this->pendingCallCount--;
    }

    /**
     * @return null|\stdClass
     */
    public function getRoleFeatures()
    {
        return $this->roleFeatures;
    }

    /**
     * @param null|\stdClass $roleFeatures
     */
    public function setRoleFeatures($roleFeatures)
    {
        $this->roleFeatures = $roleFeatures;
    }

    /**
     * @return HelloMessage
     */
    public function getHelloMessage()
    {
        return $this->helloMessage;
    }

    /**
     * @param HelloMessage $helloMessage
     */
    public function setHelloMessage($helloMessage)
    {
        $this->helloMessage = $helloMessage;
    }

    /**
     * @param \Thruway\Message\Message $message
     * @param string $eventNamePrefix
     */
    public function dispatchMessage(Message $message, $eventNamePrefix = "")
    {
        // this could probably become a constant inside the message itself
        $r         = new \ReflectionClass($message);
        $shortName = $r->getShortName();

        if ($message instanceof HelloMessage) {
            $this->dispatcher->dispatch("Pre".$shortName."Event", new MessageEvent($this, $message));
        }
        $this->dispatcher->dispatch($eventNamePrefix.$shortName."Event", new MessageEvent($this, $message));
    }

}
