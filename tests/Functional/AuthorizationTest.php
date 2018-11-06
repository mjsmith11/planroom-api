<?php

namespace Tests\Functional;

require_once(__DIR__ . "/../../src/db/connection.php");
require_once(__DIR__ . "/../../src/config/configReader.php");
require_once(__DIR__ . "/testDependenciesContainer.php");

use ConfigReader;
use Connection;
use TestContainer;
use \Firebase\JWT\JWT;

/**
 * Test Routes that read jobs
 * @SuppressWarnings checkProhibitedFunctions
 */
class AuthorizationTest extends BaseTestCase {
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
		$config['jwt'] = array('secret' => 'test');

		$file = fopen(self::$filePath, 'w');
		fwrite($file, json_encode($config));
        fclose($file);
        ConfigReader::reset(TestContainer::getContainer());
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
		$this->pdo = Connection::getConnection(true)['conn'];
    }

    public function testUnknownRole() {
        $exp = time() + 500;
		$token = array(
			"exp"   => $exp,
			"email" => "email@email.com",
			"role"  => "notARole",
			"job"   => "*"
		);
        $encoded = JWT::encode($token, 'test', 'HS512');
        echo $encoded;
        $response = $this->runApp('GET', '/jobs', null, false, true, $encoded);
        $this->assertEquals(403, $response->getStatusCode());
    }
}