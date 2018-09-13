<?php
$dbSettings = require __DIR__ . '/db/connection.php';
return  [
			'environments' => [
				'default_database' => 'app-db',
				'app-db' => [
					'name' => $dbSettings['dbName'],
					'connection' => $dbSettings['conn']
				]
			],
			'paths' => [
				'migrations' => __DIR__ . '/db/migrations'
			]

		];