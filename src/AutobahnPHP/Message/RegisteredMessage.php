<?php
/**
 * Created by PhpStorm.
 * User: daviddan
 * Date: 6/12/14
 * Time: 11:25 AM
 */

namespace AutobahnPHP\Message;


use Voryx\Wamp2\Wamp2Connection;

class RegisteredMessage extends Message
{
    /**
     * @var
     */
    private $requestId;

    /**
     * @var
     */
    private $registrationId;

    /**
     * @param $registrationId
     * @param $requestId
     */
    function __construct($requestId, $registrationId)
    {
        $this->registrationId = $registrationId;
        $this->requestId = $requestId;
    }

    /**
     * @return int
     */
    public function getMsgCode()
    {
        return static::MSG_REGISTERED;
    }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        return array($this->getRequestId(), $this->getRegistrationId());
    }

    /**
     * @return array
     */
    public function getValidConnectionStates()
    {
        return array(Wamp2Connection::STATE_ESTABLISHED);
    }

    /**
     * @return mixed
     */
    public function getRegistrationId()
    {
        return $this->registrationId;
    }

    /**
     * @return mixed
     */
    public function getRequestId()
    {
        return $this->requestId;
    }


}