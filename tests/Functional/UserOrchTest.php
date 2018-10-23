<?php
    namespace Tests\Functional;

    require_once(__DIR__ . '/../../src/db/orchestrators/userOrch.php');
    require_once(__DIR__ . "/../../src/db/connection.php");
    require_once(__DIR__ . "/testDependenciesContainer.php");
    
    use UserOrch;
    use Connection;
    use TestContainer;

/**
 * Tests for the User Orch
 */
class UserOrchTest extends \PHPUnit_Framework_TestCase {
	private $pdo;

	/**
	 * Setup class to FakePdo connection
	 */
	public function setUp() {
		$this->pdo = Connection::getConnection(TestContainer::getContainer(), true)['conn'];
    }

    /**
	 * Test readByEmail Functionality
	 */
	public function testReadByEmail() {
		$mockResult = [[ 'id' => 12, 'field1' => "fakeData1", 'field2' => "fakeData2", 'field3' => "fakeData3"]];
		$this->pdo->mock("SELECT * FROM user WHERE `email` = :email", $mockResult);

		$result = UserOrch::readByEmail('test@email.com', TestContainer::getContainer());
		$this->assertEquals(12, $result['id'], 'Read id');
		$this->assertEquals('fakeData1', $result['field1'], 'Read fakeData1');
		$this->assertEquals('fakeData2', $result['field2'], 'Read fakeData2');
		$this->assertEquals('fakeData3', $result['field3'], 'Read fakeData3');
    }
    
    public function testCheckPasswordNoUser() {
        $mockResult = [];
        $this->pdo->mock("SELECT * FROM user WHERE `email` = :email", $mockResult);
        
        $result = UserOrch::checkPassword('test@email.com', 'password', TestContainer::getContainer());
        $this->assertFalse($result, "login should fail");
    }

    public function testCheckPasswordBadPassword() {
        /// hash is for 'password123'
        $mockResult = [['email' => 'test@email.com', 'password' => '$2y$10$XtLla3j.dySzJa4PA93mu.6lxIle5WbnRlQoa.la1LGSHXlmd/k3q']];
        $this->pdo->mock("SELECT * FROM user WHERE `email` = :email", $mockResult);
        
        $result = UserOrch::checkPassword('test@email.com', 'password', TestContainer::getContainer());
        $this->assertFalse($result, "login should fail");
    }

    public function testCheckPasswordSuccess() {
        /// hash is for 'password123'
        $mockResult = [['email' => 'test@email.com', 'password' => '$2y$10$XtLla3j.dySzJa4PA93mu.6lxIle5WbnRlQoa.la1LGSHXlmd/k3q']];
        $this->pdo->mock("SELECT * FROM user WHERE `email` = :email", $mockResult);
        
        $result = UserOrch::checkPassword('test@email.com', 'password123', TestContainer::getContainer());
        $this->assertTrue($result, "login should succeed");
    }
    
}