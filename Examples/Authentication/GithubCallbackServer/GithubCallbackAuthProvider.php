<?php

use React\Promise\Deferred;
use Thruway\Authentication\AbstractAuthProviderClient;

/**
 * Class GithubCallbackAuthProvider
 * 
 * this example require  https://github.com/reactphp/http
 * 
 * @see https://developer.github.com/v3/oauth/
 * @see https://github.com/reactphp/http
 */
class GithubCallbackAuthProvider extends AbstractAuthProviderClient
{

    /**
     * @var string
     */
    private $clientId;
    
    /**
     * @var string
     */
    private $clientSecret;
    
    /**
     * @var array
     */
    private $promises = [];

    /**
     * Constructor
     * 
     * @param array $authRealms
     * @param \React\Http\Server $http
     * @param string $clientId
     * @param string $clientSecret
     */
    public function __construct($authRealms, $http, $clientId, $clientSecret)
    {

        $this->clientId     = $clientId;
        $this->clientSecret = $clientSecret;

        parent::__construct($authRealms);

        //Register Http request event
        $http->on('request', [$this, "onHttpRequest"]);

    }


    /**
     * Get authentication method name
     * 
     * @return string
     */
    public function getMethodName()
    {
        return 'github';
    }

    /**
     * Process authenticate
     * 
     * @param mixed $state
     * @param mixed $extra
     * @return array
     */
    public function processAuthenticate($state, $extra = null)
    {

        if (!isset($state)) {
            return ["FAILURE"];
        }

        //If we don't already have a promise for this state, create one
        if (!isset($this->promises[$state])) {
            $deferred               = new Deferred();
            $this->promises[$state] = $deferred;
        }

        return $this->promises[$state]->promise();

    }

    /**
     * Get http loop
     * 
     * @return \React\Http\Server
     */
    public function getHttp()
    {
        return $this->http;
    }


    /**
     * Handle process on http request
     * 
     * @param \React\Http\Request $request
     * @param \React\Http\Request $response
     */
    public function onHttpRequest(\React\Http\Request $request, $response)
    {
        if ($request->getPath() !== "/auth/github/callback") {
            $response->writeHead(404, ['Content-Type' => 'text/plain']);
            $response->end("Not Found");
            return;
        }

        $query = $request->getQuery();
        if (!isset($query['state']) || !isset($query['code'])) {
            $response->writeHead(200, ['Content-Type' => 'text/plain']);
            $response->end("No Code or State query params found");
            return;
        }

        //If we don't already have a promise for this state, create one
        if (!isset($this->promises[$query['state']])) {
            $deferred                        = new Deferred();
            $this->promises[$query['state']] = $deferred;
        }

        $accessToken = $this->getAccessToken($query['code']);
        if ($accessToken) {
            $email = $this->getEmails($accessToken)[0]->email;
            $this->promises[$query['state']]->resolve(["SUCCESS", ["authid" => $email]]);

        } else {
            $this->promises[$query['state']]->resolve(["FAILURE"]);
        }

        $response->writeHead(200, ['Content-Type' => 'text/html']);
        $response->end("<script>window.close();</script>");


    }

    /**
     * Get access token from code
     * 
     * @param string $code
     * @return string|boolean
     */
    private function getAccessToken($code)
    {

        $data        = [
            "client_id"     => $this->clientId,
            "client_secret" => $this->clientSecret,
            "code"          => $code
        ];
        $data_string = json_encode($data);

        //This needs to be replaced with Guzzle or something async
        $ch = curl_init('https://github.com/login/oauth/access_token');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string)
            ]
        );

        $result = curl_exec($ch);
        parse_str($result, $resultArray);

        if (isset($resultArray['access_token'])) {
            return $resultArray['access_token'];
        } else {
            return false;
        }
    }

    /**
     * Get list email from accesstokens
     * 
     * @param string $accessToken
     * @return mixed
     */
    private function getEmails($accessToken)
    {

        $ch = curl_init("https://api.github.com/user/emails?access_token={$accessToken}");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            ['Content-Type: application/json', 'User-Agent: Thruway-WAMP-App']
        );

        $result = curl_exec($ch);

        return json_decode($result);

    }

}