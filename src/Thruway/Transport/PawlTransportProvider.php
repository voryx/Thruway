<?php
/**
 * User: Rottenwood, based on matt
 * Date: 05-07-2014
 */

namespace Thruway\Transport;

use Thruway\ManagerDummy;
use Thruway\ManagerInterface;
use Thruway\Peer\AbstractPeer;
use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use Ratchet\Client\WebSocket;
use React\EventLoop\LoopInterface;

/**
 * Class WebsocketClient
 * @package Thruway\Transport
 */
class PawlTransportProvider extends AbstractTransportProvider implements EventEmitterInterface
{
    use EventEmitterTrait;

    /**
     * @var AbstractPeer
     */
    private $peer;

    /**
     * @var string
     */
    private $URL;

    /**
     * @var \React\EventLoop\ExtEventLoop|\React\EventLoop\LibEventLoop|\React\EventLoop\LibEvLoop|\React\EventLoop\StreamSelectLoop
     */
    private $loop;

    /**
     * @var \Ratchet\Client\Factory
     */
    private $connector;

    /**
     * @var ManagerInterface
     */
    private $manager;


    function __construct($URL = "ws://127.0.0.1:9090/")
    {

        $this->peer = null;

        $this->URL = $URL;

        $this->manager = new ManagerDummy();

    }

    /**
     *
     */
    public function startTransportProvider(AbstractPeer $peer, LoopInterface $loop)
    {
        echo "Запуск транспортного процесса\n";

        $this->peer = $peer;

        $this->loop = $loop;

        $this->connector = new \Ratchet\Client\Factory($this->loop);

        $this->connector->__invoke($this->URL, ['wamp.2.json'])->then(
            function (WebSocket $conn) {

                echo "Соединение с сервером установлено\n";

                $transport = new PawlTransport($conn);

                $this->peer->onOpen($transport);

                $conn->on(
                    'message',
                    function ($msg) use ($transport) {
                        echo "Получено: {$msg}\n";
                        $this->peer->onRawMessage($transport, $msg);
                    }
                );

                $conn->on(
                    'close',
                    function ($conn) {
                        echo "Соединение с сервером закрыто\n";
                        $this->peer->onClose('close');
                    }
                );
            },
            function ($e) {
                $this->peer->onClose('unreachable');
                echo "Не могу установить соединение: {$e->getMessage()}\n";
                // $this->loop->stop();
            }
        );

    }


    /**
     * @return AbstractPeer
     */
    public function getPeer()
    {
        return $this->peer;
    }


    /**
     * @param AbstractPeer $peer
     */
    public function setPeer(AbstractPeer $peer)
    {
        $this->peer = $peer;
    }

    /**
     * @param \Thruway\ManagerInterface $manager
     */
    public function setManager($manager)
    {
        $this->manager = $manager;

        $this->manager->logInfo("Manager attached to PawlTransportProvider");
    }

    /**
     * @return \Thruway\ManagerInterface
     */
    public function getManager()
    {
        return $this->manager;
    }



}