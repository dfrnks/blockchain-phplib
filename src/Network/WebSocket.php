<?php

namespace Blockchain\Network;

use Channel\Server as Channel;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Connection\ConnectionInterface;
use Workerman\Worker;

class WebSocket {
    /**
     * @var WebSocketConnection[]
     */
    private $connections = [];
    
    private $worker;
    
    private $onConnect;
    
    private $onMessage;
    
    public function __construct($ip = "127.0.0.1", $port = 2346, $proccess = 4) {
        new Channel($ip, $port + 100);
        
        $this->worker = new Worker("websocket://{$ip}:{$port}");
        $this->worker->count = $proccess;
    }
    
    public function onStart($function) {
        $this->worker->onWorkerStart = $function;
    }
    
    public function onConnect($function) {
        $this->onConnect = $function;
    }
    
    public function onMessage($function) {
        $this->onMessage = $function;
    }
    
    public function connect($ip, $port) {
        $ws_connection = new AsyncTcpConnection("ws://{$ip}:{$port}");
        $ws_connection->onConnect = function (ConnectionInterface $connection) {
            $wsconnection = new WebSocketConnection($connection);
            $wsconnection->onConnect($this->onConnect);
            $wsconnection->onMessage($this->onMessage);
    
            $this->connections[] = $wsconnection;
        };
    
        $ws_connection->onClose = function (ConnectionInterface $connection) use ($ws_connection){
            echo "Connection closed\n";
            $ws_connection->reconnect(5);
        };
        
        $ws_connection->connect();
    }
    
    public function sendAll($data) {
        foreach ($this->connections as $connection) {
            $connection->send($data);
        }
    }
    
    public function run() {
        $this->worker->onConnect = function (ConnectionInterface $connection) {
            $wsconnection = new WebSocketConnection($connection);
            $wsconnection->onMessage($this->onMessage);
    
            call_user_func($this->onConnect, $wsconnection);
            
            $this->connections[] = $wsconnection;
        };
        
        \Workerman\Worker::runAll();
    }
}