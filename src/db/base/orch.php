<?php
	require_once(__DIR__ . "/../connection.php");
	/**
	 * This class provides standard Orchestrator functions for interacting with the database including Create, Read, Update, Delete.
	 * It should be extended for each table.
	 */
	abstract class BaseOrch {
		protected static $tableName;
		protected static $fieldList;

		/**
		 * Creates a record in the database
		 * 
		 * @param object $object the object to add without id
		 * @param container dependency container
		 * 
		 * @return object the object as added including id
		 * 
		 * @throws Exception if id is defined
		 */
		public static function create($object, $container) {
			$container['logger']->info('Creating', array('table' => static::$tableName));
			if (isset($object['id'])) {
				$container['logger']->error('Id specified on create', array('table' => static::$tableName));
				throw new Exception("Id cannot be specified on Create");
			}

			$pdo = Connection::getConnection($container)['conn'];
			$fields = "";
			$valuePlaceholders = "";

			foreach (static::$fieldList as $value) {
				$fields = $fields . "`" . $value . "`, ";
				$valuePlaceholders = $valuePlaceholders . ":" . $value . ", ";
			}
			// remove trailing comma and space
			$fields = substr($fields, 0, -2);
			$valuePlaceholders = substr($valuePlaceholders, 0, -2);

			$sql = "INSERT INTO " . static::$tableName . " (" . $fields . ") VALUES (" . $valuePlaceholders . ")";
			$container['logger']->debug('Create query built', array('sql' => $sql));
			$statement = $pdo->prepare($sql);

			foreach (static::$fieldList as $value) {
				$statement->bindParam($value, $object[$value]);
			}
			$statement->execute();
			return self::read($pdo->lastInsertId(), $container);
		}

		/**
		 * Read one record from database
		 * 
		 * @param id the id of the record to read
		 * @param container dependency container
		 * 
		 * @return object read record
		 */
		public static function read($id, $container) {
			$container['logger']->info('Reading', array('table' => static::$tableName, 'id' => $id));
			$pdo = Connection::getConnection($container)['conn'];
			$sql = "SELECT * FROM " . static::$tableName . " WHERE `id` = :id";
			$container['logger']->debug("Read query built", array('sql' => $sql));
			$statement = $pdo->prepare($sql);
			$statement->bindParam("id", $id);
			$statement->execute();
			return $statement->fetch(PDO::FETCH_ASSOC);

		}

		/**
		 * Checks if a record exists in the database
		 * 
		 * @param id the id of the record to check
		 * @param container dependency container
		 * 
		 * @return boolean
		 */
		public static function exists($id, $container) {
			$container['logger']->info('Checking Existance', array('table' => static::$tableName, 'id' => $id));
			$pdo = Connection::getConnection($container)['conn'];
			$sql = "SELECT * FROM " . static::$tableName . " WHERE `id` = :id";
			$container['logger']->debug("Exists query built", array('sql' => $sql));
			$statement = $pdo->prepare($sql);
			$statement->bindParam("id", $id);
			$statement->execute();
			return $statement->fetchColumn() > 0;
		}
	}