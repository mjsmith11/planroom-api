<?php
namespace Tests\Functional;

require_once(__DIR__ . "/../../src/db/connection.php");
require_once(__DIR__ . '/../../src/config/configReader.php');
require_once(__DIR__ . "/testDependenciesContainer.php");

use Connection;
use ConfigReader;
use TestContainer;

/**
 * Tests for AWS Credential Provider
 * @SuppressWarnings checkProhibitedFunctions
 */
class S3ApisTest extends BaseTestCase {
	private static $fileBackup;
	private static $filePath = __DIR__ . '/../../config.json';
	private $pdo;

	/**
	 * Set up for tests. Backup config file and delete it if it exists
	 */
	public static function setUpBeforeClass() {
        ConfigReader::reset(TestContainer::getContainer());
		if (file_exists(self::$filePath)) {
			self::$fileBackup = file_get_contents(self::$filePath);
			unlink(self::$filePath);
		}
		$config = array();
		$config['display_error_details'] = true;
		$config['cors_origins'] = array('testurl.com');
		$config['mysql'] = array();
		$config['logging'] = array('level' => 'debug', 'maxFiles' => 12);
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
        ConfigReader::reset(TestContainer::getContainer());
	}

	public function setUp() {
		$this->pdo = Connection::getConnection(true)['conn'];
    }

    public function testUpload() {
        $mockResult = [[ 'id' => 45 ]];
        $this->pdo->mock("SELECT * FROM job WHERE `id` = :id", $mockResult);
        
        $response = $this->runApp('POST', '/jobs/45/plans?filename=xyz.abc', null);
		
		$this->assertEquals(200, $response->getStatusCode());

        $parsedResp = json_decode((string)$response->getBody(), true);
        $this->assertEquals($parsedResp['postEndpoint'], "https://some-bucket.s3.amazonaws.com", "post endpoint");
		$this->assertEquals($parsedResp['signature']['key'], "45/xyz.abc", "s3 key");
    }

    public function testGetObjects() {
        $mockResult = [[ 'id' => 45 ]];
        $this->pdo->mock("SELECT * FROM job WHERE `id` = :id", $mockResult);

        $response = $this->runApp('GET', '/jobs/45/plans?filename', null, true);

        $this->assertEquals(200, $response->getStatusCode());

        $parsedResp = json_decode((string)$response->getBody(), true);
        $this->assertEquals(count($parsedResp), 2, "Result should have 2 objects");
		$this->assertEquals($parsedResp[0]['key'], 'firstObj', "1st object key");
		$this->assertEquals($parsedResp[0]['url'], 'www.test.com', "1st object url");
		$this->assertEquals($parsedResp[1]['key'], 'secondObj', "2nd object key");
		$this->assertEquals($parsedResp[1]['url'], 'www.test.com', "2nd object url");
    }
}