<?php

namespace Tests\Functional;

require_once(__DIR__ . "/../../src/db/connection.php");
use Connection;

/**
 * Test Route POST /jobs
 * @SuppressWarnings checkProhibitedFunctions
 */
class CreateJobApiTest extends BaseTestCase {
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
		$config['aws'] = array('region' => 'test-region');
		$config['jwt'] = array('secret' => 'test');

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
	 * Test route to create a job
	 */
	public function testCreate() {
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

		$query = "INSERT INTO job (`name`, `bidDate`, `subcontractorBidsDue`, `prebidDateTime`, `prebidAddress`, `bidEmail`, `bonding`, `taxible`) ";
		$query = $query . "VALUES (:name, :bidDate, :subcontractorBidsDue, :prebidDateTime, :prebidAddress, :bidEmail, :bonding, :taxible)";
		$createMockResult = [[ 'id' => 45 ]];
		$this->pdo->mock($query, $createMockResult);

		$this->pdo->setLastId(45);
		
		$data = [[ 
			'name' => 'jobName',
			'bidDate' => '08-19-2019',
			'subcontractorBidsDue' => '05-16-2018T13:00',
			'prebidDateTime' => '06-02-1999T08:00',
			'prebidAddress' => '123 Main St.',
			'bidEmail' => 'abc@xyz.com',
			'bonding' => 1,
			'taxible' => 0 
		]];

		$response = $this->runApp('POST', '/jobs', $data, false, false);
		
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
}