<?php

namespace Tests\Functional;

require_once(__DIR__ . "/../../src/db/connection.php");
require_once(__DIR__ . "/testDependenciesContainer.php");

use TestContainer;
use Connection;

/**
 * Test Routes that do authentication
 * @SuppressWarnings checkProhibitedFunctions
 */
class AutocompleteApiTest extends BaseTestCase {
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
	public static function tearDownAfterClass() : void {
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
	public function setUp() : void {
		$this->pdo = Connection::getConnection(TestContainer::getContainer(), true)['conn'];
	}

	/**
	 * Autocomplete test
	 * @SuppressWarnings lineLength
	 */
	public function testGetAutocomplete() {
		$mockResult = [[ 
			'address' => 'email6@test.com'
		],
		[ 
			'address' => 'email2@test.com'
		]];
		$this->pdo->mock("SELECT address FROM email_address WHERE `address` LIKE :input ORDER BY `uses` DESC", $mockResult,array('input'=>'email%'));
		$response = $this->runApp('GET', '/email/autocomplete?text=email', null, false, false);
		$this->assertEquals(200, $response->getStatusCode());
		$parsedResp = json_decode((string)$response->getBody(), true);

		$this->assertEquals('email6@test.com', $parsedResp[0], 'first result');
		$this->assertEquals('email2@test.com', $parsedResp[1], 'second result');        
	}
}