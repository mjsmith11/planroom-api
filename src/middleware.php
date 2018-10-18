<?php
/**
 * Middleware functions
 */

require_once(__DIR__ . "/config/configReader.php");
// Application middleware

// Request Logger
$app->add(function($req, $res, $next) {
	$this['logger']->info('Request Received');
	return $next($req, $res);
});

// JWT Authentication
$app->add(new Tuupola\Middleware\JwtAuthentication([
	"secret" => ConfigReader::getJwtInfo()['secret'],
	"logger" => $app->getContainer()['logger'],
	"path" => ['/'],
	"ignore" => ['/login'],
	"algorithm" => 'HS512'
]));

// CORS Header Middleware
$app->add(function($req, $res, $next) {
	$response = $next($req, $res);
	
	if ($response->getStatusCode() == 200 && $req->isOptions()) {
		//Look for Origin in Origins that are allowed
		$httpOrigin = $_SERVER['HTTP_ORIGIN'];
		$corsOrigins = ConfigReader::getCorsOrigins();
		$shouldAddHeader = in_array($httpOrigin, $corsOrigins, true);
		$this['logger']->debug('CORS Origin Evaluation', array('httpOrigin' => $httpOrigin, 'corsOrigins' => $corsOrigins));
		if ($shouldAddHeader) {
			$response = $response->withHeader("Access-Control-Allow-Origin", $httpOrigin);
		}

		//Add headers that are the same every time
		$response = $response->withHeader("Access-Control-Allow-Headers", "Content-Type, Accept")
						     ->withHeader("Access-Control-Max-Age", "86400")
						     ->withHeader("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS");
	}
	return $response;
});
