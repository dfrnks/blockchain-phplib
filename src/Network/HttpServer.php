<?php

namespace Blockchain\Network;

use Blockchain\App;
use GlobalData\Client;
use Workerman\Connection\ConnectionInterface;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Worker;

class HttpServer {
    /**
     * @var Worker
     */
    private $worker;
    
    /**
     * @var Client
     */
    private $global;
    
    public function __construct(App $app, $ip = "127.0.0.1", $port = 8000, $proccess = 1) {
        $this->worker = new Worker("http://{$ip}:{$port}");
        $this->worker->count = $proccess;
        $this->worker->name = "HTTP Server";
        $this->worker->onWorkerStart = function () {
            Channel::connect();
    
            $this->global = new Client('127.0.0.1:2207');
        };
    
        $this->worker->onMessage = function (ConnectionInterface $connection, Request $request) use ($app) {
            [$status, $header, $body] = $app->exec(
                $request->method(),
                $request->path(),
                $request->get(),
                json_decode($request->rawBody(), true) ? : $request->post(),
                $this->global
            );
        
            $connection->send(new Response($status, $header, $body));
        };
    
    }
}