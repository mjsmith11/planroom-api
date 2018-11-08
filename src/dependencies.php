<?php
	require __DIR__ . '/s3/credentialProvider.php';

	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;
	
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

$container['mailer'] = function($cont) {
	$settings = $cont->get('settings')['smtp'];

	$mailer = new PHPMailer(true);
	$mailer->SMTPDebug = 0;
	$mailer->isSMTP();
	$mailer->Host = $settings['host'];
	$mailer->SMTPAuth = true;
	$mailer->Username = $settings['username'];
	$mailer->Password = $settings['password'];
	$mailer->SMTPSecure = 'tls';
	$mailer->Port = $settings['port'];
	$mailer->setFrom($settings['username'], $settings['fromName']);

	return $mailer;
};
