<?php
	namespace Tests\Functional;

	require_once(__DIR__ . '/../../src/jwt/orch.php');
	require_once(__DIR__ . "/../../src/db/connection.php");
	require_once(__DIR__ . "/testDependenciesContainer.php");
	require_once(__DIR__ . '/../../src/config/configReader.php');
	
	use \Planroom\JWT\Orch;
	use Connection;
	use TestContainer;
	use ConfigReader;
	use PHPUnit\Framework\TestCase;

/**
 * Tests for the JWT Orch
 * @SuppressWarnings checkProhibitedFunctions
 */
class JwtOrchTest extends TestCase {
	private $pdo;
	private static $fileBackup;
	private static $filePath = __DIR__ . '/../../config.json';

	/**
	 * Setup class to FakePdo connection
	 */
	public function setUp(): void {
		$this->pdo = Connection::getConnection(TestContainer::getContainer(), true)['conn'];
	}

	/**
	 * Set up for tests. Backup config file and delete it if it exists
	 */
	public static function setUpBeforeClass(): void {
		ConfigReader::reset(TestContainer::getContainer());
		if (file_exists(self::$filePath)) {
			self::$fileBackup = file_get_contents(self::$filePath);
			unlink(self::$filePath);
		}
		$config = array();
		$config['jwt'] = array('secret' => 'test', 'contractorExp' => 10);
		$file = fopen(self::$filePath, 'w');
		fwrite($file, json_encode($config));
		fclose($file);
	}
	
	/**
	 * After tests: Restore config file if it was backed up
	 */
	public static function tearDownAfterClass(): void {
		unlink(self::$filePath);
		if (isset(self::$fileBackup)) {
			$file = fopen(__DIR__ . '/../../config.json', 'w');
			fwrite($file, self::$fileBackup);
			fclose($file);
		}
		ConfigReader::reset(TestContainer::getContainer());
	}

	/**
	 * Test Token Generation for contractors
	 */
	public function testGetContractorToken() {
		/// hash is for 'password123'
		$mockResult = [['email' => 'test@email.com', 'password' => '$2y$10$XtLla3j.dySzJa4PA93mu.6lxIle5WbnRlQoa.la1LGSHXlmd/k3q']];
		$this->pdo->mock("SELECT * FROM user WHERE `email` = :email", $mockResult);

		$token = Orch::getContractorToken('test@email.com', TestContainer::getContainer());
		$decodedToken = \Firebase\JWT\JWT::decode($token, 'test', array('HS512'));
		$this->assertEquals($decodedToken->email, 'test@email.com', 'email in token');
		$this->assertTrue(time() + 600 - $decodedToken->exp <= 1, 'token expiration');
		$this->assertEquals($decodedToken->role, 'contractor', 'role in contractor token');
		$this->assertEquals($decodedToken->job, '*', 'job in contractor token');
	}

	/**
	 * Test Token Generation for subcontractors
	 */
	public function testGetSubcontractorToken() {
		$token = Orch::getSubcontractorToken('subcontractor@email.com', 42, 19999999999, TestContainer::getContainer());
		$decodedToken = \Firebase\JWT\JWT::decode($token, 'test', array('HS512'));
		$this->assertEquals($decodedToken->email, 'subcontractor@email.com', 'email in token');
		$this->assertEquals($decodedToken->exp, 19999999999, 'token expiration');
		$this->assertEquals($decodedToken->role, 'subcontractor', 'role in contractor token');
		$this->assertEquals($decodedToken->job, 42, 'job in contractor token');
	}
}