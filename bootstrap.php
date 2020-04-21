<?php

use Blockchain\App;
use Blockchain\Client as Blockchain;
use Blockchain\Network\Channel;

require_once __DIR__ . '/vendor/autoload.php';

$app = new App();

$app->get("/chain", function (Blockchain $bchain, $get) {
    $blocks = [];
    
    foreach ($bchain->getChain() as $block) {
        /**
         * @var \Blockchain\Block $block
         */
        $blocks[] = $block->getBlock();
    }
    
    return $blocks;
});

$app->get("/teste", function (Blockchain $bchain, $get) {
    Channel::sendAll("sendAll", ["message" => $get["message"] ?:"foda-se, funcionou!"]);
    
    return ["teste"];
});

$app->get("/connect", function (Blockchain $bchain, $get) {
    Channel::sendOneRandon("connect", ["127.0.0.1", "2346"]);
    
    return ["teste"];
});

$app->get("/peers", function (Blockchain $bchain, $get) {
    return $bchain->getPeers();
});

$app->put("/block", function (Blockchain $bchain, $get, $post) {
    Channel::sendOneRandon("addBlock", $post);
    
    return [
        "success" => true
    ];
});

$app->get("/lastblock", function (Blockchain $bchain, $get) {
    return $bchain->getLastBlock();
});