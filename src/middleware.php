<?php
require_once(__DIR__ . "/config/configReader.php");
// Application middleware

// CORS Header Middleware
$app->add(function($req, $res, $next) {
    $response = $next($req,$res);
    
    //Look for Origin in Origins that are allowed
    $http_origin = $_SERVER['HTTP_ORIGIN'];
    $cors_origins = ConfigReader::getCorsOrigins();
    if (in_array($http_origin, $cors_origins, true)) {
        $response = $response->withHeader("Access-Control-Allow-Origin", $http_origin);
    }

    //Add headers that are the same every time
    return $response->withHeader("Access-Control-Allow-Headers", "Content-Type, Accept")
                    ->withHeader("Access-Control-Max-Age", "86400")
                    ->withHeader("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS");
});