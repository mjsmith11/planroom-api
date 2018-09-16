<?php
namespace Tests\Functional;

require_once(__DIR__ . '../../src/db/base/orch.php');
use BaseOrch;

class BaseOrchTest extends \PHPUnit_Framework_TestCase {
	private $testOrch;

	public function setUpBeforeClass() {
		$this->testOrch = new class extends BaseOrch {
			protected static $tableName = "job";
			protected static $fieldList = array("name", "bidDate", "subcontractorBidsDue", "prebidDateTime", "prebidAddress", "bidEmail", "bonding", "taxible");
		};
	}
}