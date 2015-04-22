<?php


namespace Thruway\Exception;

use Thruway\Message\Traits\ArgumentsTrait;
use Thruway\Message\Traits\DetailsTrait;

class WampErrorException extends \Exception {
    use ArgumentsTrait;
    use DetailsTrait;

    private $errorUri;

    /**
     * @param string $errorUri
     * @param array|null $arguments
     * @param \stdClass|null $argumentsKw
     * @param \stdClass|null $details
     */
    function __construct($errorUri, $arguments = null, $argumentsKw = null, $details = null)
    {
        $this->setErrorUri($errorUri);
        $this->setArguments($arguments);
        $this->setArgumentsKw($argumentsKw);
        $this->setDetails($details);

        $exceptionMessage = $errorUri;
        if (is_array($arguments) && isset($arguments[0]) && is_string($arguments[0])) {
            $exceptionMessage = $arguments[0];
        }

        parent::__construct($exceptionMessage);
    }

    /**
     * @return mixed
     */
    public function getErrorUri()
    {
        return $this->errorUri;
    }

    /**
     * @param mixed $errorUri
     */
    public function setErrorUri($errorUri)
    {
        $this->errorUri = $errorUri;
    }


}