<?php

namespace Blockchain\Network;

use Workerman\Connection\ConnectionInterface;

class WebSocketConnection {
    private $connection;
    
    private $onConnect;
    
    public function __construct(ConnectionInterface $connection) {
        $this->connection = $connection;
    }
    
    public function onConnect($function) {
        $this->connection->onConnect = function (ConnectionInterface $connection) use ($function) {
            call_user_func($function, $this);
        };
    }

    public function onMessage($function) {
        $this->connection->onMessage = function (ConnectionInterface $connection, $data) use ($function) {
            $data = json_decode($data) ? : $data;
    
            call_user_func($function, $this, $data);
        };
    }
    
    public function send(array $data) {
        $this->connection->send(json_encode($data));
    }
    
}