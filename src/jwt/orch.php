<?php
namespace Planroom\JWT;

require_once(__DIR__ . "/../config/configReader.php");

use \Firebase\JWT\JWT;
use ConfigReader;

/**
 * Orchestrator for JWT
 */
class Orch {
	/**
	 * @param email contractor user's email
	 * @param container dependency container
	 * 
	 * @return string auth token for passed user
	 */
	public static function getContractorToken($email, $container) {
		$container['logger']->debug('Generating Token For Contractor User', array('email' => $email));
		$config = ConfigReader::getJwtInfo();
		$secret = $config['secret'];
		$validSeconds = $config['contractorExp'] * 60;
		$exp = time() + $validSeconds;
		$token = array(
			"exp"   => $exp,
			"email" => $email,
			"role"  => "contractor",
			"job"   => "*"
		);
		return JWT::encode($token, $secret, 'HS512');
	}

	/**
	 * @param email contractor user's email
	 * @param job job to authorize
	 * @param exp unix expiration time
	 * @param container dependency container
	 * 
	 * @return string auth token for passed user
	 */
	public static function getSubcontractorToken($email, $job, $exp, $container) {
		$container['logger']->debug('Generating Token For subcontractor User', array('email' => $email));
		$config = ConfigReader::getJwtInfo();
		$secret = $config['secret'];
		$token = array(
			"exp"   => $exp,
			"email" => $email,
			"role"  => "subcontractor",
			"job"   => $job
		);
		return JWT::encode($token, $secret, 'HS512');
	}	
}