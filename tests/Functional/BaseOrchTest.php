<?php
namespace Tests\Functional;

require_once(__DIR__ . '/../../src/db/base/orch.php');
use BaseOrch;

class BaseOrchTest extends \PHPUnit_Framework_TestCase {
	private $testOrch;

	public function setUp() {
		$this->testOrch = new class extends BaseOrch {
			protected static $tableName = "testTable";
			protected static $fieldList = array('field1', 'field2', 'field3');
		};
	}
}