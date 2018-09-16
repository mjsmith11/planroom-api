<?php

namespace Tests\Functional;

require_once(__DIR__ . "/../../src/config/configReader.php");
use ConfigReader;

/**
 * Test Route OPTIONS route for CORS
 * @SuppressWarnings checkProhibitedFunctions
 */
class CORSTest extends BaseTestCase {
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
	}

	/**
	 * After tests: Restore config file if it was backed up
	 */
	public static function tearDownAfterClass() {
		if (isset(self::$fileBackup)) {
			$file = fopen(__DIR__ . '/../../config.json', 'w');
			fwrite($file, self::$fileBackup);
			fclose($file);
		}
	}

	/**
	 * Remove the config file and reset the ConfigReader after each test
	 */
	public function tearDown() {
		unlink(self::$filePath);
		ConfigReader::reset();
	}
	
	/**
	 * Test when an Allowed Origin should be present
	 */
	public function testValidOrigin() {
		$config = array();
		$config['display_error_details'] = true;
		$config['cors_origins'] = array('localhost');
		$config['mysql'] = array();

		$file = fopen(self::$filePath, 'w');
		fwrite($file, json_encode($config));
		fclose($file);
		
		$response = $this->runApp('OPTIONS', '/');
		$this->assertEquals('localhost', $response->getHeader('Access-Control-Allow-Origin')[0], "Allow-Origin Header");
		$this->assertEquals('Content-Type, Accept', $response->getHeader('Access-Control-Allow-Headers')[0], "Allow-Headers Header");
		$this->assertEquals('GET, POST, PUT, DELETE, OPTIONS', $response->getHeader('Access-Control-Allow-Methods')[0], "Allow-Methods Header");
		$this->assertEquals('86400', $response->getHeader('Access-Control-Max-Age')[0], 'Max-Age Header');
	}

	/**
	 * Test when an Allowed Origin should not be present
	 */
	public function testInvalidOrigin() {
		$config = array();
		$config['display_error_details'] = true;
		$config['cors_origins'] = array('www.google.com');
		$config['mysql'] = array();

		$file = fopen(self::$filePath, 'w');
		fwrite($file, json_encode($config));
		fclose($file);
		
		$response = $this->runApp('OPTIONS', '/');
		$this->assertEquals(0, count($response->getHeader('Access-Control-Allow-Origin')), "No Allow-Origin Header");
		$this->assertEquals('Content-Type, Accept', $response->getHeader('Access-Control-Allow-Headers')[0], "Allow-Headers Header");
		$this->assertEquals('GET, POST, PUT, DELETE, OPTIONS', $response->getHeader('Access-Control-Allow-Methods')[0], "Allow-Methods Header");
		$this->assertEquals('86400', $response->getHeader('Access-Control-Max-Age')[0], 'Max-Age Header');
	}
		
}