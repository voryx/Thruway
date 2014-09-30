<?php


/**
 * Class FacebookAuthProvider
 */
class FacebookAuthProvider extends \Thruway\Authentication\AbstractAuthProviderClient
{

    /**
     * @var \React\EventLoop\LoopInterface
     */
    private $appId;

    /**
     * @var
     */
    private $appSecret;

    /**
     * @param array $authRealms
     * @param \React\EventLoop\LoopInterface $appId
     * @param $appSecret
     */
    public function __construct(Array $authRealms, $appId, $appSecret)
    {
        $this->appId     = $appId;
        $this->appSecret = $appSecret;

        parent::__construct($authRealms);

    }

    /**
     * @return string
     */
    public function getMethodName()
    {
        return 'facebook';
    }

    /**
     * @param mixed $signature
     * @param null $extra
     * @return array
     */
    public function processAuthenticate($signature, $extra = null)
    {

        \Facebook\FacebookSession::setDefaultApplication($this->appId, $this->appSecret);

        $session     = new \Facebook\FacebookSession($signature);
        $sessionInfo = $session->getSessionInfo();

        //Make sure that we received a valid token
        if ($sessionInfo->isValid()) {
            return ["SUCCESS"];
        } else {
            return ["FAILURE"];
        }
    }

}