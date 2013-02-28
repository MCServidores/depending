<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

use app\Model\ModelBase;

class ModelBaseTest extends DependingInTestCase {

	/**
	 * Cek konsistensi Model Factory
	 */
	public function testCekKonsistensiModelFactory() {
		$template = ModelBase::factory('Template');

		$this->assertInstanceOf('\app\Model\ModelBase', $template);
		$this->assertInstanceOf('\app\Model\ModelTemplate', $template);
		$this->setExpectedException('InvalidArgumentException', 'Model class not found');

		ModelBase::factory('Undefined');
	}

	/**
	 * Cek gracefully method
	 */
	public function testCekGracefulModelMethod() {
		$template = ModelBase::factory('Template');

		$this->setExpectedException('BadMethodCallException', get_class($template) . ' did not contain getUndefinedMethod');

		$template->getUndefinedMethod();
	}

	/**
	 * Cek konsistensi ORM Factory
	 */
	public function testCekKonsistensiOrmFactory() {
		$users = ModelBase::ormFactory('UsersQuery');

		$this->assertInstanceOf('\app\Model\Orm\om\BaseUsersQuery', $users);
		$this->assertInstanceOf('ModelCriteria', $users);
		$this->setExpectedException('InvalidArgumentException', 'ORM class not found');

		ModelBase::ormFactory('UndefinedQuery');
	}
}