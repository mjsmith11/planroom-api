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
 * Test Authorization Middleware
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
		$config['cors_origins'] = array('localhost');
		$config['mysql'] = array();
		$config['logging'] = array('level' => 'debug', 'maxFiles' => 1);
		$config['aws'] = array('key' => 'mytestkey', 'secret' => 'mytestsecret', 'region' => 'test-region', 'bucket' => 'some-bucket', 'urlExpiration' => 42);
		$config['jwt'] = array('secret' => 'test', 'contractorExp' => 15);
		$config['baseUrl'] = 'test.com';
		$config['mail'] = array();

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

	/**
	 * Unauthorized request because of unknown role.
	 */
	public function testUnknownRole() {
		$exp = time() + 500;
		$token = array(
			"exp"   => $exp,
			"email" => "email@email.com",
			"role"  => "notARole",
			"job"   => "*"
		);
		$encoded = JWT::encode($token, 'test', 'HS512');
		$response = $this->runApp('GET', '/jobs', null, false, true, $encoded);
		$this->assertEquals(403, $response->getStatusCode(), "unknown role should be 403");
	}
	
	/**
	 * Test OPTIONS request that doesn't require authorization
	 */
	public function testOptions() {
		$response = $this->runApp('OPTIONS', '/jobs', null, false, true, null);
		$this->assertEquals(200, $response->getStatusCode(), "OPTIONS requests don't need authorization");
	}

	/**
	 * Test login api that doesn't require authorization.
	 */
	public function testLogin() {
		$mockResult = [['email' => 'test@email.com', 'password' => '$2y$10$XtLla3j.dySzJa4PA93mu.6lxIle5WbnRlQoa.la1LGSHXlmd/k3q']];
		$this->pdo->mock("SELECT * FROM user WHERE `email` = :email", $mockResult);

		$data = array('email' => 'test@test.com', 'password' => 'password123');
		$response = $this->runApp('POST', '/login', $data, false, false);
		$this->assertEquals($response->getStatusCode(), 200, "Login shouldn't require authorization");
	}

	/**
	 * Authorized request by contractor
	 */
	public function testContractorRole() {
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

		$exp = time() + 500;
		$token = array(
			"exp"   => $exp,
			"email" => "email@email.com",
			"role"  => "contractor",
			"job"   => "*"
		);
		$encoded = JWT::encode($token, 'test', 'HS512');
		// a route subcontractors shouldn't be able to access"
		$response = $this->runApp('GET', '/jobs', null, false, true, $encoded);
		$this->assertEquals(200, $response->getStatusCode(), "contractor should be able to access all routes");
	}

	/**
	 * Unauthorized subcontractor request to /jobs
	 */
	function testSubcontractorUnauthorizedRoute() {
		$exp = time() + 500;
		$token = array(
			"exp"   => $exp,
			"email" => "email@email.com",
			"role"  => "subcontractor",
			"job"   => "7"
		);
		$encoded = JWT::encode($token, 'test', 'HS512');
		// a route subcontractors shouldn't be able to access"
		$response = $this->runApp('GET', '/jobs', null, false, true, $encoded);
		$this->assertEquals(403, $response->getStatusCode(), "subcontractor should not be able to access /jobs");
	}

	/**
	 * Unauthorized call to GET /jobs/:id  Subcontractor with wrong job
	 */
	function testSubGetJobWrongJob() {
		$exp = time() + 500;
		$token = array(
			"exp"   => $exp,
			"email" => "email@email.com",
			"role"  => "subcontractor",
			"job"   => "7"
		);
		$encoded = JWT::encode($token, 'test', 'HS512');
		$response = $this->runApp('GET', '/jobs/12', null, false, true, $encoded);
		$this->assertEquals(403, $response->getStatusCode(), "subcontractor should only be able to access their job");
	}

	/**
	 * Unauthorized call to GET /jobs/:id/plans.  Subcontractor with wrong job
	 */
	function testSubGetPlansWrongJob() {
		$exp = time() + 500;
		$token = array(
			"exp"   => $exp,
			"email" => "email@email.com",
			"role"  => "subcontractor",
			"job"   => "7"
		);
		$encoded = JWT::encode($token, 'test', 'HS512');
		$response = $this->runApp('GET', '/jobs/12/plans', null, false, true, $encoded);
		$this->assertEquals(403, $response->getStatusCode(), "subcontractor should only be able to access plans for their job");
	}

	/**
	 * Authorized Subcontractor call to GET /job/:id
	 */
	function testSubGetJob() {
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

		$exp = time() + 500;
		$token = array(
			"exp"   => $exp,
			"email" => "email@email.com",
			"role"  => "subcontractor",
			"job"   => "7"
		);
		$encoded = JWT::encode($token, 'test', 'HS512');
		$response = $this->runApp('GET', '/jobs/7', null, false, true, $encoded);
		$this->assertEquals(200, $response->getStatusCode(), "subcontractor should be able to access their job");
	}

	/**
	 * Authorized subcontractor call to GET /job/:id/plans
	 */
	function testSubGetPlans() {
		$mockResult = [[ 'id' => 7 ]];
		$this->pdo->mock("SELECT * FROM job WHERE `id` = :id", $mockResult);

		$exp = time() + 500;
		$token = array(
			"exp"   => $exp,
			"email" => "email@email.com",
			"role"  => "subcontractor",
			"job"   => "7"
		);
		$encoded = JWT::encode($token, 'test', 'HS512');
		$response = $this->runApp('GET', '/jobs/7/plans', null, true, true, $encoded);
		$this->assertEquals(200, $response->getStatusCode(), "subcontractor should be able to access plans for their job");
	}
}