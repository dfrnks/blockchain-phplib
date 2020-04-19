<?php

use Blockchain\Network\WebSocket;
use Blockchain\Network\WebSocketConnection;

require_once __DIR__ . '/bootstrap.php';

$ws = new WebSocket("127.0.0.1", 2346);

$ws->onConnect(function (WebSocketConnection $connection) use ($ws) {
    echo "Foi, conectou alguem ai\n";
    
    $connection->send(["Vai para você 1"]);
});

$ws->onMessage(function (WebSocketConnection $connection, $data) use ($ws) {
//    if (is_object($data) && $data->type == "connectaai") {
//        $ws->connect("127.0.0.1", "2348");
//    }
    var_dump($data);
    
//    $connection->send(["message" => "ok"]);
});

$ws->onStart(function () use ($ws) {
    Channel\Client::connect('0.0.0.0', 2446);
    
    Channel\Client::on("sendAll", function($event_data) use ($ws) {
        $ws->sendAll($event_data);
    });
    
    Channel\Client::on("connect", function($event_data) use ($ws) {
        $ws->connect($event_data[0], $event_data[1]);
    });
});

$ws->run();
