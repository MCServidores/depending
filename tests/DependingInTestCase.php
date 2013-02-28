<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

use app\Model\ModelBase;

abstract class DependingInTestCase extends PHPUnit_Framework_TestCase {

	protected $needDatabase = false;

	/**
	 * Main Setup
	 */
	public function setUp() {
		// Panggil before hook
		if (method_exists($this, 'before')) {
			$this->before();
		}

		// Perlukah membuka database?
		if ($this->needDatabase) {
			Propel::init(str_replace('app', 'conf', APPLICATION_PATH) . DIRECTORY_SEPARATOR . 'connection.php');
		}
	}

	/**
	 * Main Tear Down
	 */
	public function tearDown() {
		if (method_exists($this, 'after')) {
			$this->after();
		}

		// Perlukah menghapus data?
		if ($this->needDatabase) {
			$this->deleteDummyUser();
		}
	}

	/**
	 * Create dummy user
	 */
	public function createDummyUser() {
		return ModelBase::factory('Auth')->createUser('dummy', 'dummy@oot.com', 'secret');
	}

	/**
	 * Delete dummy user
	 */
	public function deleteDummyUser() {
		if (($dummyUser = ModelBase::ormFactory('UsersQuery')->findOneByName('dummy')) && ! empty($dummyUser)) {
			$dummyUser->delete();
		} elseif (($dummyUser = ModelBase::ormFactory('UsersQuery')->findOneByName('Not Dummy Anymore')) && ! empty($dummyUser)) {
			$dummyUser->delete();
		}
	}
}