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
		
	}