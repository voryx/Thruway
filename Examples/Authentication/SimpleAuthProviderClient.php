<?php

if (file_exists(__DIR__ . '/../../../../autoload.php')) {
  require __DIR__ . '/../../../../autoload.php';
}
else {
  require __DIR__ . '/../../vendor/autoload.php';
}

class SimpleAuthProviderClient extends \Thruway\Authentication\AbstractAuthProviderClient {

  /**
   * @return string
   */
  public function getMethodName() {
    return 'simplysimple';
  }

  public function processAuthenticate($signature, $extra = NULL) {

    if ($signature == "letMeIn") {
      return array("SUCCESS");
    }
    else {
      return array("FAILURE");
    }

  }
}