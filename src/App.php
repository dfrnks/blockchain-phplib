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
    
    public function exec($method, $uri, $querystring, $body, Client $global) : array {
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
            $data = $rota($global, $querystring, $body);
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
}