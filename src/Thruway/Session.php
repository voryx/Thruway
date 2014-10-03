<?php

namespace Thruway;


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
     * Constructor
     *
     * @param \Thruway\Transport\TransportInterface $transport
     * @param \Thruway\Manager\ManagerInterface $manager
     */
    function __construct(TransportInterface $transport, ManagerInterface $manager = null)
    {
        $this->transport             = $transport;
        $this->state                 = static::STATE_PRE_HELLO;
        $this->sessionId             = static::getUniqueId();
        $this->realm                 = null;
        $this->messagesSent          = 0;
        $this->sessionStart          = new \DateTime();
        $this->authenticationDetails = null;

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
            $this->realm->leave($this);
            $this->realm = null;
        }
    }

    /**
     * Generate a unique id for sessions and requests
     * @return mixed
     */
    static public function getUniqueId()
    {
        $uid = self::makeUID(16);
        return $uid;
    }
    
    /**
     * Function : makeUID
     * -------------------
     * Creates an alphabet to use inside the UID in a string of length $length.
     * @param {int} $length
     */
    public static function makeUID($length){
        $UID = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ"
                . "abcdefghijklmnopqrstuvwxyz" 
                . "0123456789";
        for($i=0;$i<$length;$i++){
            $UID .= $codeAlphabet[self::generateRandomCrypto(0,strlen($codeAlphabet))];
        }
        return $UID;
    }
    
    /**
     * Function : generateRandomCrypto
     * -----------------------------
     * Create a random number between $min and $max using openssl_random_pseudo_bytes
     * @param {int} $max
     * @return {int} $max
     */
    public static function generateRandomCrypto($min, $max) {
            $range = $max - $min;
            if ($range < 0) return $min; // not so random...
            $log = log($range, 2);
            $bytes = (int) ($log / 8) + 1; // length in bytes
            $bits = (int) $log + 1; // length in bits
            $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
            do {
                $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
                $rnd = $rnd & $filter; // discard irrelevant bits
            } while ($rnd >= $range);
            return $min + $rnd;
    }

    /**
     * @param \Thruway\Manager\ManagerInterface $manager
     * @throws \InvalidArgumentException
     */
    public function setManager(ManagerInterface $manager)
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
     * @return int
     */
    public function getMessagesSent()
    {
        return $this->messagesSent;
    }

    /**
     * @return \DateTime
     */
    public function getSessionStart()
    {
        return $this->sessionStart;
    }

    /**
     * @param \Thruway\Authentication\AuthenticationDetails $authenticationDetails
     */
    public function setAuthenticationDetails($authenticationDetails)
    {
        $this->authenticationDetails = $authenticationDetails;
    }

    /**
     * @return \Thruway\Authentication\AuthenticationDetails
     */
    public function getAuthenticationDetails()
    {
        return $this->authenticationDetails;
    }

}
