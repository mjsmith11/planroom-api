<?php
require_once(__DIR__ . "/config/configReader.php");
return [
	'settings' => [
		'displayErrorDetails' => ConfigReader::getDisplayErrorDetails(),
		'addContentLengthHeader' => false, // Allow the web server to send the content-length header

		// Monolog settings
		'logger' => [
			'name' => 'planroom-api',
			'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
			'level' => ConfigReader::getLogLevel(),
			'maxfiles' => ConfigReader::getMaxLogFiles(),
		],

		// AWS Settings
		'aws' => [
			'region' => ConfigReader::getAwsRegion(),
		]
	],
];
