<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

$app->group('/jobs', function(){
    require_once(__DIR__ . "/db/orchestrators/jobOrch.php")
    
    $this->post('', function($request, $response, $args){
        $in = $request->getParsedBody();
        $out = JobOrch::Create($in);
        return $this->response->withJson($out);
    });
});
