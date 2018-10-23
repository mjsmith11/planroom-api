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
		$config['logging'] = array('level' => 'debug', 'maxFiles' => 12);
		$config['aws'] = array('region' => 'test-region');
		$config['jwt'] = array('secret' => 'test', 'contractorExp' => 42);

		$file = fopen(self::$filePath, 'w');
		fwrite($file, json_encode($config));
		fclose($file);
		$this->assertEquals(true, ConfigReader::getDisplayErrorDetails());
		$corsOrigins = ConfigReader::getCorsOrigins();
		$this->assertEquals(1, count($corsOrigins));
		$this->assertEquals('testurl.com', $corsOrigins[0]);
		$this->assertEquals(12, ConfigReader::getMaxLogFiles(), "Expected Max Log Files");
		$this->assertEquals(\Monolog\Logger::DEBUG, ConfigReader::getLogLevel(), "Expected Log Level");
		$jwtInfo = ConfigReader::getJwtInfo();
		$this->assertEquals('test', $jwtInfo['secret'], "Expected jwt secret");
		$this->assertEquals(42, $jwtInfo['contractorExp'], "Expected jwt contractor expiration");
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
		$config['aws'] = array('region' => 'test-region');


		$file = fopen(self::$filePath, 'w');
		fwrite($file, json_encode($config));
		fclose($file);
		$this->assertEquals(true, ConfigReader::getDisplayErrorDetails());
		$corsOrigins = ConfigReader::getCorsOrigins();

		$this->assertEquals(2, count($corsOrigins));
		$this->assertEquals('testurl.com', $corsOrigins[0]);
		$this->assertEquals('testurl2.com', $corsOrigins[1]);
	}

	/**
	 * Test INFO value of log level enum
	 */
	public function testInfoLogLevel() {
		$config = array();
		$config['display_error_details'] = true;
		$config['cors_origins'] = array('testurl.com');
		$config['mysql'] = array();
		$config['logging'] = array('level' => 'info', 'maxFiles' => 12);
		$config['aws'] = array('region' => 'test-region');

		$file = fopen(self::$filePath, 'w');
		fwrite($file, json_encode($config));
		fclose($file);

		$this->assertEquals(\Monolog\Logger::INFO, ConfigReader::getLogLevel(), "Expected Log Level");
	}

	/**
	 * Test NOTICE value of log level enum
	 */
	public function testNoticeLogLevel() {
		$config = array();
		$config['display_error_details'] = true;
		$config['cors_origins'] = array('testurl.com');
		$config['mysql'] = array();
		$config['logging'] = array('level' => 'notice', 'maxFiles' => 12);
		$config['aws'] = array('region' => 'test-region');

		$file = fopen(self::$filePath, 'w');
		fwrite($file, json_encode($config));
		fclose($file);

		$this->assertEquals(\Monolog\Logger::NOTICE, ConfigReader::getLogLevel(), "Expected Log Level");
	}

	/**
	 * Test WARNING value of log level enum
	 */
	public function testWarningLogLevel() {
		$config = array();
		$config['display_error_details'] = true;
		$config['cors_origins'] = array('testurl.com');
		$config['mysql'] = array();
		$config['logging'] = array('level' => 'warning', 'maxFiles' => 12);
		$config['aws'] = array('region' => 'test-region');

		$file = fopen(self::$filePath, 'w');
		fwrite($file, json_encode($config));
		fclose($file);

		$this->assertEquals(\Monolog\Logger::WARNING, ConfigReader::getLogLevel(), "Expected Log Level");
	}

	/**
	 * Test ERROR value of log level enum
	 */
	public function testErrorLogLevel() {
		$config = array();
		$config['display_error_details'] = true;
		$config['cors_origins'] = array('testurl.com');
		$config['mysql'] = array();
		$config['logging'] = array('level' => 'error', 'maxFiles' => 12);
		$config['aws'] = array('region' => 'test-region');

		$file = fopen(self::$filePath, 'w');
		fwrite($file, json_encode($config));
		fclose($file);

		$this->assertEquals(\Monolog\Logger::ERROR, ConfigReader::getLogLevel(), "Expected Log Level");
	}

	/**
	 * Test CRITICAL value of log level enum
	 */
	public function testCriticalLogLevel() {
		$config = array();
		$config['display_error_details'] = true;
		$config['cors_origins'] = array('testurl.com');
		$config['mysql'] = array();
		$config['logging'] = array('level' => 'critical', 'maxFiles' => 12);
		$config['aws'] = array('region' => 'test-region');

		$file = fopen(self::$filePath, 'w');
		fwrite($file, json_encode($config));
		fclose($file);

		$this->assertEquals(\Monolog\Logger::CRITICAL, ConfigReader::getLogLevel(), "Expected Log Level");
	}

	/**
	 * Test ALERT value of log leve enum
	 */
	public function testAlertLogLevel() {
		$config = array();
		$config['display_error_details'] = true;
		$config['cors_origins'] = array('testurl.com');
		$config['mysql'] = array();
		$config['logging'] = array('level' => 'alert', 'maxFiles' => 12);
		$config['aws'] = array('region' => 'test-region');

		$file = fopen(self::$filePath, 'w');
		fwrite($file, json_encode($config));
		fclose($file);

		$this->assertEquals(\Monolog\Logger::ALERT, ConfigReader::getLogLevel(), "Expected Log Level");
	}

	/**
	 * Test EMERGENCY value of log leve enum
	 */
	public function testEmergencyLogLevel() {
		$config = array();
		$config['display_error_details'] = true;
		$config['cors_origins'] = array('testurl.com');
		$config['mysql'] = array();
		$config['logging'] = array('level' => 'emergency', 'maxFiles' => 12);
		$config['aws'] = array('region' => 'test-region');

		$file = fopen(self::$filePath, 'w');
		fwrite($file, json_encode($config));
		fclose($file);

		$this->assertEquals(\Monolog\Logger::EMERGENCY, ConfigReader::getLogLevel(), "Expected Log Level");
	}

	/**
	 * Test Unknown value of log leve enum
	 */
	public function testUnknownLogLevel() {
		$config = array();
		$config['display_error_details'] = true;
		$config['cors_origins'] = array('testurl.com');
		$config['mysql'] = array();
		$config['logging'] = array('level' => 'unknown', 'maxFiles' => 12);
		$config['aws'] = array('region' => 'test-region');

		$file = fopen(self::$filePath, 'w');
		fwrite($file, json_encode($config));
		fclose($file);

		try {
			ConfigReader::getLogLevel();
			$this->fail("Expected an exception to be thrown");
		} catch (\Throwable $e) {
			$this->assertEquals('Undefined index: unknown', $e->getMessage(), "Exception Message");
		}
	}

	/**
	 * Test aws config
	 */
	public function testAwsConfig() {
		$config = array();
		$config['display_error_details'] = true;
		$config['cors_origins'] = array('testurl.com');
		$config['mysql'] = array();
		$config['logging'] = array('level' => 'unknown', 'maxFiles' => 12);
		$config['aws'] = array('key' => 'mytestkey', 'secret' => 'mytestsecret', 'region' => 'test-region', 'bucket' => 'some-bucket', 'urlExpiration' => 42);

		$file = fopen(self::$filePath, 'w');
		fwrite($file, json_encode($config));
		fclose($file);

		$actualAwsConfig = ConfigReader::getAwsConfig();

		$this->assertFalse(isset($actualAwsConfig['key']), 'AWS Key should not be returned');
		$this->assertFalse(isset($actualAwsConfig['secret']), 'AWS Secret should not be returned');
		$this->assertEquals($actualAwsConfig['region'], 'test-region', 'AWS region');
		$this->assertEquals($actualAwsConfig['bucket'], 'some-bucket', 'AWS bucket');
		$this->assertEquals($actualAwsConfig['urlExpiration'], 42, 'AWS urlExpiration');
	}
}