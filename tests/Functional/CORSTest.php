<?php

namespace Tests\Functional;

require_once(__DIR__ . "/../../src/config/configReader.php");
require_once(__DIR__ . "/testDependenciesContainer.php");

use ConfigReader;
use TestContainer;

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
	public static function setUpBeforeClass(): void {
		ConfigReader::reset(TestContainer::getContainer());
		if (file_exists(self::$filePath)) {
			self::$fileBackup = file_get_contents(self::$filePath);
			unlink(self::$filePath);
		}
	}

	/**
	 * After tests: Restore config file if it was backed up
	 */
	public static function tearDownAfterClass(): void {
		if (isset(self::$fileBackup)) {
			$file = fopen(__DIR__ . '/../../config.json', 'w');
			fwrite($file, self::$fileBackup);
			fclose($file);
		}
	}

	/**
	 * Remove the config file and reset the ConfigReader after each test
	 */
	public function tearDown(): void {
		unlink(self::$filePath);
		ConfigReader::reset(TestContainer::getContainer());
	}
	
	/**
	 * Test when an Allowed Origin should be present
	 */
	public function testValidOrigin() {
		$config = array();
		$config['display_error_details'] = true;
		$config['cors_origins'] = array('localhost');
		$config['mysql'] = array();
		$config['logging'] = array('level' => 'debug', 'maxFiles' => 1);
		$config['aws'] = array('region' => 'test-region');
		$config['jwt'] = array('secret' => 'test');
		$config['baseUrl'] = 'test.com';
		$config['mail'] = array();

		$file = fopen(self::$filePath, 'w');
		fwrite($file, json_encode($config));
		fclose($file);

		$response = $this->runApp('OPTIONS', '/jobs');
		$this->assertEquals('localhost', $response->getHeader('Access-Control-Allow-Origin')[0], "Allow-Origin Header");
		$this->assertEquals('Content-Type, Accept, Planroom-Authorization', $response->getHeader('Access-Control-Allow-Headers')[0], "Allow-Headers Header");
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
		$config['logging'] = array('level' => 'debug', 'maxFiles' => 1);
		$config['aws'] = array('region' => 'test-region');
		$config['jwt'] = array('secret' => 'test');
		$config['baseUrl'] = 'test.com';
		$config['mail'] = array();

		$file = fopen(self::$filePath, 'w');
		fwrite($file, json_encode($config));
		fclose($file);
		
		$response = $this->runApp('OPTIONS', '/jobs');
		$this->assertEquals(0, count($response->getHeader('Access-Control-Allow-Origin')), "No Allow-Origin Header");
		$this->assertEquals('Content-Type, Accept, Planroom-Authorization', $response->getHeader('Access-Control-Allow-Headers')[0], "Allow-Headers Header");
		$this->assertEquals('GET, POST, PUT, DELETE, OPTIONS', $response->getHeader('Access-Control-Allow-Methods')[0], "Allow-Methods Header");
		$this->assertEquals('86400', $response->getHeader('Access-Control-Max-Age')[0], 'Max-Age Header');
	}

	/**
	 * Test that there are no CORS headers on non-200 response
	 */
	public function testWith401() {
		$config = array();
		$config['display_error_details'] = true;
		$config['cors_origins'] = array('www.google.com');
		$config['mysql'] = array();
		$config['logging'] = array('level' => 'debug', 'maxFiles' => 1);
		$config['aws'] = array('region' => 'test-region');
		$config['jwt'] = array('secret' => 'test');
		$config['baseUrl'] = 'test.com';
		$config['mail'] = array();

		$file = fopen(self::$filePath, 'w');
		fwrite($file, json_encode($config));
		fclose($file);

		$response = $this->runApp('POST', '/jobs');
		$this->assertEquals(0, count($response->getHeader('Access-Control-Allow-Origin')), "No Allow-Origin Header");
		$this->assertEquals(0, count($response->getHeader('Access-Control-Allow-Headers')), "No Allow-Headers Header");
		$this->assertEquals(0, count($response->getHeader('Access-Control-Allow-Methods')), "No Allow-Methods Header");
		$this->assertEquals(0, count($response->getHeader('Access-Control-Max-Age')), 'No Max-Age Header');
	}
		
}