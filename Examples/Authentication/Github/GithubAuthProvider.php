<?php

//https://developer.github.com/v3/oauth/

class GithubAuthProvider extends \Thruway\Authentication\AbstractAuthProviderClient {


  private $clientId;
  private $clientSecret;

  function __construct(Array $authRealms, $clientId, $clientSecret) {
    $this->clientId = $clientId;
    $this->clientSecret = $clientSecret;
    parent::__construct($authRealms);
  }


  /**
   * @return string
   */
  public function getMethodName() {
    return 'github';
  }

  public function processAuthenticate($code, $extra = NULL) {

    if (isset($code)) {
      $data = array(
        "client_id" => $this->clientId,
        "client_secret" => $this->clientSecret,
        "code" => $code
      );
      $data_string = json_encode($data);

      //Replace with Guzzle example
      $ch = curl_init('https://github.com/login/oauth/access_token');
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt(
        $ch,
        CURLOPT_HTTPHEADER,
        array(
          'Content-Type: application/json',
          'Content-Length: ' . strlen($data_string)
        )
      );

      $result = curl_exec($ch);
      parse_str($result, $resultArray);

      if (isset($resultArray['access_token'])) {
        return array("SUCCESS");
      }
      else {
        return array("FAILURE");
      }
    }
    else {
      return array("FAILURE");
    }

  }

  /**
   * @return \React\Http\Server
   */
  public function getHttp() {
    return $this->http;
  }


}