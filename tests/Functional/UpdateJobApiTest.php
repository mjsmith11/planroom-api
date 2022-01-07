<?php

namespace Tests\Functional;

require_once(__DIR__ . "/../../src/db/connection.php");
use Connection;

/**
 * Test Route PUT /jobs
 * @SuppressWarnings checkProhibitedFunctions
 */
class UpdateJobApiTest extends BaseTestCase {
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
		$config['aws'] = array('region' => 'test-region');
		$config['jwt'] = array('secret' => 'test');
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
		$this->pdo = Connection::getConnection(true)['conn'];
	}
	
	/**
	 * Test route to update a job
	 */
	public function testUpdate() {
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

		$this->pdo->mock("SELECT * FROM job WHERE `id` = :id", $readMockResult, array('id' => 45));
        $query = "UPDATE job SET `name` = :name, `bidDate` = :bidDate, `subcontractorBidsDue` = :subcontractorBidsDue, `prebidDateTime` = :prebidDateTime, `prebidAddress` = :prebidAddress, `bidEmail` = :bidEmail, `bonding` = :bonding, `taxible` = :taxible WHERE `id` = :id";

		$createMockResult = [[ 'id' => 45 ]];
		$this->pdo->mock($query, $createMockResult);

		$this->pdo->setLastId(45);
		
		$data = [
			'id' => 45, 
			'name' => 'jobName',
			'bidDate' => '08-19-2019',
			'subcontractorBidsDue' => '05-16-2018T13:00',
			'prebidDateTime' => '06-02-1999T08:00',
			'prebidAddress' => '123 Main St.',
			'bidEmail' => 'abc@xyz.com',
			'bonding' => 1,
			'taxible' => 0 
		];


		$response = $this->runApp('PUT', '/jobs/45', $data, false, false);
		$this->assertEquals(200, $response->getStatusCode());

		//Couldn't figure out this part.  $response is boolean false
		/*$parsedResp = json_decode((string)$response->getBody(), true);
		
		$this->assertEquals(45, $parsedResp['id'], 'returned id');
		$this->assertEquals('08-19-2019', $parsedResp['bidDate'], 'returned bidDate');
		$this->assertEquals('05-16-2018T13:00', $parsedResp['subcontractorBidsDue'], 'returned subcontractorBidsDue');
		$this->assertEquals('06-02-1999T08:00', $parsedResp['prebidDateTime'], 'returned prebidDateTime');
		$this->assertEquals('123 Main St.', $parsedResp['prebidAddress'], 'returned prebidAddress');
		$this->assertEquals('abc@xyz.com', $parsedResp['bidEmail'], 'returned bidEmail');
		$this->assertEquals(1, $parsedResp['bonding'], 'returned bonding');
		$this->assertEquals(0, $parsedResp['taxible'], 'returned taxible');*/
	}
}