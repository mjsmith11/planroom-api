<?php
/**
 * @SuppressWarnings checkUnusedVariables
 * DIC configuration
 */

$container = $app->getContainer();

// monolog
$container['logger'] = function () {
	$logger = new Monolog\Logger('test_logger');
	$logger->pushHandler(new Monolog\Handler\NullHandler());
	return $logger;
};
