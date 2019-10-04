<?php
/**
 * @SuppressWarnings checkUnusedFunctionParameters
 * @SuppressWarnings lineLength
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
	require_once(__DIR__ . "/db/orchestrators/sentEmailOrch.php");
	require_once(__DIR__ . "/s3/orch.php");

	/**
	 * @OA\Post(
	 * 		tags={"Jobs"},
	 * 		path="/jobs",
	 * 		summary="Adds a new job",
	 * 		@OA\RequestBody(ref="#/components/requestBodies/job_in_body"),
	 * 	 	@OA\Parameter(ref="#/components/parameters/auth-token"),
	 * 		@OA\Response(
	 * 			response=200,
	 * 			description="job added successfully",
	 * 			@OA\JsonContent(ref="#/components/schemas/job_resp")
	 * 		),
	 * 		@OA\Response(
	 * 			response=401,
	 *	 		description="Unauthorized"
	 * 		),
	 * 		@OA\Response(
	 * 			response=403,
	 * 			description="Forbidden"
	 * 		)
	 * )
	 */
	$this->post('', function($request, $response, $args) {
		$in = $request->getParsedBody();
		$out = JobOrch::create($in, $this);
		return $this->response->withJson($out);
	});

	/**
	 * @OA\Get(
	 * 	tags={"Jobs"},
	 * 	path="/jobs",
	 * 	summary="Retrieve all jobs. 1st sort: (bidDate >= today). 2nd sort: (abs(today-bidDate))",
	 * 	@OA\Parameter(ref="#/components/parameters/auth-token"),
	 * 	@OA\Response(
	 * 		response=200,
	 * 		description="All jobs in the system sorted",
	 * 		@OA\JsonContent(
	 * 			type="array",
	 * 			@OA\Items(ref="#/components/schemas/job_resp")
	 * 		)
	 * 	), 
	 * 	@OA\Response(
	 * 		response=401,
	 * 		description="Unauthorized"
	 * 	),
	 * 	@OA\Response(
	 * 		response=403,
	 * 		description="Forbidden"
	 * 	)
	 * )
	 */
	$this->get('', function($request, $response, $args) {
		$out = JobOrch::getAllByBidDate($this);
		return $this->response->withJson($out);
	});

	/**
	 * @OA\Get(
	 * 	tags={"Jobs"},
	 * 	path="/jobs/{job_id}",
	 * 	summary="Retrieve one job",
	 * 	@OA\Parameter(
	 * 		parameter="job_id_in_path",
	 * 		name="job_id",
	 * 		description="The ID of the job to retrieve",
	 * 		@OA\Schema(
	 * 			type="integer",
	 * 			format="int64",
	 * 			example=25
	 * 		),
	 * 		in="path",
	 * 		required=true
	 * 	),
	 * 	@OA\Parameter(ref="#/components/parameters/auth-token"),
	 * 	@OA\Response(
	 * 		response=200,
	 * 		description="job read",
	 * 		@OA\JsonContent(ref="#/components/schemas/job_resp")
	 * 	),
	 * 	@OA\Response(
	 * 		response=401,
	 * 		description="Unauthorized"
	 * 	),
	 * 	@OA\Response(
	 * 		response=403,
	 * 		description="Forbidden"
	 * 	)
	 * )
	 */
	$this->get('/{id}', function($request, $response, $args) {
		$out = JobOrch::read($args['id'], $this);
		return $this->response->withJson($out);
	});

	/**
	 * @OA\Get(
	 * 	tags={"Jobs", "Plans"},
	 * 	path="/jobs/{job_id}/plans",
	 * 	summary="Retrieve a list of plans with download links for a job",
	 * 	@OA\Parameter(
	 * 		parameter="job_id_in_path",
	 * 		name="job_id",
	 * 		description="The ID of the job to retrieve",
	 * 		@OA\Schema(
	 * 			type="integer",
	 * 			format="int64",
	 * 			example=25
	 * 		),
	 * 		in="path",
	 * 		required=true
	 * 	),
	 * 	@OA\Parameter(ref="#/components/parameters/auth-token"),
	 * 	@OA\Response(
	 * 		response=200,
	 * 		description="plans",
	 * 		@OA\JsonContent(
	 * 			type="array",
	 * 			@OA\Items(ref="#/components/schemas/plan")
	 * 		)
	 * 	),
	 * 	@OA\Response(
	 * 		response=401,
	 * 		description="Unauthorized"
	 * 	),
	 * 	@OA\Response(
	 * 		response=403,
	 * 		description="Forbidden"
	 * 	)
	 * )
	 */
	$this->get('/{id}/plans', function($request, $response, $args) {
		$out = Planroom\S3\S3Orch::getObjectsByJob($args['id'], $this);
		return $this->response->withJson($out);
	});

	/**
	 * @OA\Post(
	 * 	tags={"Jobs", "Plans"},
	 * 	path="/jobs/{job_id}/plans?filename={filename}",
	 * 	summary="creates a presigned post for a new plan file",
	 * 	@OA\Parameter(
	 * 		parameter="job_id_in_path",
	 * 		name="job_id",
	 * 		description="The ID of the job to retrieve",
	 * 		@OA\Schema(
	 * 			type="integer",
	 * 			format="int64",
	 * 			example="25"
	 * 		),
	 * 		in="path",
	 * 		required=true
	 * 	),
	 * 	@OA\Parameter(
	 * 		parameter="filename",
	 * 		name="filename",
	 * 		description="name of the file that will be uploaded",
	 * 		@OA\Schema(
	 * 			type="string",
	 * 			example="abcdef.pdf"
	 * 		),
	 * 		in="path",
	 * 		required=true
	 * 	),
	 * 	@OA\Parameter(ref="#/components/parameters/auth-token"),
	 * 	@OA\RequestBody(),
	 * 	@OA\Response(
	 * 		response=200,
	 * 		description="presigned request",
	 * 		@OA\JsonContent(
	 * 			type="array",
	 * 			@OA\Items(ref="#/components/schemas/presigned_post")
	 * 		)
	 * 	),
	 * 	@OA\Response(
	 * 		response=401,
	 * 		description="Unauthorized"
	 * 	),
	 * 	@OA\Response(
	 * 		response=403,
	 * 		description="Forbidden"
	 * 	)
	 * )
	 */
	$this->post('/{id}/plans', function($request, $response, $args) {
		$filename = $request->getQueryParam('filename', '');
		$out = Planroom\S3\S3Orch::getPresignedPost($args['id'], $filename, $this);
		return $this->response->withJson($out);
	});

	/**
	 * @OA\Post(
	 * 	tags={"Jobs","Emails"},
	 * 	path="/jobs/{job_id}/invite",
	 * 	summary="Invite a group of email addresses to a job",
	 * 	@OA\Parameter(
	 * 		parameter="job_id_in_path",
	 * 		name="job_id",
	 * 		description="The ID of the job to retrieve",
	 * 		@OA\Schema(
	 * 			type="integer",
	 * 			format="int64"
	 * 		),
	 * 		in="path",
	 * 		required=true
	 * 	),
	 * 	@OA\Parameter(ref="#/components/parameters/auth-token"),
	 * 	@OA\RequestBody(
	 * 		request="invitation request",
	 * 		required=true,
	 * 		description="invite_req",
	 * 		@OA\JsonContent(ref="#/components/schemas/invitation_req")
	 * 	),
	 * 	@OA\Response(
	 * 		response=200,
	 * 		description="Invitations Sent",
	 * 		@OA\JsonContent()
	 * 	),
	 * 	@OA\Response(
	 * 		response=401,
	 * 		description="Unauthorized"
	 * 	),
	 * 	@OA\Response(
	 * 		response=403,
	 * 		description="Forbidden"
	 * 	)
	 * )
	 * 
	 */
	$this->post('/{id}/invite', function($request, $response, $args) {
		$in = $request->getParsedBody();
		JobOrch::sendInvitations($args['id'], $in['validDays'], $in['emails'], $in['message'], $this);
		return $response;
	});

	/**
	 * @OA\Get(
	 * 	tags={"Jobs","Emails"},
	 * 	path="/jobs/{job_id}/invite",
	 * 	summary="Retrieve a list of emails that have been invited to a job",
	 * 	@OA\Parameter(
	 * 		parameter="job_id_in_path",
	 * 		name="job_id",
	 * 		description="The ID of the job to retrieve emails for",
	 * 		@OA\Schema(
	 * 			type="integer",
	 * 			format="int64"
	 * 		),
	 * 		in="path",
	 * 		required=true
	 * 	),
	 * 	@OA\Parameter(ref="#/components/parameters/auth-token"),
	 * 	@OA\RequestBody(),
	 * 	@OA\Response(
	 * 		response=200,
	 * 		description="matching email addresses",
	 * 		@OA\JsonContent(
	 * 			type="array",
	 *			description="list of emails receiving invitations",
	 * 			@OA\Items(
	 *            @OA\Property(
	 * 		        property="address",
	 * 				description="the email address",
  	 *      		type="string",
	 * 		        example="email@example.com"
	 * 	          ),
	 *          )
	 * 		)
	 * 	),
	 * 	@OA\Response(
	 * 		response=401,
	 * 		description="Unauthorized"
	 * 	),
	 * 	@OA\Response(
	 * 		response=403,
	 * 		description="Forbidden"
	 * 	)
	 * )
	 */
	$this->get('/{id}/invite', function($request, $response, $args) {
		$out = SentEmailOrch::getEmailsByJob($args['id'], $this);
		return $this->response->withJson($out);
	});
});
$app->group('/email', function() {
	require_once(__DIR__ . "/db/orchestrators/emailAddressOrch.php");

	/**
	 * @OA\Get(
	 * 	tags={"Emails"},
	 * 	path="/email/autocomplete?text={text}",
	 * 	summary="Searches for email autocomplete suggestions",
	 * 	@OA\Parameter(
	 * 		parameter="text",
	 * 		name="text",
	 * 		description="Partial email address. A wildcard will be added on the right",
	 * 		@OA\Schema(
	 * 			type="string",
	 * 			example="email@ex"
	 * 		),
	 * 		in="path",
	 * 		required=true
	 * 	),
	 * 	@OA\Parameter(ref="#/components/parameters/auth-token"),
	 * 	@OA\RequestBody(),
	 * 	@OA\Response(
	 * 		response=200,
	 * 		description="matching email addresses",
	 * 		@OA\JsonContent(
	 * 			type="array",
	 * 			@OA\Items(
	 *            @OA\Property(
	 * 		        property="address",
  	 *      		type="string",
	 * 		        example="email@example.com"
	 * 	          ),
	 *          )
	 * 		)
	 * 	),
	 * 	@OA\Response(
	 * 		response=401,
	 * 		description="Unauthorized"
	 * 	),
	 * 	@OA\Response(
	 * 		response=403,
	 * 		description="Forbidden"
	 * 	)
	 * )
	 */
	$this->get('/autocomplete', function($request, $response, $args) {
		$text = $request->getQueryParam('text', '');
		$out = EmailAddressOrch::getAutoCompleteSuggestions($text, $this);
		return $this->response->withJson($out);
	});
});

