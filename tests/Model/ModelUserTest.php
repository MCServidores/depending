<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

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

		$allUsers = $user->getAllUser();

		$this->assertTrue(count($allUsers) > 0);
	}

	/**
	 * Cek update user
	 */
	public function testCekUpdateUserModelUser() {
		$auth = new ModelUser();

		$this->assertFalse($auth->updateUser(NULL, array()));
		$this->assertFalse($auth->updateUser(010101010, array()));

		// Valid update
		$this->createDummyUser();
		$dummyUser = ModelBase::ormFactory('UsersQuery')->findOneByName('dummy');

		$this->assertInstanceOf('\app\Parameter',$auth->updateUser($dummyUser->getUid(), array('name' => 'Not Dummy Anymore')));
	}

	/**
	 * Cek update user custom data
	 */
	public function testCekUpdateUserCustomModelUser() {
		$auth = new ModelUser();

		$this->assertFalse($auth->updateUserData(NULL, array()));
		$this->assertFalse($auth->updateUserData(010101010, array()));

		// Valid update
		$this->createDummyUser();
		$dummyUser = ModelBase::ormFactory('UsersQuery')->findOneByName('dummy');

		$this->assertInstanceOf('\app\Parameter',$auth->updateUserData($dummyUser->getUid(), array('realname' => 'Dummy User')));
	}
}