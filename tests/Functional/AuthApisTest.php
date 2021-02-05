<?php

namespace Tests\Functional;

require_once(__DIR__ . "/../../src/db/connection.php");
require_once(__DIR__ . "/testDependenciesContainer.php");

use TestContainer;
use Connection;

/**
 * Test Routes that do authentication
 * @SuppressWarnings checkProhibitedFunctions
 */
class AuthApisTest extends BaseTestCase {
	private $pdo;
	private static $fileBackup;
	private static $filePath = __DIR__ . '/../../config.json';
	

	/**
	 * Set up for tests. Backup config file and delete it if it exists
	 */
	public static function setUpBeforeClass(): void {
		if (file_exists(self::$filePath)) {
			self::$fileBackup = file_get_contents(self::$filePath);
			unlink(self::$filePath);
		}
		$config = array();
		$config['display_error_details'] = true;
		$config['cors_origins'] = array();
		$config['mysql'] = array();
		$config['logging'] = array('level' => 'debug', 'maxFiles' => 1);
		$config['aws'] = array('key' => 'mytestkey', 'secret' => 'mytestsecret', 'region' => 'test-region', 'bucket' => 'some-bucket', 'urlExpiration' => 42);
		$config['jwt'] = array('secret' => 'test', 'contractorExp' => 10);
		$config['baseUrl'] = 'test.com';
		$config['mail'] = array();
		
		$file = fopen(self::$filePath, 'w');
		fwrite($file, json_encode($config));
		fclose($file);
	}

	/**
	 * After tests: Restore config file if it was backed up
	 */
	public static function tearDownAfterClass(): void {
		unlink(self::$filePath);
		if (isset(self::$fileBackup)) {
			$file = fopen(__DIR__ . '/../../config.json', 'w');
			fwrite($file, self::$fileBackup);
			fclose($file);
		}
	}

	/**
	 * Set up test connection
	 */
	public function setUp(): void {
		$this->pdo = Connection::getConnection(TestContainer::getContainer(), true)['conn'];
	}

	/**
	 * Test token refresh api
	 * @SuppressWarnings lineLength
	 */
	public function testTokenRefresh() {
		$token = 'eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9.eyJleHAiOjI5OTk5OTk5OTk5LCJyb2xlIjoiY29udHJhY3RvciIsImpvYiI6IioiLCJlbWFpbCI6InRlc3RAdGVzdC5jb20ifQ.Isa8HwqE2zJ4nKpu-M8a1j0GFk5acncvh_ioOPC-66TlDETWISFNyMgvyDnKF5Jpt7g1msPXym_spv7vcFX7aQ';
		$response = $this->runApp('GET', '/token-refresh', null, false, false, $token);
		$this->assertEquals($response->getStatusCode(), 200, "Should be successful");
		$parsedResp = json_decode((string)$response->getBody(), true);
		$decodedToken = \Firebase\JWT\JWT::decode($parsedResp['token'], 'test', array('HS512'));
		$this->assertEquals($decodedToken->email, 'test@test.com', 'email in token');
		$this->assertTrue(time() + 600 - $decodedToken->exp <= 1, 'token expiration');
		$this->assertEquals($decodedToken->job, '*', 'job in token');
		$this->assertEquals($decodedToken->role, 'contractor', 'role in token');
	}

	/**
	 * Test a login that fails
	 */
	public function testFailedLogin() {
		$mockResult = [];
		$this->pdo->mock("SELECT * FROM user WHERE `email` = :email", $mockResult);

		$data = array('email' => 'test@test.com', 'password' => 'p');
		$response = $this->runApp('POST', '/login', $data, false, false);
		$this->assertEquals($response->getStatusCode(), 401, "Should be unauthenticated");
	}

	/**
	 * Test a successful login
	 */
	public function testSuccessfulLogin() {
		$mockResult = [['email' => 'test@email.com', 'password' => '$2y$10$XtLla3j.dySzJa4PA93mu.6lxIle5WbnRlQoa.la1LGSHXlmd/k3q']];
		$this->pdo->mock("SELECT * FROM user WHERE `email` = :email", $mockResult);

		$data = array('email' => 'test@test.com', 'password' => 'password123');
		$response = $this->runApp('POST', '/login', $data, false, false);
		$this->assertEquals($response->getStatusCode(), 200, "Should be successful");

		$parsedResp = json_decode((string)$response->getBody(), true);
		$decodedToken = \Firebase\JWT\JWT::decode($parsedResp['token'], 'test', array('HS512'));
		$this->assertEquals($decodedToken->email, 'test@test.com', 'email in token');
		$this->assertTrue(time() + 600 - $decodedToken->exp <= 1, 'token expiration');
	}
}