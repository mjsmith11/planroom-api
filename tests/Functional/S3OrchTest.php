<?php
namespace Tests\Functional;

require_once(__DIR__ . "/testDependenciesContainer.php");
require_once(__DIR__ . "/../../src/s3/orch.php");
require_once(__DIR__ . "/../../src/db/connection.php");
require_once __DIR__ . '/../../vendor/autoload.php';
require_once(__DIR__ . '/../../src/config/configReader.php');

use TestContainer;
use Connection;
use ConfigReader;

/**
 * Tests for AWS S3 Orch
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
		$config['baseUrl'] = 'test.com';
		$config['mail'] = array();

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
		ConfigReader::reset(TestContainer::getContainer());
	}

	/**
	 * Setup pdo for the test
	 */
	public function setUp() {
		$this->pdo = Connection::getConnection(TestContainer::getContainer(), true)['conn'];
	}

	/**
	 * Test presigned post with a job that doesn't exist
	 */
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

	/**
	 * Test presigned post without providing filename
	 */
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

	/**
	 * Test Presigned Post with valid parameters
	 */
	public function testGetPresignedPostSuccess() {
		$mockResult = [[ 'id' => 45 ]];
		$this->pdo->mock("SELECT * FROM job WHERE `id` = :id", $mockResult);
		
		$res = \Planroom\S3\S3Orch::getPresignedPost(1, 'file.txt', TestContainer::getContainer());
		$this->assertEquals($res['postEndpoint'], "https://some-bucket.s3.amazonaws.com", "post endpoint");
		$this->assertEquals($res['signature']['key'], "1/file.txt", "s3 key");
	}

	/**
	 * Test GetObjectsByJob with a job that doesn't exist
	 */
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

	/**
	 * Test Get Objects By Job with nothing returned
	 */
	public function testGetObjectsByJobEmpty() {
		$mockResult = [[ 'id' => 45 ]];
		$this->pdo->mock("SELECT * FROM job WHERE `id` = :id", $mockResult);

		$stub = $this->createMock(\Aws\S3\S3Client::class);
		$stub->method('getIterator')
			->willReturn([]);

		$container = TestContainer::getContainer();
		unset($container['S3Client']);
		$container['S3Client'] = $stub;

		$objects = \Planroom\S3\S3Orch::getObjectsByJob(45, $container);

		$this->assertEquals(count($objects), 0, "Result should be empty");
	}

	/**
	 * Test Get Objects By Job with 1 object returned
	 */
	public function testGetObjectsByJobOne() {
		$mockResult = [[ 'id' => 45 ]];
		$this->pdo->mock("SELECT * FROM job WHERE `id` = :id", $mockResult);
		
		$stub = $this->createMock(\Aws\S3\S3Client::class);
		$stub->method('getIterator')
			->willReturn([[ 'Key' => 'firstObj']]);
		$stub->method('getCommand')
			->willReturn(new \Aws\Command("dummy-command"));
		$stub->method('createPresignedRequest')
			->willReturn(new \GuzzleHttp\Psr7\Request('GET', 'www.test.com'));

		$container = TestContainer::getContainer();
		unset($container['S3Client']);
		$container['S3Client'] = $stub;

		$objects = \Planroom\S3\S3Orch::getObjectsByJob(45, $container);

		$this->assertEquals(count($objects), 1, "Result should have 1 object");
		$this->assertEquals($objects[0]['key'], 'firstObj', "1st object key");
		$this->assertEquals($objects[0]['url'], 'www.test.com', "1st object url");
	}

	/**
	 * Test Get Objects By Job with 2 jobs returned
	 */
	public function testGetObjectsByJobTwo() {
		$mockResult = [[ 'id' => 45 ]];
		$this->pdo->mock("SELECT * FROM job WHERE `id` = :id", $mockResult);
		
		$stub = $this->createMock(\Aws\S3\S3Client::class);
		$stub->method('getIterator')
			->willReturn([[ 'Key' => 'firstObj'], [ 'Key' => 'secondObj']]);
		$stub->method('getCommand')
			->willReturn(new \Aws\Command("dummy-command"));
		$stub->method('createPresignedRequest')
			->willReturn(new \GuzzleHttp\Psr7\Request('GET', 'www.test.com'));

		$container = TestContainer::getContainer();
		unset($container['S3Client']);
		$container['S3Client'] = $stub;

		$objects = \Planroom\S3\S3Orch::getObjectsByJob(45, $container);

		$this->assertEquals(count($objects), 2, "Result should have 2 objects");
		$this->assertEquals($objects[0]['key'], 'firstObj', "1st object key");
		$this->assertEquals($objects[0]['url'], 'www.test.com', "1st object url");
		$this->assertEquals($objects[1]['key'], 'secondObj', "2nd object key");
		$this->assertEquals($objects[1]['url'], 'www.test.com', "2nd object url");
	}
}
