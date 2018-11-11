<?php
/**
 * @SuppressWarnings checkUnusedVariables
 * DIC configuration
 */
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;

$container = $app->getContainer();

// monolog
$container['logger'] = function () {
	$logger = new Monolog\Logger('test_logger');
	$logger->pushHandler(new Monolog\Handler\NullHandler());
	return $logger;
};

$container['S3Client'] = function($cont) {
	$credProvider = Planroom\S3\CredentialProvider::json($cont);
	$client = new Aws\S3\S3Client([
		'version' => 'latest',
		'region' => 'us-east-1',
		'credentials' => $credProvider
	]);
	return $client;
};

$container['mailer'] = function($cont) {
	return new PHPMailer(true);
};