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

/**
 * Tests for the Base Orch
 */
class JobOrchTest extends \PHPUnit_Framework_TestCase {
	private $pdo;

	/**
	 * Setup class to FakePdo connection
	 */
	public function setUp() {
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
		$this->pdo->mock("SELECT * FROM job WHERE `id` = :id", $readMockResult);

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