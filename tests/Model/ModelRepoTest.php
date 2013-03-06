<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

use app\Model\ModelBase;
use app\Model\ModelRepo;

class ModelRepoTest extends DependingInTestCase {
	protected $needDatabase = true;

	/**
	 * Cek konsistensi model Repo instance
	 */
	public function testCekKonsistensiModelRepo() {
		$repo = ModelBase::factory('Repo');

		$this->assertInstanceOf('\app\Model\ModelBase', $repo);
		$this->assertInstanceOf('\app\Model\ModelRepo', $repo);
	}

	/**
	 * Cek fetching data
	 */
	public function testCekGetAllRepo() {
		$this->createDummyRepo();

		$repo = new ModelRepo();

		$allRepos = $repo->getAllRepo();

		$this->assertTrue(count($allRepos) > 0);
	}

	/**
	 * Cek update Repo
	 */
	public function testCekUpdateRepoModelRepo() {
		$auth = new ModelRepo();

		$this->assertFalse($auth->updateRepo(NULL, array()));
		$this->assertFalse($auth->updateRepo(010101010, array()));

		// Valid update
		$this->createDummyRepo();
		$dummyRepo = ModelBase::ormFactory('ReposQuery')->findOneByName('dummy');

		$this->assertInstanceOf('\app\Parameter',$auth->updateRepo($dummyRepo->getRid(), array('name' => 'anotherdummy')));
	}

	/**
	 * Cek update Repo custom data
	 */
	public function testCekUpdateRepoCustomModelRepo() {
		$auth = new ModelRepo();

		$this->assertFalse($auth->updateRepoData(NULL, array()));
		$this->assertFalse($auth->updateRepoData(010101010, array()));

		// Valid update
		$this->createDummyRepo();
		$dummyRepo = ModelBase::ormFactory('ReposQuery')->findOneByName('dummy');

		$this->assertInstanceOf('\app\Parameter',$auth->updateRepoData($dummyRepo->getRid(), array('composer' => 'some_data')));
	}
}