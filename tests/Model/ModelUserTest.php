<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

use app\Parameter;
use app\Model\ModelBase;
use app\Model\ModelUser;

class ModelUserTest extends DependingInTestCase {
	protected $needDatabase = true;

	/**
	 * Cek konsistensi model User instance
	 */
	public function testCekKonsistensiModelUser() {
		$user = ModelBase::factory('User');

		$this->assertInstanceOf('\app\Model\ModelBase', $user);
		$this->assertInstanceOf('\app\Model\ModelUser', $user);
	}

	/**
	 * Cek fetching data
	 */
	public function testCekGetAllUser() {
		$this->createDummyUser();

		$user = new ModelUser();

		$allUsers = $user->getAllUser(1,1,array(
			array('column'=>'name','value'=>'%ummy'),
		));

		$this->assertTrue(count($allUsers) > 0);
	}

	/**
	 * Cek update user
	 */
	public function testCekUpdateUserModelUser() {
		$user = new ModelUser();

		$this->assertFalse($user->updateUser(NULL, array()));
		$this->assertFalse($user->updateUser(010101010, array()));

		// Valid update
		$this->createDummyUser();
		$dummyUser = ModelBase::ormFactory('UsersQuery')->findOneByName('dummy');

		$this->assertInstanceOf('\app\Parameter',$user->updateUser($dummyUser->getUid(), array('name' => 'Not Dummy Anymore')));
	}

	/**
	 * Cek update user custom data
	 */
	public function testCekUpdateUserCustomModelUser() {
		$user = new ModelUser();

		$this->assertFalse($user->updateUserData(NULL, array()));
		$this->assertFalse($user->updateUserData(010101010, array()));

		// Valid update
		$this->createDummyUser();
		$dummyUser = ModelBase::ormFactory('UsersQuery')->findOneByName('dummy');

		$this->assertInstanceOf('\app\Parameter',$user->updateUserData($dummyUser->getUid(), array('realname' => 'Dummy User')));
	}

	/**
	 * Cek Build tabs
	 */
	public function testCekBuildTabsModelUser() {
		$user = new ModelUser();

		$projectTab = NULL;
		$packageTab = 'Something about his package';

		$this->assertCount(2, $user->buildTabs($projectTab,$packageTab));
	}
}