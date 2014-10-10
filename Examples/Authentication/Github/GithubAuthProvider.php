<?php


/**
 * Class GithubAuthProvider
 * 
 * @see https://developer.github.com/v3/oauth/
 */
class GithubAuthProvider extends \Thruway\Authentication\AbstractAuthProviderClient
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
     * Constructor
     * 
     * @param array $authRealms
     * @param \React\EventLoop\LoopInterface $clientId
     * @param $clientSecret
     */
    public function __construct(Array $authRealms, $clientId, $clientSecret)
    {
        $this->clientId     = $clientId;
        $this->clientSecret = $clientSecret;

        parent::__construct($authRealms);
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
     * @param mixed $code
     * @param mixed $extra
     * @return array
     */
    public function processAuthenticate($code, $extra = null)
    {

        if (isset($code)) {
            $data        = [
                "client_id"     => $this->clientId,
                "client_secret" => $this->clientSecret,
                "code"          => $code
            ];
            $data_string = json_encode($data);

            //Replace with Guzzle example
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
                return [
                    "SUCCESS",
                    ["authid" => "anonymous"]
                ];

            } else {
                return ["FAILURE"];
            }
        } else {
            return ["FAILURE"];
        }

    }

}