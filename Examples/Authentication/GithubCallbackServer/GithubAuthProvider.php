<?php


//https://developer.github.com/v3/oauth/

class GithubAuthProvider extends \Thruway\Authentication\AbstractAuthProviderClient {


  private $clientId;
  private $clientSecret;
  private $promises = array();


  function __construct($http, $clientId, $clientSecret) {

    $this->clientId = $clientId;
    $this->clientSecret = $clientSecret;
    parent::__construct();

    //Register Http request event
    $http->on('request', array($this, "onHttpRequest"));

  }


  /**
   * @return string
   */
  public function getMethodName() {
    return 'github';
  }

  public function processAuthenticate($state, $extra = NULL) {

    if (!isset($state)) {
      return array("FAILURE");
    }

    //If we don't already have a promise for this state, create one
    if (!isset($this->promises[$state])) {
      $deferred = new \React\Promise\Deferred();
      $this->promises[$state] = $deferred;
    }

    return $this->promises[$state]->promise();

  }

  /**
   * @return \React\Http\Server
   */
  public function getHttp() {
    return $this->http;
  }


  public function onHttpRequest(\React\Http\Request $request, $response) {
    if ($request->getPath() !== "/auth/github/callback") {
      $response->writeHead(404, array('Content-Type' => 'text/plain'));
      $response->end("Not Found");
    }

    $query = $request->getQuery();
    if (!isset($query['state']) || !isset($query['code'])) {
      $response->writeHead(200, array('Content-Type' => 'text/plain'));
      $response->end("No Code or State query params found");
      return;
    }

    //If we don't already have a promise for this state, create one
    if (!isset($this->promises[$query['state']])) {
      $deferred = new \React\Promise\Deferred();
      $this->promises[$query['state']] = $deferred;
    }

    $accessToken = $this->getAccessToken($query['code']);
    if ($accessToken) {
      $this->promises[$query['state']]->resolve(array("SUCCESS", $accessToken));
    }
    else {
      $this->promises[$query['state']]->resolve(array("FAILURE"));
    }

    $response->writeHead(200, array('Content-Type' => 'text/html'));
    $response->end("<script>window.close();</script>");


  }

  private function getAccessToken($code) {

    $data = array(
      "client_id" => $this->clientId,
      "client_secret" => $this->clientSecret,
      "code" => $code
    );
    $data_string = json_encode($data);

    //This needs to be replaced with Guzzle
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
      return $resultArray['access_token'];
    }
    else {
      return FALSE;
    }
  }

}