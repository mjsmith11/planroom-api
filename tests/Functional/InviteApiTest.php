<?php

namespace Tests\Functional;

require_once(__DIR__ . "/../../src/db/connection.php");
use Connection;

/**
 * Test Routes that send invitations
 * @SuppressWarnings checkProhibitedFunctions
 */
class InviteApiTest extends BaseTestCase {
	private $pdo;
	private static $fileBackup;
	private static $filePath = __DIR__ . '/../../config.json';
	

	/**
	 * Set up for tests. Backup config file and delete it if it exists
	 */
	public static function setUpBeforeClass() : void {
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
	 * Test /jobs/:id/invite
	 */
	public function testInviteApi() {
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

		$readEmailMock = [[
			'id' => 12,
			'address' => 'abc@xyz.com',
			'uses' => 1
		]];

		$this->pdo->mock("SELECT * FROM job WHERE `id` = :id", $readMockResult, array('id' => 45));
		$this->pdo->mock("SELECT * FROM email_address WHERE `id` = :id", $readEmailMock,array('id',''));
		$this->pdo->mock("SELECT * FROM email_address WHERE `address` = :address", [[]], array('address'=>'email@test.com'));
		
		$data = array('validDays' => 3, 'emails' => array('email@test.com', 'email@test.com')); // invite the same email twice due to a limitation in the pdo mock tool

		$response = $this->runApp('POST', '/jobs/45/invite', $data, false, false, null);
		
		$this->assertEquals(200, $response->getStatusCode());
	}
}