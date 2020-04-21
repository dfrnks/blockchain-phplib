<?php

namespace Blockchain\Network;

use Blockchain\Client as Blockchain;
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
    
    private $peers = [];
    
    private $chain = [];
    
    public function __construct($ip = "127.0.0.1", $port = 2346, $process = 4) {
        $GLOBALS["process_qtd"] = $process;
    
        $this->worker = new Worker("websocket://{$ip}:{$port}");
        $this->worker->count = $process;
        $this->worker->name = "WS Server";
        $this->worker->onWorkerStart = function () {
            $this->bchain = new Blockchain('127.0.0.1:2207');
            
            Channel::connect();
    
            if ($this->worker->id == 0) {
                foreach ($this->peers as $peer) {
                    $this->connect($peer);
                }
            }
            
            Channel::on("sendAll", function($event_data) {
                $this->sendAll($event_data["data"]);
            });
    
            Channel::on("connect", function($event_data) {
                if ($this->worker->id === $event_data["id"]) {
                    $this->connect($event_data["data"]);
                }
            });
    
            Channel::on("addBlock", function($event_data) {
                if ($this->worker->id === $event_data["id"]) {
                    $this->log("Novo bloco para adicionar");
                    
                    $chain = $this->bchain->addBlock($event_data["data"]);
    
                    $this->syncChain($chain);
                    
                    Channel::publish("syncChain", $chain);
                }
            });
    
            Channel::on("syncChain", function($event_data) {
                $this->syncChain();
            });
        };
    
        $this->worker->onConnect = function (ConnectionInterface $connection) {
            $this->log("Novo peer connected");
            
            $this->connections[] = $connection;
    
            $this->syncChain();
        };
        
        $this->worker->onMessage = function (ConnectionInterface $connection, $data) {
            $this->onMessage($connection, $data);
        };
    }
    
    /**
     * @param $address
     * @return WebSocket
     */
    public function setAddress($address): WebSocket {
        $this->address = $address;
        
        return $this;
    }
    
    /**
     * @param $peer
     * @return WebSocket
     */
    public function setPeerConnect($peer): WebSocket {
        $this->peers[] = $peer;

        return $this;
    }
    
    /**
     * @param ConnectionInterface $connection
     * @param $buffer
     * @throws \Exception
     */
    public function onMessage(ConnectionInterface $connection, $buffer) : void {
        $this->log($buffer);
        
        $data = @unserialize($buffer);
        
        if (isset($data["myaddress"])) {
            $this->bchain->addPeer($data["myaddress"]);
            return;
        }
    
        if (isset($data["newchain"])) {
            $this->bchain->setChain($data["newchain"]);
            return;
        }
    
        if (isset($data["peers"])) {
            $mypeers = $this->bchain->getPeers();
            
            foreach ($data["peers"] as $key => $peer) {
                foreach ($mypeers as $mypeer) {
                    if ($peer == $mypeer) {
                        unset($data["peers"][$key]);
                    }
                }
            }
    
            foreach ($data["peers"] as $key => $peer) {
                Channel::sendOneRandon("connect", $peer);
            }
            
            return;
        }
    
    
        var_dump($data);
    }
    
    /**
     * @param $ip
     * @param $port
     * @throws \Exception
     */
    public function connect($address) : void {
        $connection = new AsyncTcpConnection("ws://{$address}");
    
        $connection->onClose = function () use ($connection){
            $this->log("Connection closed " .  $connection->getRemoteAddress());
            
            $connection->reconnect(5);
        };
    
        $connection->onMessage = function (ConnectionInterface $connection, $data) {
            $this->onMessage($connection, $data);
        };
    
        $connection->onConnect = function (ConnectionInterface $connection) {
            $connection->send(serialize(["myaddress" => $this->address]));
            $connection->send(serialize(["newchain" => $this->bchain->getChain()]));
            $connection->send(serialize(["peers" => $this->bchain->getPeers()]));
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
    
    public function syncChain(array $chain = []) : void {
        if (!$this->connections) {
            return;
        }
        
        $chain = $chain ? : $this->bchain->getChain();
    
        if (count($this->chain) >= count($chain)) {
            return;
        }
        
        $this->chain = $chain;
        
        $this->log("Sincronizando chain");
        
        foreach ($this->connections as $connection) {
            $connection->send(serialize(["newchain" => $chain]));
        }
    }
    
    public function log($msg) {
        if (is_array($msg)) {
            $msg = json_encode($msg);
        }
        
        $date = date("c");
        
        Worker::log("id[{$this->worker->id}] [{$date}] {$msg}");
    }
}