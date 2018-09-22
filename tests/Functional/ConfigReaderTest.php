<?php
namespace Tests\Functional;

require_once(__DIR__ . "/../../src/config/configReader.php");
require_once(__DIR__ . "/testDependenciesContainer.php");

use ConfigReader;
use TestContainer;

/**
 * Tests for configuration reader
 * @SuppressWarnings checkProhibitedFunctions
 */
class ConfigReaderTest extends \PHPUnit_Framework_TestCase {
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
		ConfigReader::reset(TestContainer::getContainer());
	}

	/**
	 * Test with a basic config file
	 */
	public function testBasicConfigFile() {
		$config = array();
		$config['display_error_details'] = true;
		$config['cors_origins'] = array('testurl.com');
		$config['mysql'] = array();
		$config['logging'] = array('level' => 'debug', 'maxFiles' => 1);

		$file = fopen(self::$filePath, 'w');
		fwrite($file, json_encode($config));
		fclose($file);
		$this->assertEquals(true, ConfigReader::getDisplayErrorDetails());
		$corsOrigins = ConfigReader::getCorsOrigins();
		$this->assertEquals(1, count($corsOrigins));
		$this->assertEquals('testurl.com', $corsOrigins[0]);
	}

	/**
	 * Test a config file with multiple CORS urls to allow
	 */
	public function testMultiCORSConfigFile() {
		$config = array();
		$config['display_error_details'] = true;
		$config['cors_origins'] = array('testurl.com', 'testurl2.com');
		$config['mysql'] = array();
		$config['logging'] = array('level' => 'debug', 'maxFiles' => 1);


		$file = fopen(self::$filePath, 'w');
		fwrite($file, json_encode($config));
		fclose($file);
		$this->assertEquals(true, ConfigReader::getDisplayErrorDetails());
		$corsOrigins = ConfigReader::getCorsOrigins();

		$this->assertEquals(2, count($corsOrigins));
		$this->assertEquals('testurl.com', $corsOrigins[0]);
		$this->assertEquals('testurl2.com', $corsOrigins[1]);
	}
}