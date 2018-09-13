<?php
/**
 * @SuppressWarnings checkUnusedVariables
 * Middleware functions
 */

require_once(__DIR__ . "/config/configReader.php");
// Application middleware

// CORS Header Middleware
$app->add(function($req, $res, $next) {
	$response = $next($req, $res);
	
	//Look for Origin in Origins that are allowed
	$httpOrigin = $_SERVER['HTTP_ORIGIN'];
	$corsOrigins = ConfigReader::getCorsOrigins();
	$shouldAddHeader = in_array($httpOrigin, $corsOrigins, true);
	if ($shouldAddHeader) {
		$response = $response->withHeader("Access-Control-Allow-Origin", $httpOrigin);
	}

	//Add headers that are the same every time
	return $response->withHeader("Access-Control-Allow-Headers", "Content-Type, Accept")
					->withHeader("Access-Control-Max-Age", "86400")
					->withHeader("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS");
});