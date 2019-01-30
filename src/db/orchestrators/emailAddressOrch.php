<?php
	require_once(__DIR__ . '/../base/orch.php');
	/**
	 * @SuppressWarnings checkUnusedVariables
	 * Orchestrator for Email Addresses
	 * 
     */
    class EmailAddressOrch extends BaseOrch {
		protected static $tableName = "email_address";
        protected static $fieldList = array("address", "uses");
        

        /**
		 * Checks if a address exists in the database
		 * 
		 * @param address the address to check
		 * @param container dependency container
		 * 
		 * @return boolean
		 */
        public static function addressExists($address, $container) {
            $container['logger']->info("Checking email address existence", array('address' => $address));
            $pdo = Connection::getConnection($container)['conn'];
			$sql = "SELECT * FROM " . static::$tableName . " WHERE `address` = :address";
            $container['logger']->debug("Address exists query built", array('sql' => $sql));
            $statement = $pdo->prepare($sql);
			$statement->bindParam("address", $address);
			$statement->execute();
			return $statement->fetchColumn() > 0;
        }
    }