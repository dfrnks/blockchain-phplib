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
     * @var \Closure
     */
    private $onConnect;
    
    /**
     * @var \Closure
     */
    private $onMessage;
    
    public function __construct($ip = "127.0.0.1", $port = 2346, $process = 4) {
        $GLOBALS["proccess_qtd"] = $process;
    
        $this->worker = new Worker("websocket://{$ip}:{$port}");
        $this->worker->count = $process;
        $this->worker->name = "WS Server";
        $this->worker->onConnect = function (ConnectionInterface $connection) {
            $this->connections[] = $connection;
        };
        
        $this->worker->onWorkerStart = function () {
            Channel::connect();
    
            $global = new Blockchain('127.0.0.1:2207');
            $global->info = "bla;bla";
    
            Channel::on("sendAll", function($event_data) {
                var_dump($event_data);
                
                if ($event_data["id"] === -1 || $this->worker->id === $event_data["id"]) {
                    $this->sendAll($event_data["data"]);
                }
            });
    
            Channel::on("connect", function($event_data) {
                var_dump($event_data);
                
                if ($event_data["id"] === -1 || $this->worker->id === $event_data["id"]) {
                    $this->connect($event_data["data"][0], $event_data["data"][1]);
                }
            });
        };
        
        $this->worker->onMessage = function (ConnectionInterface $connection, $data) {
            $this->onMessage($connection, $data);
        };
    }
    
    public function onMessage(ConnectionInterface $connection, $data) : void {
        var_dump($data);
    }
    
    public function connect($ip, $port) : void {
        $connection = new AsyncTcpConnection("ws://{$ip}:{$port}");
    
        $connection->onClose = function () use ($connection){
            echo "Connection closed\n";
            $connection->reconnect(5);
        };
    
        $connection->onMessage = function (ConnectionInterface $connection, $data) {
            $this->onMessage($connection, $data);
        };
    
        $connection->connect();
    
        $this->connections[] = $connection;
    }
    
    public function sendAll($data) : void {
        foreach ($this->connections as $connection) {
            $connection->send(json_encode($data));
        }
    }
}