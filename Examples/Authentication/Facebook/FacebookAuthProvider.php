<?php

if (file_exists(__DIR__ . '/../../../../autoload.php')) {
  require __DIR__ . '/../../../../autoload.php';
}
else {
  require __DIR__ . '/../../vendor/autoload.php';
}

class FacebookAuthProvider extends \Thruway\Authentication\AbstractAuthProviderClient {

  private $appId;

  private $appSecret;

  public function __construct($appId, $appSecret) {
    $this->appId = $appId;
    $this->appSecret = $appSecret;

    parent::__construct();

  }

  /**
   * @return string
   */
  public function getMethodName() {
    return 'facebook';
  }

  public function processAuthenticate($signature, $extra = NULL) {

    \Facebook\FacebookSession::setDefaultApplication($this->appId, $this->appSecret);

    $session = new \Facebook\FacebookSession($signature);
    $sessionInfo = $session->getSessionInfo();

    //Make sure that we received a valid token
    if ($sessionInfo->isValid()) {
      return array("SUCCESS");
    }
    else {
      return array("FAILURE");
    }

  }
}