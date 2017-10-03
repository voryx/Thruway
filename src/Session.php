<?php

namespace Thruway;

use Thruway\Authentication\AuthenticationDetails;
use Thruway\Common\Utils;
use Thruway\Event\EventDispatcher;
use Thruway\Event\LeaveRealmEvent;
use Thruway\Event\MessageEvent;
use Thruway\Logging\Logger;
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
    private $messagesSent = 0;

    /** @var int */
    private $messagesReceived = 0;

    /** @var \DateTime */
    private $sessionStart;

    /** @var int */
    private $pendingCallCount = 0;

    /** @var \stdClass|null */
    private $roleFeatures;

    /** @var HelloMessage */
    private $helloMessage;

    /** @var EventDispatcher */
    public $dispatcher;

    /** @var float */
    private $lastInboundActivity = 0;

    /** @var float */
    private $lastOutboundActivity = 0;

    /**
     * Constructor
     *
     * @param \Thruway\Transport\TransportInterface $transport
     */
    public function __construct(TransportInterface $transport)
    {
        $this->transport             = $transport;
        $this->state                 = static::STATE_PRE_HELLO;
        $this->sessionId             = Utils::getUniqueId();
        $this->realm                 = null;
        $this->sessionStart          = new \DateTime();
        $this->authenticationDetails = null;
        $this->dispatcher            = new EventDispatcher();

        $this->dispatcher->addRealmSubscriber($this);
    }

    /**
     * Events that we'll be listening on
     *
     * @return array
     */
    public function getSubscribedRealmEvents()
    {
        return [
            'SendAbortMessageEvent'        => ['handleSendMessage', 10],
            'SendAuthenticateMessageEvent' => ['handleSendMessage', 10],
            'SendChallengeMessageEvent'    => ['handleSendMessage', 10],
            'SendErrorMessageEvent'        => ['handleSendMessage', 10],
            'SendEventMessageEvent'        => ['handleSendMessage', 10],
            'SendGoodbyeMessageEvent'      => ['handleSendMessage', 10],
            'SendInterruptMessageEvent'    => ['handleSendMessage', 10],
            'SendInvocationMessageEvent'   => ['handleSendMessage', 10],
            'SendPublishedMessageEvent'    => ['handleSendMessage', 10],
            'SendRegisteredMessageEvent'   => ['handleSendMessage', 10],
            'SendResultMessageEvent'       => ['handleSendMessage', 10],
            'SendSubscribedMessageEvent'   => ['handleSendMessage', 10],
            'SendUnregisteredMessageEvent' => ['handleSendMessage', 10],
            'SendUnsubscribedMessageEvent' => ['handleSendMessage', 10],
            'SendWelcomeMessageEvent'      => ['handleSendMessage', 10]
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
        $this->lastOutboundActivity = microtime(true);
        $this->dispatchMessage($msg, 'Send');
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
            $this->dispatcher->dispatch('LeaveRealm', new LeaveRealmEvent($this->realm, $this));

            $this->realm = null;
        }
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
     * @return int
     */
    public function getMessagesReceived()
    {
        return $this->messagesReceived;
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
            $authId     = 'anonymous';
            $authMethod = 'anonymous';
            $authRole   = 'anonymous';
            $authRoles  = [];
        }

        return [
            'realm'         => $this->getRealm()->getRealmName(),
            'authprovider'  => null,
            'authid'        => $authId,
            'authrole'      => $authRole,
            'authroles'     => $authRoles,
            'authmethod'    => $authMethod,
            'session'       => $this->getSessionId(),
            'role_features' => $this->getRoleFeatures()
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
        if ($this->pendingCallCount === 0) {
            Logger::alert($this, 'Session pending call count wants to go negative.');

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
     * @return float
     */
    public function getLastInboundActivity()
    {
        return $this->lastInboundActivity;
    }

    /**
     * @return float
     */
    public function getLastOutboundActivity()
    {
        return $this->lastOutboundActivity;
    }

    /**
     * @param \Thruway\Message\Message $message
     * @param string $eventNamePrefix
     */
    public function dispatchMessage(Message $message, $eventNamePrefix = '')
    {
        if ($eventNamePrefix === '') {
            $this->lastInboundActivity = microtime(true);
            $this->messagesReceived++;
        }

        // this could probably become a constant inside the message itself
        $shortName = (new \ReflectionClass($message))->getShortName();

        if ($message instanceof HelloMessage) {
            $this->dispatcher->dispatch('Pre' . $shortName . 'Event', new MessageEvent($this, $message));
        }
        $this->dispatcher->dispatch($eventNamePrefix . $shortName . 'Event', new MessageEvent($this, $message));
    }
}
