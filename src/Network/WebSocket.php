<?php

namespace Blockchain\Network;

use Blockchain\Client as Blockchain;
use Channel\Client as Channel;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Connection\ConnectionInterface;
use Workerman\Worker;

class WebSocket {
    
    /**
     * @var ConnectionInterface[]
     */
    private $connections = [];
    
    /**
     * @var Worker
     */
    private $worker;
    
    /**
     * @var Blockchain
     */
    private $bchain;
    
    private $address;
    
    public function __construct($ip = "127.0.0.1", $port = 2346, $process = 4) {
        $this->address = "{$ip}:{$port}";
        
        $GLOBALS["process_qtd"] = $process;
    
        $this->worker = new Worker("websocket://{$this->address}");
        $this->worker->count = $process;
        $this->worker->name = "WS Server";
        $this->worker->onWorkerStart = function () {
            Channel::connect();
    
            $this->bchain = new Blockchain('127.0.0.1:2207');
            $this->bchain->info = "bla;bla";
    
            Channel::on("sendAll", function($event_data) {
                if ($event_data["id"] === -1 || $this->worker->id === $event_data["id"]) {
                    $this->sendAll($event_data["data"]);
                }
            });
    
            Channel::on("connect", function($event_data) {
                if ($event_data["id"] === -1 || $this->worker->id === $event_data["id"]) {
                    $this->connect($event_data["data"][0], $event_data["data"][1]);
                }
            });
        };
    
        $this->worker->onConnect = function (ConnectionInterface $connection) {
            $this->connections[] = $connection;
        };
        
        $this->worker->onMessage = function (ConnectionInterface $connection, $data) {
            $this->onMessage($connection, $data);
        };
    }
    
    /**
     * @param ConnectionInterface $connection
     * @param $buffer
     * @throws \Exception
     */
    public function onMessage(ConnectionInterface $connection, $buffer) : void {
        $data = unserialize($buffer);
        
        if (isset($data["myaddress"])) {
            $this->bchain->addPeer($data["myaddress"]);
            return;
        }
        
        var_dump($data);
    }
    
    /**
     * @param $ip
     * @param $port
     * @throws \Exception
     */
    public function connect($ip, $port) : void {
        $connection = new AsyncTcpConnection("ws://{$ip}:{$port}");
    
        $connection->onClose = function () use ($connection){
            echo "Connection closed\n";
            $connection->reconnect(5);
        };
    
        $connection->onMessage = function (ConnectionInterface $connection, $data) {
            $this->onMessage($connection, $data);
        };
    
        $connection->onConnect = function (ConnectionInterface $connection) {
            $connection->send(serialize(["myaddress" => $this->address]));
        };
    
        $connection->connect();
    
        $this->connections[] = $connection;
        
        $this->bchain->addPeer($connection->getRemoteAddress());
    }
    
    public function sendAll($data) : void {
        foreach ($this->connections as $connection) {
            $connection->send(json_encode($data));
        }
    }
}