<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

use app\Session as AppSession;
use app\Model\ModelBase;
use app\Controller\ControllerHome;
use Symfony\Component\HttpFoundation\Request;

class ControllerHomeTest extends DependingInTestCase {

	protected $needDatabase = true;
	protected $request;

	/**
	 * Set up
	 */
	public function before() {
		// Emulate logged in user
		$session = new AppSession();

		$session->start();

		$sessionId = $session->getName();
		$cookies = array($sessionId => TRUE);
		$request = Request::create('/home/index', 'GET', array(), $cookies);

		if ( ! $request->hasPreviousSession()) {
			$request->setSession($session);
		} 

		$request->getSession()->set('login', true);
		$this->request = $request;
	}

	/**
	 * Cek konsistensi controller base instance
	 */
	public function testCekKonsistensiAppControllerHome() {
		$request = Request::create('/home/index');
		$controllerHome = new ControllerHome($request);

		$this->assertInstanceOf('\app\Controller\ControllerBase', $controllerHome);
		$this->assertInstanceOf('\app\Controller\ControllerHome', $controllerHome);
		$this->assertObjectHasAttribute('request', $controllerHome);
	}

	/**
	 * Cek action index
	 */
	public function testCekActionIndexAppControllerHome() {
		// Non login user
		$request = Request::create('/home/index');
		$controllerHome = new ControllerHome($request);
		$response = $controllerHome->actionIndex();

		$this->assertInstanceOf('\Symfony\Component\HttpFoundation\Response', $response);
		$this->assertEquals(200, $response->getStatusCode());

		// Login user
		$dummyUser = $this->createDummyUser();
		$request = $this->request;
		$request->getSession()->set('userId', $dummyUser->getUid());
		$controllerHome = new ControllerHome($request);
		$response = $controllerHome->actionIndex();

		$this->assertInstanceOf('\Symfony\Component\HttpFoundation\Response', $response);
		$this->assertEquals(200, $response->getStatusCode());
	}

	/**
	 * Cek action import
	 */
	public function testCekActionImportAppControllerHome() {
		$request = Request::create('/home/import');
		$controllerHome = new ControllerHome($request);

		$this->setExpectedException('RangeException', 'You seems trying to access a different side of this app. Please stop.');

		$controllerHome->actionImport();
	}
}