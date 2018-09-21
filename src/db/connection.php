<?php
	/**
	 * This class provides a connection to the database per the config.json
	 */
	class Connection {
		private static $connection;

		/**
		 * Get Singleton Database pdo connection
		 * 
		 * @return pdo connection
		 * 
		 * @throws PDOException when connecting fails
		 * 
		 * @param object dependency container
		 * @param boolean defaults to false. Creates a connection as Tests\Functional\FakePdo when true
		 */
		public static function getConnection($container, $test = false) {
			if ($test) {
				self::$connection = [
					'conn' => new Tests\Functional\FakePdo()
				];
			}
			if (!isset(self::$connection)) {
				$container->logger->debug('Reading Database Config');
				$jsonString = file_get_contents(__DIR__ . '/../../config.json');
				$config = json_decode($jsonString, true);
				
				$host    = $config['mysql']['host'];
				$port    = $config['mysql']['port'];
				$db      = $config['mysql']['database'];
				$user    = $config['mysql']['username'];
				$pass    = $config['mysql']['password'];
				$charset = 'utf8';

				$dsn = 'mysql:host=' . $host . ';dbname=' . $db . ';charset=' . $charset;
				$opt = [
					PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
					PDO::ATTR_EMULATE_PREPARES   => false,
				];
				try {
					$container->logger->debug('Connecting to database');
					$pdo =  new PDO($dsn, $user, $pass, $opt);
					unset($host, $port, $user, $pass, $charset, $dsn, $config, $opt);
					self::$connection = [
						'conn' => $pdo,
						'dbName' => $db
					];
				} catch (\PDOException $e) {
					unset($host, $port, $db, $user, $pass, $charset, $dsn, $config, $opt);
					$container->logger->alert('Cannot connect to database', array('Exception' => $e));
					throw new \PDOException($e->getMessage(), (int)$e->getCode());
				}
			}
			return self::$connection;
		}
	}