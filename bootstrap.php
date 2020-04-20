<?php

use Blockchain\App;
use Blockchain\Network\Channel;

require_once __DIR__ . '/vendor/autoload.php';

$app = new App();
$app->get("/", function ($get) {
    Channel::connect('127.0.0.1', 2446);
    Channel::publish("sendAll", ["message" => $get["message"] ?:"foda-se, funcionou!"]);
    
    return ["teste"];
});