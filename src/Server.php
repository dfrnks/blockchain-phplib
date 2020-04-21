<?php

namespace Blockchain;

use Workerman\Connection\ConnectionInterface;
use Workerman\Worker;

class Server {
    
    /**
     * Worker instance.
     * @var worker
     */
    protected $worker;
    
    /**
     * All data.
     * @var array
     */
    protected $dataArray = [];
    
    /**
     * @var Block[]
     */
    protected $chain = [];
    
    /**
     * @var array 
     */
    protected $peers = [];
    
    public function __construct($ip = "0.0.0.0", $port = 2207) {
        $worker = new Worker("frame://$ip:$port");
        $worker->count = 1;
        $worker->name = "Block chain";
        $worker->onMessage = array($this, "onMessage");
        $worker->reloadable = false;
        
        $worker->onWorkerStart = function () {
            if (file_exists("chain.bk")) {
                $this->chain = unserialize(file_get_contents("chain.bk"));
            }
        };
        
        $worker->onWorkerStop = function () {
            file_put_contents("chain.bk", serialize($this->chain));
        };
        
        $this->worker = $worker;
    }
    
    /**
     * @param ConnectionInterface $connection
     * @param $buffer
     * @return bool|void
     */
    public function onMessage(ConnectionInterface $connection, $buffer) {
        if($buffer === "ping") {
            return;
        }
        
        $data = unserialize($buffer);
        
        if(!$buffer || !isset($data["cmd"]) || !isset($data["type"])) {
            $connection->close(serialize("bad request"));
            return;
        }
        
        $type = $data["type"];
        
        switch ($type) {
            case "data":
                $this->data($connection, $data);
                break;
            case "peer":
                $this->peer($connection, $data);
                break;
            case "chain":
                $this->chain($connection, $data);
                break;
        }
        
        return;
    }
    
    /**
     * @param ConnectionInterface $connection
     * @param $data
     * @return bool|void
     */
    protected function data(ConnectionInterface $connection, $data) {
        if(!isset($data["cmd"]) || !isset($data["key"])) {
            $connection->close(serialize("bad request"));
            return;
        }
        
        $cmd = $data["cmd"];
        $key = $data["key"];
    
        switch($cmd) {
            case "get":
                if(!isset($this->dataArray[$key])) {
                    return $connection->send("N;");
                }
            
                return $connection->send(serialize($this->dataArray[$key]));
                break;
            case "set":
                $this->dataArray[$key] = $data["value"];
                $connection->send("b:1;");
                break;
            case "add":
                if(isset($this->dataArray[$key])) {
                    return $connection->send("b:0;");
                }
            
                $this->dataArray[$key] = $data["value"];
            
                return $connection->send("b:1;");
            
                break;
            case "increment":
                if(!isset($this->dataArray[$key])) {
                    return $connection->send("b:0;");
                }
            
                if(!is_numeric($this->dataArray[$key])) {
                    $this->dataArray[$key] = 0;
                }
            
                $this->dataArray[$key] = $this->dataArray[$key] + $data["step"];
            
                return $connection->send(serialize($this->dataArray[$key]));
                break;
            case "cas":
                $old_value = !isset($this->dataArray[$key]) ? null : $this->dataArray[$key];
            
                if(md5(serialize($old_value)) === $data["md5"]) {
                    $this->dataArray[$key] = $data["value"];
                    return $connection->send("b:1;");
                }
            
                $connection->send("b:0;");
                break;
            case "delete":
                unset($this->dataArray[$key]);
                $connection->send("b:1;");
                break;
            default:
                $connection->close(serialize("bad cmd ". $cmd));
        }
    }
    
    /**
     * @param ConnectionInterface $connection
     * @param $data
     * @return bool|void
     */
    protected function peer(ConnectionInterface $connection, $data) {
        if(!isset($data["cmd"])) {
            $connection->close(serialize("bad request"));
            return;
        }
        
        $cmd = $data["cmd"];
    
        switch($cmd) {
            case "get":
                return $connection->send(serialize($this->peers));
                break;
            case "add":
                $this->peers[] = $data["peer"];
                return $connection->send("b:1;");
                break;
        }
    }
    
    /**
     * @param ConnectionInterface $connection
     * @param $data
     * @return bool|void
     */
    protected function chain(ConnectionInterface $connection, $data) {
        if(!isset($data["cmd"])) {
            $connection->close(serialize("bad request"));
            return;
        }
        
        $cmd = $data["cmd"];
        
        switch($cmd) {
            case "get":
                return $connection->send(serialize($this->chain));
                break;
            case "set":
                $this->replaceChain($data["chain"]);
                
                return $connection->send(serialize($this->chain));
                break;
            case "getLast":
                return $connection->send(serialize(end($this->chain)));
                break;
            case "add":
                /**
                 * @var Block $block
                 */
                $block = $data["block"];
                
                if (!$block->isValid()) {
                    $connection->send("b:0;");
                } else {
                    /**
                     * @var Block $lastBlock
                     */
                    $lastBlock = end($this->chain);
                    
                    if (empty($lastBlock) || $block->getLastHash() == $lastBlock->getHash()) {
                        $this->chain[] = $data["block"];
                        
                        $connection->send("b:1;");
                    } else {
                        $connection->send("b:0;");
                    }
                }
                
                break;
        }
    }
    
    protected function replaceChain($chain) {
        if (count($this->chain) > count($chain)) {
            return false;
        } elseif (!$this->isValidChain($chain)) {
            return false;
        }
        
        $this->chain = $chain;
        
        return true;
    }
    
    /**
     * @param Block[] $chain
     * @return bool
     */
    protected function isValidChain($chain) {
        if (!empty($this->chain) && $chain[0] !== $this->chain[0]) {
            return false;
        }
        
        foreach ($chain as $key => $block) {
            if ($key > 0) {
                $lastBlock = $chain[$key - 1];
                $lastHash = $lastBlock->getHash();
            } else {
                $lastHash = "--";
            }
            
            if ($block->getLastHash() != $lastHash) {
                return false;
            }
            
            if (!$block->isValid()) {
                return false;
            }
            
            if ($block->hash() !== $block->getHash()) {
                return false;
            }
        }
        
        return true;
    }
}