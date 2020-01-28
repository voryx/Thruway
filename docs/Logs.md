#### Logs

Sometimes you need use this package with some framework, or web requests, so,
for more comfortable and no unexpected messages, you like to suppress logs about connection, send, messages...

before use any methods.

```php
use Psr\Log\NullLogger;
use Thruway\Logging\Logger;

Logger::set(new NullLogger());
```
