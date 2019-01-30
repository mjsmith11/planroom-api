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
        
    }