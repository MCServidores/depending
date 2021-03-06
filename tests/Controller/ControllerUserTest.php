<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

use app\Controller\ControllerUser;
use app\Model\ModelBase;
use Symfony\Component\HttpFoundation\Request;

class ControllerUserTest extends DependingInTestCase {
	protected $needDatabase = true;

	/**
	 * Set up
	 */
	public function before() {
		$_GET['page'] = '1';
		$_POST['query'] = 'facebook';
	}

	/**
	 * Tear down
	 */
	public function after() {
		unset($_GET['page']);
		unset($_POST['query']);
	}

	/**
	 * Cek konsistensi controller user instance
	 */
	public function testCekKonsistensiAppControllerUser() {
		$request = Request::create('/user');
		$controllerUser = new ControllerUser($request);

		$this->assertInstanceOf('\app\Controller\ControllerBase', $controllerUser);
		$this->assertInstanceOf('\app\Controller\ControllerUser', $controllerUser);
	}

	/**
	 * Cek action profile
	 */
	public function testCekActionProfileAppControllerUser() {
		$this->deleteDummyUser();
		$user = $this->createDummyUser();

		$request = Request::create('/user', 'GET', array('id' => $user->getUid()));
		$controllerUser = new ControllerUser($request);
		$response = $controllerUser->actionProfile();

		$this->assertInstanceOf('\Symfony\Component\HttpFoundation\Response', $response);
		$this->assertEquals(200, $response->getStatusCode());
	}
}