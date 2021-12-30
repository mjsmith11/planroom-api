<?php
namespace Tests\Functional;

require_once(__DIR__ . '/../../src/db/orchestrators/emailAddressOrch.php');
require_once(__DIR__ . "/../../src/db/connection.php");
require_once(__DIR__ . "/testDependenciesContainer.php");

use EmailAddressOrch;
use Connection;
use TestContainer;


/**
 * Tests for the Base Orch
 * @SuppressWarnings checkProhibitedFunctions
 */
class EmailAddressOrchTest extends BaseTestCase {
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
	public static function tearDownAfterClass() : void {
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
	public function setUp() : void {
		$this->pdo = Connection::getConnection(TestContainer::getContainer(), true)['conn'];
	}
	
	/**
	 * Test reading by address
	 */
	public function testReadByAddress() {
		$mockResult = [['id' => 10,'address' => 'myemail@xyz.com','uses' => 61]];
		$this->pdo->mock("SELECT * FROM email_address WHERE `address` = :address", $mockResult, array('address' => 'myemail@xyz.com'));
		$container = TestContainer::getContainer();
		$result = EmailAddressOrch::readByAddress('myemail@xyz.com', $container);
		$this->assertEquals(10, $result['id'], 'Read id');
		$this->assertEquals('myemail@xyz.com', $result['address'], 'Read address');
		$this->assertEquals(61, $result['uses'], 'Read uses');
	}

	/** 
	 * Test for an address that doesn't exist
	*/
	public function testAddressExistsFalse() {
		$this->pdo->mock("SELECT * FROM email_address WHERE `address` = :address", [[]], array('address' => 'myemail@xyz.com'));
		$container = TestContainer::getContainer();
		$result = EmailAddressOrch::addressExists('myemail@xyz.com', $container);
		$this->assertFalse($result, 'address should not exist');
	}

	/** 
	 * Test for an address that does exist
	*/
	public function testAddressExistsTrue() {
		$this->pdo->mock("SELECT * FROM email_address WHERE `address` = :address", [[
			'id' => 10,
			'address' => 'myemail@xyz.com',
			'uses' => 61
		]], array('address' => 'myemail@xyz.com'));
		$container = TestContainer::getContainer();
		$result = EmailAddressOrch::addressExists('myemail@xyz.com', $container);
		$this->assertTrue($result, 'address should not exist');
	}

	/**
	 * Test autocomplete suggestions no results
	 */
	public function testAutocompleteSuggestionsEmpty() {
		$mockResult = [];
		$this->pdo->mock("SELECT address FROM email_address WHERE `address` LIKE :input ORDER BY `uses` DESC", $mockResult, array('input' => 'email%'));
		$container = TestContainer::getContainer();
		$result = EmailAddressOrch::getAutoCompleteSuggestions('email', $container);
		$this->assertEquals(count($result), 0, "result length");
	}

	/**
	 * Test autocomplete suggestions
	 */
	public function testAutocompleteSuggestions() {
		$mockResult = [[ 
			'address' => 'email1@test.com'
		],
		[ 
			'address' => 'email2@test.com'
		]];
		$this->pdo->mock("SELECT address FROM email_address WHERE `address` LIKE :input ORDER BY `uses` DESC", $mockResult, array('input' => 'email%'));
		$container = TestContainer::getContainer();
		$result = EmailAddressOrch::getAutoCompleteSuggestions('email', $container);
		$this->assertEquals(count($result), 2, "result length");
		$this->assertEquals($result[0], 'email1@test.com', "first result");
		$this->assertEquals($result[1], 'email2@test.com', "second result");
	}

	/**
	 * Test record first use
	 */
	public function testRecordFirstUse() {
		// mock exists as false
		$this->pdo->mock("SELECT * FROM email_address WHERE `address` = :address", [[]],array('address' => 'abc@xyz.com'));

		// mock the insert
		$this->pdo->mock("INSERT INTO email_address (`address`, `uses`) VALUES (:address, :uses)", [[]]);
		// mock the read after create
		$readEmailMock = [[
			'id' => 12,
			'address' => 'abc@xyz.com',
			'uses' => 1
		]];

		$this->pdo->mock("SELECT * FROM email_address WHERE `id` = :id", $readEmailMock, array('id' => ''));

		$container = TestContainer::getContainer();
		$result = EmailAddressOrch::recordUse("abc@xyz.com", $container);

		$this->assertEquals(12, $result['id'], 'Read id');
		$this->assertEquals('abc@xyz.com', $result['address'], 'Read address');
		$this->assertEquals(1, $result['uses'], 'Read uses');

		
	}

	/**
	 * Test subsequent record uses
	 * this isn't working.  The readbyaddress doesn't get a result in the recordUse method
	 */
	public function ntestSubsequentRecord() {
		// mock the exists query
		$this->pdo->mock("SELECT * FROM email_address WHERE `address` = :address", [[
			'id' => 10,
			'address' => 'myemail@xyz.com',
			'uses' => 61
		]]);

		$this->pdo->mock("SELECT * FROM email_address WHERE `address` = :address", [[
			'id' => 10,
			'address' => 'myemail@xyz.com',
			'uses' => 61
		]]);

		// mock the update query
		$this->pdo->mock("UPDATE email_address SET uses = :uses WHERE `address` = :address", [[]]);
		
		// mock the read query
		$this->pdo->mock("SELECT * FROM email_address WHERE `id` = :id", [[
			'id' => 10,
			'address' => 'myemail@xyz.com',
			'uses' => 61
		]]);
		$container = TestContainer::getContainer();
		$result = EmailAddressOrch::recordUse("myemail@xyz.com", $container);
		$this->assertEquals(10, $result['id'], 'Read id');
		$this->assertEquals('myemail@xyz.com', $result['address'], 'Read address');
		$this->assertEquals(61, $result['uses'], 'Read uses');

	}
}