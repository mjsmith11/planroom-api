<?php
namespace Tests\Functional;

require_once(__DIR__ . "/testDependenciesContainer.php");
require_once(__DIR__ . "/../../src/s3/orch.php");
require_once(__DIR__ . "/../../src/db/connection.php");

use TestContainer;
use Connection;

/**
 * Tests for AWS Credential Provider
 * @SuppressWarnings checkProhibitedFunctions
 */
class S3OrchTest extends \PHPUnit_Framework_TestCase {
	private static $fileBackup;
	private static $filePath = __DIR__ . '/../../config.json';
	private $pdo;

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
		$config['cors_origins'] = array('testurl.com');
		$config['mysql'] = array();
		$config['logging'] = array('level' => 'unknown', 'maxFiles' => 12);
		$config['aws'] = array('key' => 'mytestkey', 'secret' => 'mytestsecret', 'region' => 'test-region', 'bucket' => 'some-bucket', 'urlExpiration' => 42);
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

	public function setUp() {
		$this->pdo = Connection::getConnection(TestContainer::getContainer(), true)['conn'];
	}

	public function testGetPresignedPostNoJob() {
		$mockResult = [];
		$this->pdo->mock("SELECT * FROM job WHERE `id` = :id", $mockResult);
		
		try {
			\Planroom\S3\S3Orch::getPresignedPost(1, 'myFile', TestContainer::getContainer());
			$this->fail('No exception');
		} catch (\Exception $e) {
			$this->assertEquals($e->getMessage(), "Job 1 does not exist", "Exception Message");
		}
	}

	public function testGetPresignedPostBlankFile() {
		$mockResult = [[ 'id' => 45 ]];
		$this->pdo->mock("SELECT * FROM job WHERE `id` = :id", $mockResult);
		
		try {
			\Planroom\S3\S3Orch::getPresignedPost(1, ' ', TestContainer::getContainer());
			$this->fail('No exception');
		} catch (\Exception $e) {
			$this->assertEquals($e->getMessage(), "filename must be specified", "Exception Message");
		}
	}

	public function testGetPresignedPostSuccess() {
		$mockResult = [[ 'id' => 45 ]];
		$this->pdo->mock("SELECT * FROM job WHERE `id` = :id", $mockResult);
		
		$res = \Planroom\S3\S3Orch::getPresignedPost(1, 'file.txt', TestContainer::getContainer());
		$this->assertEquals($res['postEndpoint'], "https://some-bucket.s3.amazonaws.com", "post endpoint");
		$this->assertEquals($res['signature']['key'], "1/file.txt", "s3 key");
	}

	public function testGetObjectsByJobNoJob() {
		$mockResult = [];
		$this->pdo->mock("SELECT * FROM job WHERE `id` = :id", $mockResult);
		
		try {
			\Planroom\S3\S3Orch::getObjectsByJob(1, TestContainer::getContainer());
			$this->fail('No exception');
		} catch (\Exception $e) {
			$this->assertEquals($e->getMessage(), "Job 1 does not exist", "Exception Message");
		}
	}

	// empty list
	// one item list
	// two item list
}
