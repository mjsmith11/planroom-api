<?php
namespace Tests\Functional;

require_once(__DIR__ . "/../../src/config/configReader.php");
require_once(__DIR__ . "/testDependenciesContainer.php");
require_once(__DIR__ . "/../../src/s3/credentialProvider.php");
// require(__DIR__ . "/../../vendor/autoload.php");

use ConfigReader;
use TestContainer;

/**
 * Tests for AWS Credential Provider
 * @SuppressWarnings checkProhibitedFunctions
 */
class S3CredentialProviderTest extends \PHPUnit_Framework_TestCase {
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
		if (file_exists(self::$filePath)) {
			unlink(self::$filePath);
		}
		ConfigReader::reset(TestContainer::getContainer());
	}
	
	/**
	 * Test trying to get aws credentials without a config file
	 */
	public function testNoConfig() {
		try {
			$cp = \Planroom\S3\CredentialProvider::json(TestContainer::getContainer());
			$promise = $cp();
			$promise->wait();
			$this->fail('Exception not thrown');
		} catch (\Aws\Exception\CredentialsException $e) {
			$this->assertEquals($e->getMessage(), 'Error parsing config file', 'Expected exception message');
		}
	}
	
	/**
	 * Test trying to get aws credentials with a config file without a key
	 */
	public function testNoKey() {
		$config = array();
		$config['aws'] = array('secret' => 'mytestsecret', 'key' => '');

		$file = fopen(self::$filePath, 'w');
		fwrite($file, json_encode($config));
		fclose($file);

		try {
			$cp = \Planroom\S3\CredentialProvider::json(TestContainer::getContainer());
			$promise = $cp();
			$promise->wait();
			$this->fail('Exception not thrown');
		} catch (\Aws\Exception\CredentialsException $e) {
			$this->assertEquals($e->getMessage(), 'Could not find credentials in config.json', 'Expected exception message');
		}
	}
	
	/**
	 * Test trying to get aws credentials with a config file without a secret
	 */
	public function testNoSecret() {
		$config = array();
		$config['aws'] = array('secret' => '', 'key' => 'myTestKey');

		$file = fopen(self::$filePath, 'w');
		fwrite($file, json_encode($config));
		fclose($file);

		try {
			$cp = \Planroom\S3\CredentialProvider::json(TestContainer::getContainer());
			$promise = $cp();
			$promise->wait();
			$this->fail('Exception not thrown');
		} catch (\Aws\Exception\CredentialsException $e) {
			$this->assertEquals($e->getMessage(), 'Could not find credentials in config.json', 'Expected exception message');
		}
	}
	
	/**
	 * Test trying to get aws credentials with a valid config file
	 */
	public function testGoodCreds() {
		$config = array();
		$config['aws'] = array('secret' => 'mySecret', 'key' => 'myTestKey');

		$file = fopen(self::$filePath, 'w');
		fwrite($file, json_encode($config));
		fclose($file);

		$cp = \Planroom\S3\CredentialProvider::json(TestContainer::getContainer());
		$promise = $cp();
		$creds = $promise->wait();
		$this->assertEquals($creds->getSecretKey(), 'mySecret', 'secret in credentials');
		$this->assertEquals($creds->getAccessKeyId(), 'myTestKey', 'key in credentials');
	}
}