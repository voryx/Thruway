<?php

namespace Thruway;


use Thruway\Authentication\AuthenticationDetails;
use Thruway\Manager\ManagerDummy;
use Thruway\Manager\ManagerInterface;
use Thruway\Message\Message;
use Thruway\Transport\TransportInterface;

/**
 * Class Session
 *
 * @package Thruway
 */
class Session extends AbstractSession
{

    /**
     * @var \Thruway\Authentication\AuthenticationDetails
     */
    private $authenticationDetails;


    /**
     * @var int
     */
    private $messagesSent;

    /**
     * @var \DateTime
     */
    private $sessionStart;

    /**
     * @var \Thruway\Manager\ManagerInterface
     */
    private $manager;

    /**
     * @var int
     */
    private $pendingCallCount;

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
        $this->sessionId             = Session::getUniqueId();
        $this->realm                 = null;
        $this->messagesSent          = 0;
        $this->sessionStart          = new \DateTime();
        $this->authenticationDetails = null;
        $this->pendingCallCount      = 0;

        if ($manager === null) {
            $manager = new ManagerDummy();
        }

        $this->setManager($manager);

    }

    /**
     * Send message
     *
     * @param \Thruway\Message\Message $msg
     * @return mixed|void
     */
    public function sendMessage(Message $msg)
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
            $this->realm->leave($this);
            $this->realm = null;
        }
    }

    /**
     * Generate a unique id for sessions and requests
     * @return mixed
     */
    public static function getUniqueId()
    {
        $filter      = 0x1fffffffffffff; // 53 bits
        $randomBytes = openssl_random_pseudo_bytes(8);
        list($high, $low) = array_values(unpack("N2", $randomBytes));
        return abs(($high << 32 | $low) & $filter);
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
        } else {
            $authId     = "anonymous";
            $authMethod = "anonymous";
        }

        return [
            "realm"        => $this->getRealm()->getRealmName(),
            "authprovider" => null,
            "authid"       => $authId,
            "authrole"     => "none",
            "authmethod"   => $authMethod,
            "session"      => $this->getSessionId()
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
    public function incPendingCallCount() {
        return $this->pendingCallCount++;
    }

    /**
     * @return int
     */
    public function decPendingCallCount() {
        // if we are already at zero - something is wrong
        if ($this->pendingCallCount == 0) {
            $this->getManager()->alert('Session pending call count wants to go negative.');
            return 0;
        }
        return $this->pendingCallCount--;
    }
}
