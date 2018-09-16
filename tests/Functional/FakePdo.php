<?php
namespace Tests\Functional;

/**
 * Extension of Pseudo Pdo to change the way lastInsertId works
 */
class FakePdo extends \Pseudo\Pdo {
	private $lastId;

	/**
	 * Set the value to be returned when calling lastInsertId
	 * @param id value to set
	 * 
	 */
	public function setLastId($id) {
		$this->lastId = $id;
	}

	/**
	 * Last InsertId
	 * 
	 * @param name null
	 * @return int last inserted value
	 */
	public function lastInsertId($name = null) {
		$name = null;
		return $this->lastId;
	}
}