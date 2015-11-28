## Connection Object

The Connection object is designed to match the use of the Connection object in [AutobahnJS](http://autobahn.ws/js/).

The Connection object is just a wrapper for the [Client](Client.md) object with a PawlTransportProvider.

Use of the Connection object is not recommended and will likely be removed in future versions.

#### Typical use of Connection object:
```php
$conn = new Connection(
    [
        "url"   => "ws://127.0.0.1:9090/ws",
        "realm" => "realm1";
    ],
    $loop);

$conn->on('open', function (ClientSession $session, TransportInterface $transport, stdClass $details) {
                     // This is where you do your stuff
                 });

$conn->open();
```

## API
##### public function __construct(Array $options, LoopInterface $loop = null)
name | type | use
--- | --- | ---
$options | array | associative array used to set connection options
$loop | LoopInterface | The loop the Connection will run on

Options:

name | type | use
--- | --- | ---
realm | string | The realm the client will run on
url | string | The URL the client will connect to
authid | string | The authid to authenticate with
authmethods | array | List of string authentication methods supported
onClose | callable | Function to call on session close
onChallenge | callable | Function that gets called on challenge

onClose function is simply subscribed to the 'close' event. See below for more info.

onChallenge function signature: ```function ($session, $authmethod, $msg)```

name | type | use
--- | --- | ---
$session | ClientSession | The session being established
$authmethod | string | The authentication method requested by the router
$msg | ChallengeMessage | The challenge message sent from the router

The onChallenge function returns a string signature in response to the challenge.


##### public function open($startLoop = true)
Calls start($startLoop) on the underlying client object.

##### public function close()
Closes the transport and disables connection retry.

##### public function getClient()
Get the underlying Client object

## Events
The Connection object emits all the events that the Client emits. Please see [Client](Client.md) for more info.