<?php
namespace Tests\Functional;

require_once(__DIR__ . "/../../src/config/configReader.php");
use ConfigReader;

/**
 * Tests for configuration reader
 */
class ConfigReaderTest extends \PHPUnit_Framework_TestCase {
    private static $fileBackup;
    private static $filePath = __DIR__ . '/../../config.json';
    public static function setUpBeforeClass() {
        if (file_exists(self::$filePath)) {
            self::$fileBackup = file_get_contents(self::$filePath);
            unlink(self::$filePath);
        }
    }
    public static function tearDownAfterClass() {
        if (isset(self::$fileBackup)) {
            $file = fopen(__DIR__ . '/../../config.json', 'w');
            fwrite($file, self::$fileBackup);
            fclose($file);
        }
    }

    public function tearDown() {
        unlink(self::$filePath);
        ConfigReader::reset();
    }
    public function testBasicConfigFile() {
        $config = array();
        $config['display_error_details'] = true;
        $config['cors_origins'] = array('testurl.com');
        $config['mysql'] = array();

        $file = fopen(self::$filePath, 'w');
        fwrite($file, json_encode($config));
        fclose($file);
        $this->assertEquals(true, ConfigReader::getDisplayErrorDetails());
        $corsOrigins = ConfigReader::getCorsOrigins();
        $this->assertEquals(1, count($corsOrigins));
        $this->assertEquals('testurl.com', $corsOrigins[0]);
    }

    public function testMultiCORSConfigFile() {
        $config = array();
        $config['display_error_details'] = true;
        $config['cors_origins'] = array('testurl.com', 'testurl2.com');
        $config['mysql'] = array();

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