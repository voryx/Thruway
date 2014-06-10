<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/9/14
 * Time: 5:18 PM
 */

namespace AutobahnPHP\Transport;


use AutobahnPHP\AbstractPeer;
use AutobahnPHP\Peer;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

class RatchetTransport extends AbstractTransport {

    private $peer;
    private $address;
    private $port;

    function __construct(AbstractPeer $peer, $address = "127.0.0.1", $port = 8080) {
        $this->peer = $peer;
        $this->port = $port;
        $this->address = $address;
    }

    public function startTransport() {
        $ws = new WsServer(new RatchetServer($this->peer));
        $ws->disableVersion(0);

        $server = IoServer::factory(new HttpServer($ws), $this->port, $this->address);
        $server->run();
    }
} 