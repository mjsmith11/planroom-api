<?php
/**
 * @SuppressWarnings checkUnusedFunctionParameters
 * @OA\Info(
 * 		title="planroom-api",
 * 		version="1.1.1"
 * )
 */
use Slim\Http\Request;
use Slim\Http\Response;

// Routes
$app->group('/jobs', function() {
	require_once(__DIR__ . "/db/orchestrators/jobOrch.php");
	require_once(__DIR__ . "/s3/orch.php");

	/**
	 * @OA\Post(
	 * 		path="/jobs",
	 * 		summary="Adds a new job",
	 * 		@OA\RequestBody(
	 * 			@OA\MediaType(
	 * 				mediaType="application/json",
	 * 				@OA\Schema(
	 * 					@OA\Property(
 	 *                     property="id",
 	 *                     type="string"
 	 *                 ),
  	 *                 @OA\Property(
 	 *                     property="name",
 	 *                     type="string"
 	 *                 )
	 * 				)
	 * 			)
	 * 		),
	 * 		@OA\Response(
	 * 			response="200",
	 * 			description="OK"
	 * 		)
	 * )
	 */
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

	$this->post('/{id}/invite', function($request, $response, $args) {
		$in = $request->getParsedBody();
		JobOrch::sendInvitations($args['id'], $in['validDays'], $in['emails'], $this);
		return $response;
	});
});

$app->group('', function() {
	require_once(__DIR__ . "/jwt/orch.php");
	require_once(__DIR__ . "/db/orchestrators/userOrch.php");
	require_once(__DIR__ . "/config/configReader.php");
	
	$this->post('/login', function($request, $response, $args) {
		$in = $request->getParsedBody();
		$authenticated = UserOrch::checkPassword($in['email'], $in['password'], $this);
		if ($authenticated) {
			$this['logger']->info('User logged in', array('email' => $in['email']));
			return $this->response->withJson(array(
				'token' => \Planroom\JWT\Orch::getContractorToken($in['email'], $this)
			));
		} else {
			$this['logger']->warning('User failed to log in', array('email' => $in['email']));
			return $response->withStatus(401);
		}
	});

	$this->get('/token-refresh', function($request, $response, $args) {
		$authHeader = $request->getHeader('Planroom-Authorization')[0];
		$token = explode(' ', $authHeader)[1];
		$decoded = (array) \Firebase\JWT\JWT::decode($token, ConfigReader::getJwtInfo()['secret'], array('HS512'));
		$email = $decoded['email'];
		return $response->withJson(array(
			'token' => \Planroom\JWT\Orch::getContractorToken($email, $this)
		));
	});

	$this->options('/{routes:.+}', function ($req, $response, $args) {
		return $response;
	});
});
