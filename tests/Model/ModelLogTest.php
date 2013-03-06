<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

use app\Model\ModelBase;
use app\Model\ModelLog;

class ModelLogTest extends DependingInTestCase {
	protected $needDatabase = true;

	/**
	 * Cek konsistensi model Log instance
	 */
	public function testCekKonsistensiModelLog() {
		$Log = ModelBase::factory('Log');

		$this->assertInstanceOf('\app\Model\ModelBase', $Log);
		$this->assertInstanceOf('\app\Model\ModelLog', $Log);
	}

	/**
	 * Cek fetching data
	 */
	public function testCekGetAllLog() {
		$this->createDummyLog();

		$Log = new ModelLog();

		$allLogs = $Log->getAllLog();

		$this->assertTrue(count($allLogs) > 0);
	}

	/**
	 * Cek update Log
	 */
	public function testCekUpdateLogModelLog() {
		$auth = new ModelLog();

		$this->assertFalse($auth->updateLog(NULL, array()));
		$this->assertFalse($auth->updateLog(010101010, array()));

		// Valid update
		$dummyLog = $this->createDummyLog();

		$this->assertInstanceOf('\app\Parameter',$auth->updateLog($dummyLog->getId(), array('after' => md5('anotherdummy'))));
	}
}