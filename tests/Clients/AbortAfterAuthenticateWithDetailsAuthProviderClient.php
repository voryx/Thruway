<?php

class AbortAfterAuthenticateWithDetailsAuthProviderClient extends \Thruway\Authentication\AbstractAuthProviderClient {
    /**
     * @return string
     */
    public function getMethodName()
    {
        return 'abortafterauthenticatewithdetails';
    }

    public function preProcessAuthenticate(array $args) {
        return [
            "FAILURE",
            [
                "abort_uri" => "my.custom.abort.uri.2",
                "details" => [
                    "message" => "My custom abort message 2"
                ]
            ]
        ];
    }

} 