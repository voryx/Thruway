<?php

class AbortAfterHelloWithDetailsAuthProviderClient extends \Thruway\Authentication\AbstractAuthProviderClient {
    /**
     * @return string
     */
    public function getMethodName()
    {
        return 'abortafterhellowithdetails';
    }

    public function processHello(array $args) {
        return [
            "FAILURE",
            [
                "abort_uri" => "my.custom.abort.uri",
                "details" => [
                    "message" => "My custom abort message"
                ]
            ]
        ];
    }

} 