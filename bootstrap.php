<?php

use Blockchain\App;
use Blockchain\Network\Channel;
use GlobalData\Client;

require_once __DIR__ . '/vendor/autoload.php';

$app = new App();
$app->get("/", function (Client $global, $get) {
    
    var_dump($global->info);
    
    return ["teste"];
});

$app->get("/teste", function (Client $global, $get) {
    Channel::sendAll("sendAll", ["message" => $get["message"] ?:"foda-se, funcionou!"]);
    
    return ["teste"];
});

$app->get("/connect", function (Client $global, $get) {
    Channel::sendOneRandon("connect", ["127.0.0.1", "2346"]);
    
    return ["teste"];
});