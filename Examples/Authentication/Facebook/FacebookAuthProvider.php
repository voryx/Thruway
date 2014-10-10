<?php


/**
 * Class FacebookAuthProvider
 * 
 * This example requires Facebook SDK v4
 * @see https://github.com/facebook/facebook-php-sdk-v4
 */
class FacebookAuthProvider extends \Thruway\Authentication\AbstractAuthProviderClient
{

    /**
     * @var string
     */
    private $appId;

    /**
     * @var string
     */
    private $appSecret;

    /**
     * Constructor
     * 
     * @param array $authRealms
     * @param string $appId
     * @param string $appSecret
     */
    public function __construct(Array $authRealms, $appId, $appSecret)
    {
        $this->appId     = $appId;
        $this->appSecret = $appSecret;

        parent::__construct($authRealms);

    }

    /**
     * Get authentication method name
     * 
     * @return string
     */
    public function getMethodName()
    {
        return 'facebook';
    }

    /**
     * process authenticate
     * 
     * @param mixed $signature
     * @param mixed $extra
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