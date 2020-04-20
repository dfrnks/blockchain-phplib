<?php

namespace Blockchain\Network;

use Blockchain\App;
use Workerman\Connection\ConnectionInterface;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Worker;

class HttpServer {
    /**
     * @var Worker
     */
    private $worker;
    
    public function __construct(App $app, $ip = "127.0.0.1", $port = 8000, $proccess = 4) {
        $this->worker = new Worker("http://{$ip}:{$port}");
        $this->worker->count = $proccess;
        $this->worker->name = "HTTP Server";
        $this->worker->onMessage = function (ConnectionInterface $connection, Request $request) use ($app) {
            [$status, $header, $body] = $app->exec(
                $request->method(),
                $request->path(),
                $request->get(),
                json_decode($request->rawBody(), true) ? : $request->post()
            );
            
            $connection->send(new Response($status, $header, $body));
        };
    }
}