$app->group('', function() {
	require_once(__DIR__ . "/jwt/orch.php");
	require_once(__DIR__ . "/db/orchestrators/userOrch.php");
	require_once(__DIR__ . "/config/configReader.php");
	
	/**
	 * @OA\Schema(
	 * 	schema="login",
	 * 	description="login params",
	 * 	type="object",
	 * 	@OA\Property(
	 * 		property="email",
	 * 		type="string",
	 * 		example="user@domain.com"
	 * 	),
	 * 	@OA\Property(
	 * 		property="password",
	 * 		type="string",
	 * 		example="SecretPassword"
	 * 	)
	 * )
	 * 
	 * @OA\Schema(
	 * 	schema="token",
	 * 	description="Access token",
	 * 	type="object",
	 * 	@OA\Property(
	 * 		property="token",
	 * 		type="string",
	 * 		example="eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9.eyJlbWFpbCI6InVzZXJAZG9tYWluLmNvbSIsImpvYiI6IioiLCJyb2xlIjoiY29udHJhY3RvciIsImV4cCI6MTUxNjIzOTAyMn0.rUj26QhkeDFfDU9aZ4YVMaAx9LzNPQKjC2vVkW19i-5w-mRAEz1-6SRs319SlpuhweqowhN5ZfdVoKqEnGlMEw"
	 * 	)
	 * )
	 * 
	 * @OA\Post(
	 * 	tags={"Authentication"},
	 * 	path="/login",
	 * 	summary="Login for contractors",
	 * 	@OA\RequestBody(
	 * 		request="login request",
	 * 		required=true,
	 * 		description="login_req",
	 * 		@OA\JsonContent(ref="#/components/schemas/login")
	 * 	),
	 * 	@OA\Response(
	 * 		response=200,
	 * 		description = "logged in",
	 * 		@OA\JsonContent(ref="#/components/schemas/token")
	 * 	),
	 * 	@OA\Response(
	 * 		response=401,
	 * 		description="Login failed"
	 * 	)
	 * )
	 */
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

	/**
	 * @OA\Get(
	 * 	tags={"Authentication"},
	 * 	path="/token-refresh",
	 * 	summary="Get a fresh token",
	 * 	@OA\Parameter(ref="#/components/parameters/auth-token"),
	 * 	@OA\Response(
	 * 		response=200,
	 * 		description = "new token",
	 * 		@OA\JsonContent(ref="#/components/schemas/token")
	 * 	),
	 * 	@OA\Response(
	 * 		response=401,
	 * 		description="Unauthorized"
	 * 	),
	 * 	@OA\Response(
	 * 		response=403,
	 * 		description="Forbidden"
	 * 	)
	 * )
	 */
	$this->get('/token-refresh', function($request, $response, $args) {
		$authHeader = $request->getHeader('Planroom-Authorization')[0];
		$token = explode(' ', $authHeader)[1];
		$decoded = (array) \Firebase\JWT\JWT::decode($token, ConfigReader::getJwtInfo()['secret'], array('HS512'));
		$email = $decoded['email'];
		return $response->withJson(array(
			'token' => \Planroom\JWT\Orch::getContractorToken($email, $this)
		));
	});

	/**
	 * @OA\Options(
	 * 	tags={"Misc"},
	 * 	path="/{route}",
	 * 	summary="Preflight requests for CORS",
	 * 	@OA\Parameter(
	 * 		parameter="route",
	 * 		name="route",
	 * 		description="route to preflight",
	 * 		@OA\Schema(
	 * 			type="string"
	 * 		),
	 * 		in="path",
	 * 		required=true
	 * 	),
	 * @OA\Response(
	 * 		response=200,
	 *		description="CORS Options",
	 *		@OA\Header(
	 *			header="Access-Control-Allow-Origin",
	 *			@OA\Schema(type="string"),
	 *			description="Allowed CORS Origins"
	 *		),
	 *		@OA\Header(
	 *			header="Access-Control-Allow-Headers",
	 *			@OA\Schema(type="string"),
	 *			description="Allowed Request Headers"
	 *		),
	 *		@OA\Header(
	 *			header="Access-Control-Max-Age",
	 *			@OA\Schema(type="string"),
	 *			description="Maximum age of CORS data"
	 *		),
	 *		@OA\Header(
	 *			header="Access-Control-Allow-Methods",
	 *			@OA\Schema(type="string"),
	 *			description="Allowed Request Methods"
	 *		),
	 * 	)
	 * )
	 */
	$this->options('/{routes:.+}', function ($req, $response, $args) {
		return $response;
	});
});

	/**
 	 * 	@OA\Parameter(
	 *	  	parameter="auth-token",
	 * 		name="Planroom-Authorization",
	 * 		in="header",
	 * 		description="Auth token",
	 * 		required=true,
	 * 		@OA\Schema(
	 *	 		type="string",
	 *			example="Bearer eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9.eyJlbWFpbCI6InVzZXJAZG9tYWluLmNvbSIsImpvYiI6IioiLCJyb2xlIjoiY29udHJhY3RvciIsImV4cCI6MTUxNjIzOTAyMn0.rUj26QhkeDFfDU9aZ4YVMaAx9LzNPQKjC2vVkW19i-5w-mRAEz1-6SRs319SlpuhweqowhN5ZfdVoKqEnGlMEw"
	 * 		)
	 * 	)
 	 */