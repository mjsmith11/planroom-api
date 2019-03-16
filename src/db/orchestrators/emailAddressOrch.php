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
		 * Add a record for new addresses and 
		 * 
		 * @param address the address to check
		 * @param container dependency container
		 * 
		 * @return email_address object
		 */
		public static function recordUse($address, $container) {
			$container['logger']->info("Recording email address use", array('address' => $address));
			if (self::addressExists($address, $container)) {
				$obj = self::readByAddress($address, $container);
				$obj['uses'] = $obj['uses'] + 1;
				$pdo = Connection::getConnection($container)['conn'];
				$sql = "UPDATE email_address SET uses = :uses WHERE `address` = :address";
				$container['logger']->debug('Update query for record use built', array('sql' => $sql));
				$statement = $pdo->prepare($sql);
				$statement->bindParam("uses", $obj['uses']);
				$statement->bindParam("address", $address);
				$statement->execute();
				return self::read($obj['id'], $container);
			} else {
				$obj = array('address' => $address, 'uses' => 1);
				return self::create($obj, $container);
			}
		}

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
			$sql = "SELECT * FROM email_address WHERE `address` = :address";
			$container['logger']->debug("Address exists query built", array('sql' => $sql));
			$statement = $pdo->prepare($sql);
			$statement->bindParam("address", $address);
			$statement->execute();
			return $statement->fetchColumn() > 0;
		}
		
		/**
		 * Read a record by address
		 * 
		 * @param address the address to check
		 * @param container dependency container
		 * 
		 * @return email_address object
		 */
		public static function readByAddress($address, $container) {
			$container['logger']->info("Reading email by address", array('address' => $address));
			$pdo = Connection::getConnection($container)['conn'];
			$sql = "SELECT * FROM email_address WHERE `address` = :address";
			$container['logger']->debug("Read by address query built", array('sql' => $sql));
			$statement = $pdo->prepare($sql);
			$statement->bindParam("address", $address);
			$statement->execute();
			return $statement->fetch(PDO::FETCH_ASSOC);
		}

		/**
		 * Search for autocomplete suggestions
		 * 
		 * @param inputString Partial email address.  A wildcard will be added on the right
		 * @param container dependency container
		 * 
		 * @return array of email addresses
		 */
		public static function getAutoCompleteSuggestions($inputString, $container) {
			$container['logger']->info("Reading auto complete suggestions", array('input' => $inputString));
			$pdo = Connection::getConnection($container)['conn'];
			$sql = "SELECT address FROM email_address WHERE `address` LIKE :input ORDER BY `uses` DESC";
			$container['logger']->debug("Autocomplete query built", array('sql' => $sql));
			$statement = $pdo->prepare($sql);
			$inputString = $inputString . '%';
			$statement->bindParam("input", $inputString);
			$statement->execute();
			
			$result = array();
			$row = $statement->fetch();
			while ($row) {
				array_push($result, $row['address']);
				$row = $statement->fetch();
			}
			return $result;
		}
	}