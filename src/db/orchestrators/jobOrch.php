<?php
	require_once(__DIR__ . '/../base/orch.php');
	require_once(__DIR__ . '/../../email/invitations.php');

	/**
	 * @SuppressWarnings checkUnusedVariables
	 * Orchestrator for Jobs
	 */
	class JobOrch extends BaseOrch {
		protected static $tableName = "job";
		protected static $fieldList = array("name", "bidDate", "subcontractorBidsDue", "prebidDateTime", "prebidAddress", "bidEmail", "bonding", "taxible");

		/**
		 * @param container dependency container
		 * @returns associative array of all jobs. First sort places present or future bid dates before past bid dates. Second sort is by distance from today
		 */
		public static function getAllByBidDate($container) {
			$container['logger']->info('Reading all jobs by bid date');
			$pdo = Connection::getConnection($container)['conn'];
			$sql = "SELECT * FROM job order by bidDate<CURDATE(), ABS(DATEDIFF(bidDate,CURDATE()))";
			$container['logger']->debug("Read query", array('sql' => $sql));
			return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
		}

		/**
		 * Sends invitations to a job via email
		 * @param id id of the job to invite
		 * @param expDays How many days should the link in the email be valid
		 * @param emails array of emails to invite
		 * @param container dependency container
		 */
		public static function sendInvitations($id, $expDays, $emails, $container) {
			$job = $this->Read($id, $container);
			$container['logger']->debug('sending invitations', array('id' => $id, 'expDays' => $expDays, 'emails' => $emails));
			$exp = time() + ($expDays * 86400);
			foreach ($emails as $email) {
				\Planroom\Email\Invitations::sendInvitation($email, $job, $exp, $container);
			}
		}
	}