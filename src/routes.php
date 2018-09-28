<?php
/**
 * @SuppressWarnings checkUnusedFunctionParameters
 */
use Slim\Http\Request;
use Slim\Http\Response;

// Routes

$app->group('/jobs', function() {
	require_once(__DIR__ . "/db/orchestrators/jobOrch.php");

	$this->post('', function($request, $response, $args) {
		$in = $request->getParsedBody();
		$out = JobOrch::create($in, $this);
		return $this->response->withJson($out);
	});

	$this->get('', function($request, $response, $args) {
		$out = JobOrch::getAllByBidDate($this);
		return $this->response->withJson($out);
	});

	$this->get('/{id}', function($request, $response, $args) {
		$out = JobOrch::read($args['id'], $this);
		return $this->response->withJson($out);
	});
});

$app->group('', function() {
	$this->options('/{routes:.+}', function ($req, $response, $args) {
		return $response;
	});
});
