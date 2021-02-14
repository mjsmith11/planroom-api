<?php
namespace Tests\Functional;

require_once(__DIR__ . '/../../src/db/base/orch.php');
require_once(__DIR__ . "/../../src/db/connection.php");
require_once(__DIR__ . "/testDependenciesContainer.php");

use BaseOrch;
use Connection;
use TestContainer;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Base Orch
 * @SuppressWarnings checkUnusedVariables
 * @SuppressWarnings docBlocks
 * @SuppressWarnings oneClassPerFile
 */
class BaseOrchTest extends TestCase {
	private $testOrch;
	private $pdo;

	/**
	 * Setup class to extend BaseOrch and FakePdo connection
	 */
	public function setUp(): void {
		$this->testOrch = new class extends BaseOrch {
			protected static $tableName = "testTable";
			protected static $fieldList = array('id, field1', 'field2', 'field3');
		};
		$this->pdo = Connection::getConnection(TestContainer::getContainer(), true)['conn'];
	}

	/**
	 * Test Read Functionality
	 */
	public function testRead() {
		$mockResult = [[ 'id' => 42, 'field1' => "expectedData1", 'field2' => "expectedData2", 'field3' => "expectedData3"]];
		$this->pdo->mock("SELECT * FROM testTable WHERE `id` = :id", $mockResult, array('id' => 42));

		$result = $this->testOrch::Read(42, TestContainer::getContainer());
		$this->assertEquals(42, $result['id'], 'Read id');
		$this->assertEquals('expectedData1', $result['field1'], 'Read expectedData1');
		$this->assertEquals('expectedData2', $result['field2'], 'Read expectedData2');
		$this->assertEquals('expectedData3', $result['field3'], 'Read expectedData3');

	}
	
	/**
	 * 
	 * Test for exception when calling create with id
	 */
	public function testCreateWithId() {
		$data = ['id' => 43];
		try {
			$this->testOrch::Create($data, TestContainer::getContainer());
			$this->fail("Expected Exception not thrown");
		} catch (\Throwable $e) {
			$this->assertEquals('Id cannot be specified on Create', $e->getMessage(), "Exception Message");
		}
	}

	/**
	 * Prove that create is using an Insert query by checking for exception when not mocking it
	 */
	public function testCreateNoMock() {
		$data = ['field1' => "Data1", 'field2' => "Data2", 'field3' => "Data3"];
		try {
			$this->testOrch::Create($data, TestContainer::getContainer());
			$this->fail("Expected Exception not thrown");
		} catch (\Pseudo\Exception $e) {
			$query = 'INSERT INTO testTable (`id, field1`, `field2`, `field3`) VALUES (:id, field1, :field2, :field3)';
			$expectedMessage = 'Attempting an operation on an un-mocked query is not allowed, the raw query: ' . $query;
			$this->assertEquals($expectedMessage, $e->getMessage(), "Exception Message");
		}
	}

	/**
	 * Test a successful create
	 */
	public function testCreate() {
		$readMockResult = [[ 'id' => 45, 'field1' => "myData1", 'field2' => "myData2", 'field3' => "myData3"]];
		$this->pdo->mock("SELECT * FROM testTable WHERE `id` = :id", $readMockResult, array('id' => 45));

		$createMockResult = [[ 'id' => 45 ]];
		$this->pdo->mock("INSERT INTO testTable (`id, field1`, `field2`, `field3`) VALUES (:id, field1, :field2, :field3)", $createMockResult, array('id' => 45));

		$this->pdo->setLastId(45);
		
		$data = ['field1' => "Data1", 'field2' => "Data2", 'field3' => "Data3"];
		$result = $this->testOrch::Create($data, TestContainer::getContainer());

		$this->assertEquals(45, $result['id'], 'Read id');
		$this->assertEquals('myData1', $result['field1'], 'Read expectedData1');
		$this->assertEquals('myData2', $result['field2'], 'Read expectedData2');
		$this->assertEquals('myData3', $result['field3'], 'Read expectedData3');
	}

	/**
	 * Test Exists Finding Record
	 */
	public function testExistsYes() {
		$mockResult = [[ 'id' => 42, 'field1' => "expectedData1", 'field2' => "expectedData2", 'field3' => "expectedData3"]];
		$this->pdo->mock("SELECT * FROM testTable WHERE `id` = :id", $mockResult, array('id' => 42));

		$result = $this->testOrch::exists(42, TestContainer::getContainer());
		$this->assertEquals(true, $result, "record should exist");
	}

	/**
	 * Test Exists No Record
	 */
	public function testExistsNo() {
		$mockResult = [];
		$this->pdo->mock("SELECT * FROM testTable WHERE `id` = :id", $mockResult, array('id' => 42));

		$result = $this->testOrch::exists(42, TestContainer::getContainer());
		$this->assertEquals(false, $result, "record should exist");
	}
}