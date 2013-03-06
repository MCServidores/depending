<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

use app\Model\ModelBase;

class ModelTemplateTest extends DependingInTestCase {

	/**
	 * Cek konsistensi model template instance
	 */
	public function testCekKonsistensiModelTemplate() {
		$template = ModelBase::factory('Template');

		$this->assertInstanceOf('\app\Model\ModelBase', $template);
		$this->assertInstanceOf('\app\Model\ModelTemplate', $template);
		$this->assertObjectHasAttribute('defaultData', $template);
	}

	/**
	 * Cek get default data
	 */
	public function testCekGetDefaultDataModelTemplate() {
		$template = ModelBase::factory('Template');
		$defaultData = $template->getDefaultData();

		$this->assertArrayHasKey('title', $defaultData);
		$this->assertArrayHasKey('content', $defaultData);
		$this->assertArrayHasKey('menu_top', $defaultData);
		$this->assertArrayHasKey('menu_bottom', $defaultData);
	}

	/**
	 * Cek set limit hash
	 */
	public function testCekLimitHashModelTemplate() {
		$template = ModelBase::factory('Template');
		$hash = md5('something');

		$this->assertEquals(10,strlen($template->setLimitHash($hash)));
	}

	/**
	 * Cek set log text
	 */
	public function testCekSetLogTextModelTemplate() {
		$template = ModelBase::factory('Template');
		$status = 0;

		$this->assertEquals('scheduled',$template->setLogText($status));
	}

	/**
	 * Cek set success text
	 */
	public function testCekSetSuccessTextModelTemplate() {
		$template = ModelBase::factory('Template');
		$status = 1;

		$this->assertEquals('success',$template->setSuccessText($status));
	}
}