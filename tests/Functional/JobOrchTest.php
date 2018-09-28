<?php
namespace Tests\Functional;

require_once(__DIR__ . '/../../src/db/orchestrators/jobOrch.php');
require_once(__DIR__ . "/../../src/db/connection.php");
require_once(__DIR__ . "/testDependenciesContainer.php");

use JobOrch;
use Connection;
use TestContainer;

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
}