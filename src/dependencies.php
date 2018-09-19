<?php
/**
 * @SuppressWarnings checkUnusedVariables
 * DIC configuration
 */

$container = $app->getContainer();

// monolog
$container['logger'] = function ($cont) {
	$settings = $cont->get('settings')['logger'];
	$logger = new Monolog\Logger($settings['name']);
	$logger->pushProcessor(new Monolog\Processor\UidProcessor());
	$logger->pushProcessor(new Monolog\Processor\IntrospectionProcessor());
	$logger->pushProcessor(new Monolog\Processor\WebProcessor());
	$logger->pushHandler(new Monolog\Handler\RotatingFileHandler($settings['path'], $settings['maxfiles'], $settings['level']));
	return $logger;
};
