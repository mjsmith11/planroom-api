<?php
	require_once(__DIR__ . '/../base/orch.php');
	/**
	 * @SuppressWarnings checkUnusedVariables
	 * Orchestrator for Jobs
	 */
	class JobOrch extends BaseOrch {
		protected static $tableName = "user";
        protected static $fieldList = array("email", "password");

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

        public static function checkPassword($email, $password, $container) {
            $user = self::readByEmail($email, $container);
            if (count($user) !== 1) {
                return false;
            }
            return password_verify($password, $user['password']);
        }
    }