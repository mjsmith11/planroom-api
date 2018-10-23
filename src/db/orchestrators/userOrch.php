<?php
	require_once(__DIR__ . '/../base/orch.php');
	/**
	 * @SuppressWarnings checkUnusedVariables
	 * Orchestrator for Jobs
	 */
	class UserOrch extends BaseOrch {
		protected static $tableName = "user";
		protected static $fieldList = array("email", "password");

		/**
		 * @param email user's email
		 * @param continer dependency container
		 * @return associative array representing user with provided email
		 */
		public static function readByEmail($email, $container) {
			$lcEmail = strtolower($email);
			$container['logger']->info('Looking up user by email', array('email' => $email));
			$pdo = Connection::getConnection($container)['conn'];
			$sql = "SELECT * FROM " . self::$tableName . " WHERE `email` = :email";
			$container['logger']->debug("email sql", array('sql' => $sql));
			$statement = $pdo->prepare($sql);
			$statement->bindParam("email", $email);
			$statement->execute();
			return $statement->fetch(PDO::FETCH_ASSOC);
		}
		/**
		 * @param email email to check
		 * @param password password to check
		 * @param container dependency container
		 * 
		 * @return bool do the email and password match a db user
		 */
		public static function checkPassword($email, $password, $container) {
			$user = self::readByEmail($email, $container);
			if (!$user) {
				return false;
			}
			return password_verify($password, $user['password']);
		}
	}