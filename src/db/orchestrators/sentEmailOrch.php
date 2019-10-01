<?php
	require_once(__DIR__ . '/../base/orch.php');
	/**
	 * @SuppressWarnings checkUnusedVariables
	 * Orchestrator for Sent Emails
	 * 
	 */
	class SentEmailOrch extends BaseOrch {
		protected static $tableName = "sent_email";
		protected static $fieldList = array("timestamp", "subject", "body", "alt_body", "job_id", "address_id");
		
		public static function getEmailsByJob($jobId,$container) {
			$container['logger']->info('Reading all emails for job ' . $jobId);
			$pdo = Connection::getConnection($conatiner)['conn'];
			$sql = "SELECT DISTINCT `e.address` FROM email_address as e ";
			$sql = $sql . "INNER JOIN sent_email as s ON `e.id` = `s.address_id` ";
			$sql = $sql . "WHERE `s.job_id` = :job";
			$container['logger']->debug("emails sql", array('sql' => $sql));
			$statement = $pdo->prepare($sql);
			$statement->bindParam("job", $jobId);
			$statement->execute();
			return $statement->fetch(PDO::FETCH_ASSOC);
		}
	}