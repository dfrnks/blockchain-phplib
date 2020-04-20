<?php

namespace Blockchain\Network;

use Channel\Client;

class Channel extends Client {
    protected static $_isWorkermanEnv = false;
    
    public static function sendOneRandon($events, $data) {
        Channel::publish($events, ["id" => rand(0, $GLOBALS["process_qtd"] - 1), "data" => $data]);
    }
    
    public static function sendAll($events, $data) {
        Channel::publish($events, ["id" => -1, "data" => $data]);
    }
}