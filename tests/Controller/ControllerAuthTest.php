<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

use app\Controller\ControllerAuth;
use Symfony\Component\HttpFoundation\Request;

class ControllerAuthTest extends DependingInTestCase {

	/**
	 * Cek action Login
	 */
	public function testCekActionLoginAppControllerAuth() {
		$request = Request::create('/auth/login');
		$controllerAuth = new ControllerAuth($request);
		$response = $controllerAuth->actionLogin();

		$this->assertInstanceOf('\Symfony\Component\HttpFoundation\Response', $response);
		$this->assertEquals(200, $response->getStatusCode());
	}

	/**
	 * Cek action Logingithub
	 */
	public function testCekActionLoginAppViaGithubControllerAuth() {
		$request = Request::create('/auth/logingithub');
		$controllerAuth = new ControllerAuth($request);
		$response = $controllerAuth->actionLogingithub();

		$this->assertInstanceOf('\Symfony\Component\HttpFoundation\RedirectResponse', $response);
		$this->assertEquals(302, $response->getStatusCode());
	}

	/**
	 * Cek action Register
	 */
	public function testCekActionRegisterAppControllerAuth() {
		$request = Request::create('/auth/register');
		$controllerAuth = new ControllerAuth($request);
		$response = $controllerAuth->actionRegister();

		$this->assertInstanceOf('\Symfony\Component\HttpFoundation\Response', $response);
		$this->assertEquals(200, $response->getStatusCode());
	}

	/**
	 * Cek action Registergithub
	 */
	public function testCekActionRegisterAppViaGithubControllerAuth() {
		$request = Request::create('/auth/registergithub');
		$controllerAuth = new ControllerAuth($request);
		$response = $controllerAuth->actionRegistergithub();

		$this->assertInstanceOf('\Symfony\Component\HttpFoundation\RedirectResponse', $response);
		$this->assertEquals(302, $response->getStatusCode());
	}

	/**
	 * Cek action Forgot
	 */
	public function testCekActionForgotAppControllerAuth() {
		$request = Request::create('/auth/forgot');
		$controllerAuth = new ControllerAuth($request);
		$response = $controllerAuth->actionForgot();

		$this->assertInstanceOf('\Symfony\Component\HttpFoundation\Response', $response);
		$this->assertEquals(200, $response->getStatusCode());
	}

	/**
	 * Cek action Logout
	 */
	public function testCekActionLogoutControllerAuth() {
		$request = Request::create('/auth/logout');
		$controllerAuth = new ControllerAuth($request);
		$response = $controllerAuth->actionLogout();

		$this->assertInstanceOf('\Symfony\Component\HttpFoundation\RedirectResponse', $response);
		$this->assertEquals(302, $response->getStatusCode());
	}

	/**
	 * Cek action Reconfirmation
	 */
	public function testCekActionReconfirmationControllerAuth() {
		$request = Request::create('/auth/reconfirmation');
		$controllerAuth = new ControllerAuth($request);
		$response = $controllerAuth->actionReconfirmation();

		$this->assertInstanceOf('\Symfony\Component\HttpFoundation\RedirectResponse', $response);
		$this->assertEquals(302, $response->getStatusCode());
	}
	
	/**
	 * Cek action Confirm
	 */
	public function testCekActionConfirmationControllerAuth() {
		$request = Request::create('/auth/confirmation');
		$controllerAuth = new ControllerAuth($request);

		$this->setExpectedException('InvalidArgumentException', 'Token not found!');

		$controllerAuth->actionConfirmation();
	}

	/**
	 * Cek action Reset
	 */
	public function testCekActionResetControllerAuth() {
		$request = Request::create('/auth/reset');
		$controllerAuth = new ControllerAuth($request);

		$this->setExpectedException('InvalidArgumentException', 'Token not found!');

		$controllerAuth->actionReset();
	}
}