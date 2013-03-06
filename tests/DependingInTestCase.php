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
		// Call before hook
		if (method_exists($this, 'before')) {
			$this->before();
		}

		// Need to open database connection?
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

		// Need to delete data?
		if ($this->needDatabase) {
			$this->deleteDummyUser();
			$this->deleteDummyRepo();
			$this->deleteDummyLog();
		}
	}

	/**
	 * Create dummy repo
	 */
	public function createDummyRepo() {
		return ModelBase::factory('Repo')->createRepo('12345', 'dummy', 'coolaid/dummy', 'Coolest repository', 0, 1);
	}

	/**
	 * Delete dummy repo
	 */
	public function deleteDummyRepo() {
		if (($dummyRepo = ModelBase::ormFactory('ReposQuery')->findOneByName('dummy')) && ! empty($dummyRepo)) {
			$dummyRepo->delete();
		} elseif (($dummyRepo = ModelBase::ormFactory('ReposQuery')->findOneByName('anotherdummy')) && ! empty($dummyRepo)) {
			$dummyRepo->delete();
		}
	}

	/**
	 * Create dummy log
	 */
	public function createDummyLog() {
		return ModelBase::factory('Log')->createLog(md5('dummy_before'), md5('dummy'), 'master', 'http://dummy/commit/123', 'I did that', 'Mr. Dummy');
	}

	/**
	 * Delete dummy repo
	 */
	public function deleteDummyLog() {
		if (($dummyLog = ModelBase::ormFactory('LogsQuery')->findOneByAfter(md5('dummy'))) && ! empty($dummyLog)) {
			$dummyLog->delete();
		} elseif (($dummyLog = ModelBase::ormFactory('LogsQuery')->findOneByAfter(md5('anotherdummy'))) && ! empty($dummyLog)) {
			$dummyLog->delete();
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