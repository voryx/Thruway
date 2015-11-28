## Client Object
The Client object can be used as-is or can be extended.

#### Typical As-is Client Object use:

```php
$client = new Client($realmName, $loop);

$client->addTransportProvider(new PawlTransportProvider('ws://127.0.0.1:9090/ws'));

$client->on('open', function (ClientSession $session, TransportInterface $transport, stdClass $details) {
    // This is where you do your stuff
});

$client->start();
```

#### Typical use of Client by extending
```php
class MyClient extends Client {
    public function onSessionStart(ClientSession $session, TransportInterface $transport, stdClass $details) {
        // This method is exactly the same as the above on('open')
        // it is actually called by the event emitter
    }
}

$client = new MyClient($realmName, $loop);

$client->addTransportProvider(new PawlTransportProvider('ws://127.0.0.1:9090/ws'));

$client->start();
```
#### Public Methods
##### public function __construct($realm, LoopInterface $loop = null)
name | type | use
--- | --- | ---
$realm | string | The name of the realm
$loop | LoopInterface | This defaults to null, in which case the client will use the React EventLoop Factory to create
its own loop.
##### public function onSessionStart(ClientSession $session, TransportInterface $transport, $details)
This function is called when a session has been negotiated with the router. It is meant to be overridden for classes
that extend Client.

name | type | use
--- | --- | ---
$session | ClientSession | The newly created client session
$transport | TransportInterface | This is the transport that the session is running on
$details | object | This is the details that were returned by the WelcomeMessage

##### public function addTransportProvider(ClientTransportProviderInterface $transportProvider)
This function is used to add a transport provider. This can only be called once.

name | type | use
--- | --- | ---
$transportProvider | ClientTransportProviderInterface |

##### public function setReconnectOptions($reconnectOptions)
Sets reconnectOptions. The values given are merged with the current values.

name | type | use
--- | --- | ---
$reconnectOptions | array | options
Available Options:
Option | Default | Meaning
--- | --- | ---
"max_retries" | 15 | The number of retry attempts to make
"initial_retry_delay" | 1.5 | Seconds before first retry
"max_retry_delay" | 300 | Maximum delay in seconds
"retry_delay_growth" | 1.5 | Multiplier on subsequent retries
"retry_delay_jitter" | 0.1 | Not implemented

##### public function addClientAuthenticator(ClientAuthenticationInterface $ca)
Sets the client authenticator which is used to authenticate the client session
name | type | use
--- | --- | ---
$ca | ClientAuthenticationInterface | authenticator

##### public function start($startLoop = true)
Starts the client

name | type | use
--- | --- | ---
$startLoop | bool | If true, start will execute the run method on the loop

##### public function onSessionEnd(ClientSession $session)
This is called when the session ends. This method is meant to be overridden by objects that extend Client

name | type | use
--- | --- | ---
$session | ClientSession | The session that has ended

##### public function setAttemptRetry($attemptRetry)
Set whether or not the Client should attempt a retry on connection close.

name | type | use
--- | --- | ---
$attemptRetry | bool | true to retry, false to not retry

##### public function getLoop()
Get the event loop that the client is running on.

##### public function setAuthId($authId)
Set the authid used to authenticate the session.

name | type | use
--- | --- | ---
$authid | string | authentication id

##### public function getAuthId()
Gets the authid used for the session

##### public function setAuthMethods(array $authMethods)
Sets supported authentication methods. If using an authentication provider, this is set automatically.

name | type | use
--- | --- | ---
$authMethods | array | an array of strings of supported authentication methods

##### public function getSession()
Gets the current ClientSession or null if not connected.

##### public function getRealm()
Gets the realm (string) of the client

## Events

##### open
Emitted when a session starts.

Handler function signature: ```function (ClientSession $session, TransportInterface $transport, stdClass $details)```

name | type | use
--- | --- | ---
$session | ClientSession | The new ClientSession
$transport | TransportInterface | The transport the session is running on
$details | object | Details from the WelcomeMessage

##### error
Emitted when the connection has been aborted, there is protocol error, or if there is a connection error in the
underlying transport.

Handler function signature: ```function ($errorUri)```

name | type | use
--- | --- | ---
$errorUri | string | The URI of the error (if available)

##### challenge
Emitted during session negotiation if there is a Challenge issued by the router.

The client can send a signature back to the router by constructing the AuthenticateMessage and sending it directly.
This is not necessary when using a ClientAuthenticator.

Handler function signature: ```function (ClientSession $session, ChallengeMessage $msg)```

name | type | use
--- | --- | ---
$session | ClientSession | The session being negotiated
$msg | ChallengeMessage | The ChallengeMessage sent by the router

>Note: If you are using this for authentication, you need to set the authid, and authmethods on the Client prior
>to starting the client.

##### close
Called when the session closes.

Handler function signature: ```function ($reason)```

name | type | use
--- | --- | ---
$reason | string | The reason if closed due to GoodbyeMessage
