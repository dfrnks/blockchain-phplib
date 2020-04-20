<?php

use Blockchain\App;
use Blockchain\Network\Channel;

require_once __DIR__ . '/vendor/autoload.php';

$app = new App();
$app->get("/", function ($get) {
    return ["teste"];
});

$app->get("/teste", function ($get) {
    Channel::sendAll("sendAll", ["message" => $get["message"] ?:"foda-se, funcionou!"]);
    
    return ["teste"];
});

$app->get("/connect", function ($get) {
    Channel::sendOneRandon("connect", ["127.0.0.1", "2346"]);
    
    return ["teste"];
});