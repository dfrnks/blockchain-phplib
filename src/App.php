<?php

namespace Blockchain;

class App {
    
    private $routes = [];
    
    public function get(string $path, $function) : App {
        $this->routes["GET"][$path] = $function;
        
        return  $this;
    }
    
    public function post(string $path, $function) : App {
        $this->routes["POST"][$path] = $function;
        
        return  $this;
    }
    
    public function put(string $path, $function) : App {
        $this->routes["PUT"][$path] = $function;
        
        return  $this;
    }
    
    public function delete(string $path, $function) : App {
        $this->routes["DELETE"][$path] = $function;
        
        return  $this;
    }
    
    public function exec($method, $uri, $querystring, $body) : array {
        if (!key_exists($method, $this->routes) || !key_exists($uri, $this->routes[$method])) {
            return [
                404,
                [
                    "Content-Type" => "application/json"
                ],
                json_encode([ "code" => 404, "message" => "Page not found" ])
            ];
        }
        
        $rota = $this->routes[$method][$uri];
        
        try {
            $data = $rota($querystring, $body);
        } catch (\Exception $exception) {
            return [
                500,
                [
                    "Content-Type" => "application/json"
                ],
                json_encode([ "code" => $exception->getCode() ? : 500, "message" => $exception->getMessage() ])
            ];
        }
        
        if(is_array($data) || is_object($data)) {
            return [
                200,
                [
                    "Content-Type" => "application/json"
                ],
                json_encode($data)
            ];
        }
    
        return [ 200, [], $data ];
    }
    
    public function run() {
        $data = json_decode(file_get_contents("php://input"), true) ? : $_POST;
        
        [$status, $header, $body] = $this->exec($_SERVER["REQUEST_METHOD"], $_SERVER["REQUEST_URI"], $_GET, $data);
        
        http_response_code($status);
        
        foreach ($header as $key => $item) {
            header("{$key}: {$item}");
        }
        
        echo $body;
    }
}