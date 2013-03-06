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
		$log = ModelBase::factory('Log');

		$this->assertInstanceOf('\app\Model\ModelBase', $log);
		$this->assertInstanceOf('\app\Model\ModelLog', $log);
	}

	/**
	 * Cek fetching data
	 */
	public function testCekGetAllLog() {
		$this->createDummyLog();

		$log = new ModelLog();

		$allLogs = $log->getAllLog();

		$this->assertTrue(count($allLogs) > 0);
	}

	/**
	 * Cek update Log
	 */
	public function testCekUpdateLogModelLog() {
		$log = new ModelLog();

		$this->assertFalse($log->updateLog(NULL, array()));
		$this->assertFalse($log->updateLog(010101010, array()));

		// Valid update
		$dummyLog = $this->createDummyLog();

		$this->assertInstanceOf('\app\Parameter',$log->updateLog($dummyLog->getId(), array('after' => md5('anotherdummy'))));
	}
}