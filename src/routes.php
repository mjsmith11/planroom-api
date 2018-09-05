<?php

use Slim\Http\Request;
use Slim\Http\Response;

require_once(__DIR__ . "/db/orchestrators/jobOrch.php");

// Routes

$app->group('/jobs', function(){
    $this->post('', function($request, $response, $args){
        $in = $request->getParsedBody();
        $out = JobOrch::Create($in);
        return $this->response->withJson($out);
    });
});
