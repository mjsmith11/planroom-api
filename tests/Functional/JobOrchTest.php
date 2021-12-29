<?php
namespace Tests\Functional;

require_once(__DIR__ . '/../../src/db/orchestrators/jobOrch.php');
require_once(__DIR__ . "/../../src/db/connection.php");
require_once(__DIR__ . "/testDependenciesContainer.php");

use JobOrch;
use Connection;
use TestContainer;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Base Orch
 * @SuppressWarnings checkProhibitedFunctions
 */
class JobOrchTest extends TestCase {
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
	 * Setup class to FakePdo connection
	 */
	public function setUp(): void {
		$this->pdo = Connection::getConnection(TestContainer::getContainer(), true)['conn'];
	}

	/**
	 * Test getAllByBidDate Functionality
	 */
	public function testgetAllByBidDate() {
		$mockResult = [[ 'id' => 12, 'field1' => "fakeData1", 'field2' => "fakeData2", 'field3' => "fakeData3"]];
		$this->pdo->mock("SELECT * FROM job order by bidDate<CURDATE(), ABS(DATEDIFF(bidDate,CURDATE()))", $mockResult);

		$result = JobOrch::getAllByBidDate(TestContainer::getContainer())[0];
		$this->assertEquals(12, $result['id'], 'Read id');
		$this->assertEquals('fakeData1', $result['field1'], 'Read fakeData1');
		$this->assertEquals('fakeData2', $result['field2'], 'Read fakeData2');
		$this->assertEquals('fakeData3', $result['field3'], 'Read fakeData3');

	}

	/**
	 * Test sending invitations
	 */
	public function testSendInvitations() {
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

		// mock the email record
		$this->pdo->mock("SELECT * FROM email_address WHERE `address` = :address", [[]]);

		// mock the Insert query for email
		$readEmailMock = [[
			'id' => 12,
			'address' => 'abc@xyz.com',
			'uses' => 1
		]];
		$this->pdo->mock("INSERT INTO email_address (`address`, `uses`) VALUES (:address, :uses)", [[]]);
		// mock the read after create
		$readEmailMock = [[
			'id' => 12,
			'address' => 'abc@xyz.com',
			'uses' => 1
		]];
		$this->pdo->mock("SELECT * FROM email_address WHERE `id` = :id", $readEmailMock);

		//mock sent_email insertion
		/*$query = "INSERT INTO sent_email (`timestamp`, `subject`, `body`, `alt_body`, `job_id`, `address_id`) ";
		$query = $query . "VALUES (:timestamp, :subject, :body, :alt_body, :job_id, :address_id)";
		$this->pdo->mock($query, [[]]);
		// mock read after create
		$readSentEmailMock = [[
			'id' => 10,
			'timestamp' => '05-16-2018T13:00',
			'subject' => 'mySubject',
			'body' => 'myBody',
			'alt_body' => 'myAltBody',
			'job_id' => 1,
			'address_id' => 12
		]];
		$this->pdo->mock("SELECT * FROM sent_email WHERE `id` = :id", $readSentEmailMock);*/


		$container = TestContainer::getContainer();
		$stub = $this->createMock(PHPMailer::class);
		$stub->method('clearAddresses')
			->willReturn('');
		$stub->method('addAddress')
			->willReturn('');
		$stub->method('isHTML')
			->willReturn('');
		$stub->method('send')
			->willReturn('');
		unset($container['mailer']);
		$container['mailer'] = $stub;
		$emails = array('test1@test.com', 'test2@test.com');
		JobOrch::sendInvitations(45, 3, $emails, $container);
		// It's tough to assert something here, but this shows it won't error
	}
}