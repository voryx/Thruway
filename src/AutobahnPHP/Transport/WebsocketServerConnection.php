<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/8/14
 * Time: 11:17 PM
 */

namespace AutobahnPHP\Transport;


class WebsocketServerConnection
{
    const STATE_HEADER_READ = 1;
    const STATE_RESPONSE_SENT = 2;
    const STATE_WAMP = 3;

    private $state;

    private $buffer;

    private $reactConn;

    function __construct($reactConn)
    {
        $this->reactConn = $reactConn;

        $this->state = static::STATE_HEADER_READ;
        $this->buffer = "";
    }

    public function onData($data)
    {
        $this->buffer .= $data;

        switch ($this->state) {
            case static::STATE_HEADER_READ:
                $headerEnd = strpos($this->buffer, "\r\n\r\n");
                if ($headerEnd !== FALSE) {
                    $headerRaw = substr($this->buffer, 0, $headerEnd);

                }
                break;
            case static::STATE_RESPONSE_SENT:
            default:
                echo "Unknown state.\n";

        }
    }

    public function onClose()
    {

    }
}