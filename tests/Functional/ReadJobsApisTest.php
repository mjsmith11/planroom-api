<?php

namespace Tests\Functional;

require_once(__DIR__ . "/../../src/db/connection.php");
use Connection;

/**
 * Test Routes that read jobs
 * @SuppressWarnings checkProhibitedFunctions
 */
class ReadJobsApisTest extends BaseTestCase {
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
		$config['baseUrl'] = 'test.com';

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
		$this->pdo = Connection::getConnection(true)['conn'];
	}

	/**
	 * Test route /jobs/{id}
	 */
	public function testReadSingleJob() {
		$readMockResult = [[ 
			'id' => 45, 
			'name' => 'jobName',
			'bidDate' => '08-19-2019',
			'subcontractorBidsDue' => '05-16-2018T13:00',
			'prebidDateTime' => '06-02-1999T08:00',
			'prebidAddress' => '123 Main St.',
			'bidEmail' => 'abc@xyz.com',
			'bonding' => 1,
			'taxible' => 0 
		]];

		$this->pdo->mock("SELECT * FROM job WHERE `id` = :id", $readMockResult);
		
		$response = $this->runApp('GET', '/jobs/45', null, false, false);
		
		$this->assertEquals(200, $response->getStatusCode());

		$parsedResp = json_decode((string)$response->getBody(), true);
		
		$this->assertEquals(45, $parsedResp['id'], 'returned id');
		$this->assertEquals('08-19-2019', $parsedResp['bidDate'], 'returned bidDate');
		$this->assertEquals('05-16-2018T13:00', $parsedResp['subcontractorBidsDue'], 'returned subcontractorBidsDue');
		$this->assertEquals('06-02-1999T08:00', $parsedResp['prebidDateTime'], 'returned prebidDateTime');
		$this->assertEquals('123 Main St.', $parsedResp['prebidAddress'], 'returned prebidAddress');
		$this->assertEquals('abc@xyz.com', $parsedResp['bidEmail'], 'returned bidEmail');
		$this->assertEquals(1, $parsedResp['bonding'], 'returned bonding');
		$this->assertEquals(0, $parsedResp['taxible'], 'returned taxible');
	}

		/**
	 * Test route /jobs
	 */
	public function testReadMultipleJob() {
		$readMockResult = [[ 
			'id' => 45, 
			'name' => 'jobName',
			'bidDate' => '08-19-2019',
			'subcontractorBidsDue' => '05-16-2018T13:00',
			'prebidDateTime' => '06-02-1999T08:00',
			'prebidAddress' => '123 Main St.',
			'bidEmail' => 'abc@xyz.com',
			'bonding' => 1,
			'taxible' => 0 
		],
		[ 
			'id' => 46, 
			'name' => 'jobName2',
			'bidDate' => '08-20-2019',
			'subcontractorBidsDue' => '05-17-2018T13:00',
			'prebidDateTime' => '06-03-1999T08:00',
			'prebidAddress' => '1234 Main St.',
			'bidEmail' => 'abcd@xyz.com',
			'bonding' => 0,
			'taxible' => 1 
		]];

		$this->pdo->mock("SELECT * FROM job order by bidDate<CURDATE(), ABS(DATEDIFF(bidDate,CURDATE()))", $readMockResult);
		
		$response = $this->runApp('GET', '/jobs', null, false, false);
		
		$this->assertEquals(200, $response->getStatusCode());

		$parsedResp = json_decode((string)$response->getBody(), true);

		$firstJob = $parsedResp[0];
		$secondJob = $parsedResp[1];
		
		$this->assertEquals(2, count($parsedResp), 'array length');

		$this->assertEquals(45, $firstJob['id'], 'returned id 1');
		$this->assertEquals('08-19-2019', $firstJob['bidDate'], 'returned bidDate 1');
		$this->assertEquals('05-16-2018T13:00', $firstJob['subcontractorBidsDue'], 'returned subcontractorBidsDue 1');
		$this->assertEquals('06-02-1999T08:00', $firstJob['prebidDateTime'], 'returned prebidDateTime 1');
		$this->assertEquals('123 Main St.', $firstJob['prebidAddress'], 'returned prebidAddress 1');
		$this->assertEquals('abc@xyz.com', $firstJob['bidEmail'], 'returned bidEmail 1');
		$this->assertEquals(1, $firstJob['bonding'], 'returned bonding 1');
		$this->assertEquals(0, $firstJob['taxible'], 'returned taxible 1');

		$this->assertEquals(46, $secondJob['id'], 'returned id 2');
		$this->assertEquals('08-20-2019', $secondJob['bidDate'], 'returned bidDate 2');
		$this->assertEquals('05-17-2018T13:00', $secondJob['subcontractorBidsDue'], 'returned subcontractorBidsDue 2');
		$this->assertEquals('06-03-1999T08:00', $secondJob['prebidDateTime'], 'returned prebidDateTime 2');
		$this->assertEquals('1234 Main St.', $secondJob['prebidAddress'], 'returned prebidAddress 2');
		$this->assertEquals('abcd@xyz.com', $secondJob['bidEmail'], 'returned bidEmail 2');
		$this->assertEquals(0, $secondJob['bonding'], 'returned bonding 2');
		$this->assertEquals(1, $secondJob['taxible'], 'returned taxible 2');
	}
}