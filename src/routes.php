<?php
/**
 * @SuppressWarnings checkUnusedFunctionParameters
 */
use Slim\Http\Request;
use Slim\Http\Response;

// Routes
$app->group('/jobs', function() {
	require_once(__DIR__ . "/db/orchestrators/jobOrch.php");
	require_once(__DIR__ . "/s3/orch.php");

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

	$this->get('/{id}/plans', function($request, $response, $args) {
		$out = Planroom\S3\S3Orch::getObjectsByJob($args['id'], $this);
		return $this->response->withJson($out);
	});

	$this->post('/{id}/plans', function($request, $response, $args) {
		$filename = $request->getQueryParam('filename', '');
		$out = Planroom\S3\S3Orch::getPresignedPost($args['id'], $filename, $this);
		return $this->response->withJson($out);
	});
});

$app->group('', function() {
	require_once(__DIR__ . "/jwt/orch.php");
	require_once(__DIR__ . "/db/orchestrators/userOrch.php");
	
	$this->post('/login', function($request, $response, $args){
		$in = $request->getParsedBody();
		if (UserOrch::checkPassword($in['email'], $in['password'], $this)) {
			$this['logger']->info('User logged in', array('email' => $in['email']));
			return $this->response->withJson(array(
				'token' => \Planroom\JWT\Orch::getContractorToken($in['email'], $this)
			));
		} else {
			$this['logger']->warning('User failed to log in', array('email' => $in['email']));
			return $response->withStatus(401);
		}
	});

	$this->options('/{routes:.+}', function ($req, $response, $args) {
		return $response;
	});
});
