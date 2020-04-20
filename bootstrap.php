<?php

use Blockchain\App;
use Blockchain\Client as Blockchain;
use Blockchain\Network\Channel;

require_once __DIR__ . '/vendor/autoload.php';

$app = new App();
$app->get("/", function (Blockchain $global, $get) {
    
    var_dump($global->info);
    
    return ["teste"];
});

$app->get("/teste", function (Blockchain $global, $get) {
    Channel::sendAll("sendAll", ["message" => $get["message"] ?:"foda-se, funcionou!"]);
    
    return ["teste"];
});

$app->get("/connect", function (Blockchain $global, $get) {
    Channel::sendOneRandon("connect", ["127.0.0.1", "2346"]);
    
    return ["teste"];
});