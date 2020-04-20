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
    
    public function __construct($ip = "127.0.0.1", $port = 2346, $proccess = 4) {
        new Channel($ip, $port + 100);
        
        $this->worker = new Worker("websocket://{$ip}:{$port}");
        $this->worker->count = $proccess;
        $this->worker->name = "WS Server";
        $this->worker->onConnect = function (ConnectionInterface $connection) {
            $wsconnection = new WebSocketConnection($connection);
            $wsconnection->onMessage($this->onMessage);
        
            call_user_func($this->onConnect, $wsconnection);
        
            $this->connections[] = $wsconnection;
        };
    }
    
    public function onStart(\Closure $function) : void {
        $this->worker->onWorkerStart = $function;
    }
    
    public function onConnect(\Closure $function) : void {
        $this->onConnect = $function;
    }
    
    public function onMessage(\Closure $function) : void {
        $this->onMessage = $function;
    }
    
    public function connect($ip, $port) : void {
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
    
    public function sendAll($data) : void {
        foreach ($this->connections as $connection) {
            $connection->send($data);
        }
    }
}