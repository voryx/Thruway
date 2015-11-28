# 0.4 Client API

The Thruway client library is can be used in 2 different ways: the [Client](Client.md) object or the
[Connection](Connection.md) object.

>In the code examples, $loop refers to an instance of the [React\EventLoop](https://github.com/reactphp/event-loop).
>Namespaces are typically only used when there is ambiguity. Unless otherwise stated, code is not provided in complete
>and runnable state, just snippets.

The Client and Connection object are used to establish sessions with a router. The interesting things with the library
usually happen with the [ClientSession](ClientSession.md) object after the session is established.