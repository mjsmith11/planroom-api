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
		 * 
		 * @return object the object as added including id
		 * 
		 * @throws Exception if id is defined
		 */
		public static function create($object) {
			if (isset($object['id'])) {
				throw new Exception("Id cannot be specified on Create");
				
			}

			$pdo = Connection::getConnection()['conn'];
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
			$statement = $pdo->prepare($sql);

			foreach (static::$fieldList as $value) {
				$statement->bindParam($value, $object[$value]);
			}
			$statement->execute();
			return self::read($pdo->lastInsertId());
		}

		/**
		 * Read one record from database
		 * 
		 * @param int $id the id of the record to read
		 * 
		 * @return object read record
		 */
		public static function read($id) {
			$pdo = Connection::getConnection()['conn'];
			$sql = "SELECT * FROM " . static::$tableName . " WHERE `id` = :id";
			$statement = $pdo->prepare($sql);
			$statement->bindParam("id", $id);
			$statement->execute();
			return $statement->fetch(PDO::FETCH_ASSOC);

		}
	}