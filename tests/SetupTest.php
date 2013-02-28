<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

class SetupTest extends DependingInTestCase {

	/**
	 * Cek folder app
	 */
	public function testCekFolderApplikasi()
	{
		$folderAppExists = is_dir(realpath(__DIR__.'/../app'));

		$this->assertTrue($folderAppExists);
	}

	/**
	 * Cek folder ORM Models
	 */
	public function testCekFolderORMClasses()
	{
		$folderOrmExists = is_dir(realpath(__DIR__.'/../app/Model/Orm'));

		$this->assertTrue($folderOrmExists);
	}

	/**
	 * Cek folder vendor
	 */
	public function testCekFolderVendor()
	{
		$folderVendorExists = is_dir(realpath(__DIR__.'/../vendor'));
		
		$this->assertTrue($folderVendorExists);
	}

	/**
	 * Cek folder configuration file
	 */
	public function testCekFolderConf()
	{
		$folderConfExists = is_dir(realpath(__DIR__.'/../conf'));
		
		$this->assertTrue($folderConfExists);
	}
}