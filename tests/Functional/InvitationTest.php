<?php

namespace Tests\Functional;

require_once(__DIR__ . "/../../src/db/connection.php");
require_once(__DIR__ . "/testDependenciesContainer.php");
require_once(__DIR__ . "/../../src/email/invitations.php");

use TestContainer;
use Connection;
use Planroom\Email\Invitations;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Test Routes that do authentication
 * @SuppressWarnings checkProhibitedFunctions
 * @SuppressWarnings lineLength
 */
class InvitationTest extends BaseTestCase {
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
		$config['jwt'] = array('secret' => 'test', 'contractorExp' => 10);
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
		$this->pdo = Connection::getConnection(TestContainer::getContainer(), true)['conn'];
	}

	/**
	 * Test subject generation
	 */
	public function testBuildSubject() {
		$job = [ 
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

		
		$subject = Invitations::buildSubject($job);
		$this->assertEquals($subject, 'Invitation To Bid: jobName', 'Generated subject');
	}

	/**
	 * Test building body
	 */
	public function testBuildBody() {
		$job = [ 
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

		$expected = '<center>
	   <img src="https://benchmarkmechanical.com/Images/logo1.jpg" />
	   <br><br><br>
	   <div style="width:60%;border:1px solid lightgrey">
			   <h1>Invitation to Bid</h1>
			   <h2>jobName</h2>
			   <a href="test.com/jobs/45?token=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJleHAiOjEwMDAsImVtYWlsIjoidGVzdEB0ZXN0LmNvbSIsInJvbGUiOiJzdWJjb250cmFjdG9yIiwiam9iIjo0NX0.97GW23zdyQRPYkdgQSWbHewLj82PdKAP-EaJ8ewPRxsa1wvh41x92JV1tXEDa8n8r8szwwuDiXoJEhNa4AZX5w">Click Here</a> to access bidding documents and project details.<br>This link will expire December 31, 1969, 7:16 pm.
			   <br><br><br>
			   <span style="color:grey;font-size:10pt"><em>Please do not reply to this email. The mailbox is not monitored.</em></span>
	   </div>
</center>';
		$actual = Invitations::buildBody('test@test.com', $job, 1000, TestContainer::getContainer());
		$this->assertEquals(str_replace(' ', '', str_replace("\t", '', $expected)), str_replace(' ', '', str_replace("\t", '', $actual)), 'Generated body');
	}
	
	/**
	 * Test building alternate body
	 */
	public function testBuildAltBody() {
		$job = [
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

		$expected = 'This is an invitation from Benchmark Mechanical to bid on the jobName project. Bidding documentsand project details are available at the link below. The link will expire December 31, 1969, 7:16 pm.\n\ntest.com/jobs/45?token=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJleHAiOjEwMDAsImVtYWlsIjoidGVzdEB0ZXN0LmNvbSIsInJvbGUiOiJzdWJjb250cmFjdG9yIiwiam9iIjo0NX0.97GW23zdyQRPYkdgQSWbHewLj82PdKAP-EaJ8ewPRxsa1wvh41x92JV1tXEDa8n8r8szwwuDiXoJEhNa4AZX5w\n\nPlease do not reply to this email. The mailbox is not monitored';
		$actual = Invitations::buildAltBody('test@test.com', $job, 1000, TestContainer::getContainer());
		$this->assertEquals($expected, $actual, 'Generated body');
	}
	
	/**
	 * Test sending invitations
	 */
	public function testSendInvitation() {
		$job = [
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

		/*//mock sent_email insertion
		$this->pdo->mock("INSERT INTO sent_email (`timestamp`, `subject`, `body`, `alt_body`, `job_id`, `address_id`) VALUES (:timestamp, :subject, :body, :alt_body, :job_id, :address_id)", [[]]);
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
		$this->pdo->mock("SELECT * FROM sent_email WHERE `id` = :id", $readSentEmailMock);
		*/

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

		Invitations::sendInvitation('test@test.com', $job, 1000, $container);
		// It's difficult to find something to assert here, but this will show it won't error
	}
}