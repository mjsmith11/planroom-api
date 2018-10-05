<?php
require __DIR__ . '/src/db/connection.php';
$dbSettings = Connection::getConnection(null);
return  [
			'environments' => [
				'default_database' => 'app-db',
				'app-db' => [
					'name' => $dbSettings['dbName'],
					'connection' => $dbSettings['conn']
				]
			],
			'paths' => [
				'migrations' => __DIR__ . '/src/db/migrations'
			]

		];