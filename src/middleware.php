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

$app->add(new Tuupola\Middleware\JwtAuthentication([
	"secret" => ConfigReader::getJwtSecret(),
	"logger" => $app->getContainer()['logger'],
	"ignore" => ['/login']
]));

// CORS Header Middleware
$app->add(function($req, $res, $next) {
	$response = $next($req, $res);
	
	//Look for Origin in Origins that are allowed
	$httpOrigin = $_SERVER['HTTP_ORIGIN'];
	$corsOrigins = ConfigReader::getCorsOrigins();
	$shouldAddHeader = in_array($httpOrigin, $corsOrigins, true);
	$this['logger']->debug('CORS Origin Evaluation', array('httpOrigin' => $httpOrigin, 'corsOrigins' => $corsOrigins));
	if ($shouldAddHeader) {
		$response = $response->withHeader("Access-Control-Allow-Origin", $httpOrigin);
	}

	//Add headers that are the same every time
	return $response->withHeader("Access-Control-Allow-Headers", "Content-Type, Accept")
					->withHeader("Access-Control-Max-Age", "86400")
					->withHeader("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS");
});
