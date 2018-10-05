<?php
	require __DIR__.'/s3/credentialProvider.php';
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

$container['S3Client'] = function($cont) {
	$credProvider = Planroom\S3\CredentialProvider::json($cont);
	$settings = $cont->get('settings')['aws'];
	$client = new Aws\S3\S3Client([
		'version' => 'latest',
		'region' => $settings['region'],
		'credentials' => $credProvider
	]);
	return $client;
};
