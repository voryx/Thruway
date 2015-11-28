## ClientSession

The ClientSession is created by the client to represent the current session. If a connection is lost, the
Client will destroy the ClientSession and recreate a new one on reconnect (if retrying is enabled). A reference to the
ClientSession can be retrieved from the Client in the 'open' event, by overriding the onSessionStart method
in a class that extends the Client class, or by using the getSession method of the Client.

Once the session has been established, it can be used to perform WAMP actions:

##### public function publish($topicName, $arguments = null, $argumentsKw = null, $options = null)
Used to publish events.

name | type | use
--- | --- | ---
$topicName | string | the URI to publish to
$arguments | array | The arguments list
$arguments | object or array | Keyword arguments - this will be cast to an object
$options | array or object | publish options

Options are cast to an object and passed to the router unaltered. If $options->acknowledge is set to true, publish
returns a promise. Otherwise, publish returns false.

##### public function subscribe($topicName, callable $callback, $options = null)
Used to subscribe to a URI.

name | type | use
--- | --- | ---
$topicName | string | The URI (or pattern) being subscribed to
$callable | callable | The function that will receive the notifications when events are received from the router
$options | array or object | subscribe options

$options is passed through to the router after being cast to an object.

Subscribe returns a promise. On resolution the SubscribedMessage is passed back. The promise fails if the router
reports an error.

The callable has the signature: ```function ($args, $argsKw, $details, $publicationId)```

name | type | use
--- | --- | ---
$args | array | The list arguments
$argsKw | array | The keyword arguments
$details | object | The details sent from the router
$publicationId | int | Publication ID

##### Unsubscribe

Not implemented.

##### public function register($procedureName, callable $callback, $options = null)
Used to register an RPC endpoint.

name | type | use
--- | --- | ---
$procedureName | string | The URI to register on
$callback | callback | The function that will be handling the RPC requests
$options | array or object | Regsiter options

$options is passed through to the router after being cast to an object.

The callable has the signature: ```function ($args, $argsKw, $details)```

name | type | use
--- | --- | ---
$args | array | The list arguments
$argsKw | array | The keyword arguments
$details | object | The details sent from the router

The callable can return a value, a Result object, a promise, or throw an exception.

* Value: if the callable returns a list array, it will be expanded into the $args sent back to the caller.
* As a Result object: The result object allows complete control of what message is sent back to the caller.
* Thrown Exception: If an exception is thrown, this will send an error message back to the caller.
** A special WampErrorException can be used to control the ErrorMessage sent back with greater detail.
* Promise: If a promise is returned, nothing is sent back to the caller until resolve, progress, or reject is called. A value or result object can be sent back with resolve and progress. Rejecting the promise sends an ErrorMessage to the caller (again, you can resolve with WampErrorException to control the error message)

##### public function unregister($procedureName)
Used to unregister an RPC endpoint.

name | type | use
--- | --- | ---
$procedureName | string | The URI to register on

This returns a Promise that resolves on success or rejects if there is an error.

##### public function call($procedureName, $arguments = null, $argumentsKw = null, $options = null)
Send a call request to the router.

name | type | use
--- | --- | ---
$procedureName | string | The URI being called
$arguments | array | The list arguments
$argumentsKw | array or object | The keyword arguments
$options | array or object | Call options

$options is passed through to the router after being cast to an object.

Returns a Promise. The promise resolves with a CallResult object. CallResult can be used as an array to access the
list arguments returned by the router and can also be used to get more detailed information (arguments, argumentsKw,
details, as well as the actual ResultMessage sent from the router).