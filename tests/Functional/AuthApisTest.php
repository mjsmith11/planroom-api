<?php

namespace Tests\Functional;

require_once(__DIR__ . "/../../src/db/connection.php");
require_once(__DIR__ . "/testDependenciesContainer.php");

use TestContainer;
use Connection;

/**
 * Test Routes that read jobs
 * @SuppressWarnings checkProhibitedFunctions
 */
class AuthApisTest extends BaseTestCase {
	private $pdo;
	private static $fileBackup;
	private static $filePath = __DIR__ . '/../../config.json';
	

	/**
	 * Set up for tests. Backup config file and delete it if it exists
	 */
	public static function setUpBeforeClass() {
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

		$file = fopen(self::$filePath, 'w');
		fwrite($file, json_encode($config));
		fclose($file);
	}

	/**
	 * After tests: Restore config file if it was backed up
	 */
	public static function tearDownAfterClass() {
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
	public function setUp() {
		$this->pdo = Connection::getConnection(TestContainer::getContainer(), true)['conn'];
    }

    // public static function testTokenRefresh() {
    // This test requires adding a header to the request.  I haven't found a way to do this.
    // }

    public function testFailedLogin() {
        $mockResult = [];
        $this->pdo->mock("SELECT * FROM user WHERE `email` = :email", $mockResult);

        $data = array('email' => 'test@test.com', 'password' => 'p');
        $response = $this->runApp('POST', '/login', $data, false, false);
        $this->assertEquals($response->getStatusCode(), 401, "Should be unauthenticated");
    }

    public function testSuccessfulLogin() {
        $mockResult = [['email' => 'test@email.com', 'password' => '$2y$10$XtLla3j.dySzJa4PA93mu.6lxIle5WbnRlQoa.la1LGSHXlmd/k3q']];
        $this->pdo->mock("SELECT * FROM user WHERE `email` = :email", $mockResult);

        $data = array('email' => 'test@test.com', 'password' => 'password123');
        $response = $this->runApp('POST', '/login', $data, false, false);
        $this->assertEquals($response->getStatusCode(), 200, "Should be unauthenticated");

        $parsedResp = json_decode((string)$response->getBody(), true);
        $decodedToken = \Firebase\JWT\JWT::decode($parsedResp['token'], 'test', array('HS512'));
        $this->assertEquals($decodedToken->email, 'test@test.com', 'email in token');
        $this->assertTrue(time() + 600 - $decodedToken->exp <= 1, 'token expiration');
    }
}