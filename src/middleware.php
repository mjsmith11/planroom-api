<?php
/**
 * Middleware functions
 */

require_once(__DIR__ . "/config/configReader.php");
// Application middleware
// Last Added First Executed

// CORS Header Middleware
$app->add(function($req, $res, $next) {
	$response = $next($req, $res);
	
	$status = $response->getStatusCode();
	if ($status === 200) {
		//Look for Origin in Origins that are allowed
		$httpOrigin = $_SERVER['HTTP_ORIGIN'];
		$corsOrigins = ConfigReader::getCorsOrigins();
		$shouldAddHeader = in_array($httpOrigin, $corsOrigins, true);
		$this['logger']->debug('CORS Origin Evaluation', array('httpOrigin' => $httpOrigin, 'corsOrigins' => $corsOrigins));
		if ($shouldAddHeader) {
			$response = $response->withHeader("Access-Control-Allow-Origin", $httpOrigin);
		}

		//Add headers that are the same every time
		$response = $response->withHeader("Access-Control-Allow-Headers", "Content-Type, Accept, Planroom-Authorization")
							->withHeader("Access-Control-Max-Age", "86400")
							->withHeader("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS");
	}
	return $response;
});

// Authorization
// This needs to run after jwt authnetication
$app->add(function($req, $res, $next){
	$token = $req->getAttribute('token');
	$this['logger']->debug("Authorizing Request", array("token" => $token, "path" => $req->getUri()->getPath()));
	if ($req->getUri()->getPath() === '/login' || $req->isOptions()) {
		// login and options doesn't need authorization
		return $next($req, $res);
	} elseif ($token['role'] === 'contractor') {
		// Contractor can access all routes
		return $next($req, $res);
	} elseif ($token['role'] === 'subcontractor') {
		// Subcontractor can access a restricted set of routes
		$authorized = false; // assume that it isn't authorized
		
		// GET /jobs/:id
		$path = '/jobs/' . $token['job'];
		if ($req->getUri()->getPath() === $path && $req->isGet()) { $authorized = true; }

		// GET /jobs/:id
		$path = '/jobs/' . $token['job'] . '/plans';
		if ($req->getUri()->getPath() === $path && $req->isGet()) { $authorized = true; }

		if ($authorized) {
			return $next($req, $res);
		} else {
			return $res->withStatus(403);
		}

	} else {
		// nothing is authorized besides login for an unknown role
		return $res->withStatus(403);
	}
	
});

// JWT Authentication
$app->add(new Tuupola\Middleware\JwtAuthentication([
	"secret" => ConfigReader::getJwtInfo()['secret'],
	"logger" => $app->getContainer()['logger'],
	"path" => ['/'],
	"ignore" => ['/login'],
	"algorithm" => 'HS512',
	"header" => 'Planroom-Authorization',
]));


// Request Logger
$app->add(function($req, $res, $next) {
	$this['logger']->info('Request Received');
	return $next($req, $res);
});
