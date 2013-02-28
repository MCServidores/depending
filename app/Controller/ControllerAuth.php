<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

namespace app\Controller;

use app\Parameter;
use app\Model\ModelBase;

/**
 * ControllerHome
 *
 * @author depending.in Dev
 */
class ControllerAuth extends ControllerBase
{
	/**
	 * Handler untuk GET/POST /auth/login
	 */
	public function actionLogin() {
		// Hanya untuk non-login user
		if ($this->acl->isLogin()) return $this->redirect('/home');

		// Data
		$this->layout = 'modules/auth/login.tpl';
		$data = ModelBase::factory('Template')->getAuthData(array('title' => 'Login'));

		// Proses form jika POST terdeteksi
		// @codeCoverageIgnoreStart
		if ($_POST) {
			$loginResult = ModelBase::factory('Auth')->login($_POST);

			// Cek hasil login
			if ($loginResult->get('success') === true) {
				// Login berhasil
				$this->setLogin($loginResult->get('data'));
				
				// Redirect ke after login url atau ke home
				return $this->redirect($this->session->get('redirectAfterLogin', '/home'));
			}

			$this->data->set('result', $loginResult);
		}
		// @codeCoverageIgnoreEnd

		// Render
		return $this->render($data);
	}

	/**
	 * Handler untuk GET/POST /auth/logingithub
	 */
	public function actionLogingithub() {
		// Hanya untuk non-login user
 		if ($this->acl->isLogin()) return $this->redirect('/home');

		// Beri flag
		$this->session->set('loginGithub', true);

		return $this->redirect('/github');
	}

	/**
	 * Handler untuk GET/POST /auth/logout
	 */
	public function actionLogout() {
		// Proses permintaan logout
		$this->session->clear();

		return $this->redirect('/home');
	}

	/**
	 * Handler untuk GET/POST /auth/register
	 */
	public function actionRegister() {
		// Hanya untuk non-login user
 		if ($this->acl->isLogin()) return $this->redirect('/home');

		// Data
		$this->layout = 'modules/auth/register.tpl';
		$data = ModelBase::factory('Template')->getAuthData(array('title' => 'Register'));

		// Proses form jika POST terdeteksi
		// @codeCoverageIgnoreStart
		if ($_POST) {
			$registrationResult = ModelBase::factory('Auth')->register($_POST);

			// Cek hasil registrasi
			if ($registrationResult->get('success') === true) {
				// Login berhasil
				$this->setLogin($registrationResult->get('data'));
				
				// Redirect ke after login url atau ke home
				return $this->redirect($this->session->get('redirectAfterLogin', '/home'));
			}

			$this->data->set('result', $registrationResult);
		}
		// @codeCoverageIgnoreEnd

		// Render
		return $this->render($data);
	}

	/**
	 * Handler untuk GET/POST /auth/registergithub
	 */
	public function actionRegistergithub() {
		// Hanya untuk non-login user
 		if ($this->acl->isLogin()) return $this->redirect('/home');

		return $this->redirect('/github');
	}

	/**
	 * Handler untuk GET/POST /auth/forgot
	 */
	public function actionForgot() {
		// Hanya untuk non-login user
 		if ($this->acl->isLogin()) return $this->redirect('/home');

 		// Proses form jika POST terdeteksi
		// @codeCoverageIgnoreStart
		if ($_POST) {
			$resetResult = new Parameter(array('success' => false));

			if ( ! isset($_POST['email']) || empty($_POST['email'])) {
				$resetResult->set('error', 'Enter your email!');
			} else {
				$sent = ModelBase::factory('Auth')->sendReset($_POST['email']);

				if ($sent) {
					// Redirect ke halaman utama
					$this->setAlert('info', $message ,2000,true);
					return $this->redirect('/home');
				} else {
					$resetResult->set('error', 'Email yang anda masukan belum terdaftar!');
				}
			}

			$this->data->set('result', $resetResult);
		}
		// @codeCoverageIgnoreEnd
		
		// Data
		$this->layout = 'modules/auth/forgot.tpl';
		$data = ModelBase::factory('Template')->getAuthData(array('title' => 'Forgot Password'));

		// Render
		return $this->render($data);
	}

	/**
	 * Handler untuk GET/POST /auth/reset
	 *
	 * @codeCoverageIgnore
	 */
	public function actionReset() {
 		if ( ! $this->acl->isLogin() && ($token = $this->getToken())) {
			// cek token reset
			$authResult = ModelBase::factory('Auth')->confirm($token);

			if ($authResult->get('success') == false) {
				throw new \InvalidArgumentException('Token reset tidak valid!');
			} 

			// Login
			$this->setLogin($authResult->get('data'));
 		} 

 		return $this->redirect('/setting/password');
	}

	/**
	 * Handler untuk GET/POST /auth/reconfirmation
	 */
	public function actionReconfirmation() {
		// @codeCoverageIgnoreStart
		// Hanya untuk login user
 		if ($this->acl->isLogin()) {
			// Resend
			$currentUser = $this->data->get('user');
			ModelBase::factory('Auth')->sendConfirmation($currentUser->get('Uid'));
 		}
		// @codeCoverageIgnoreEnd

		// Render
		return $this->redirect('/home');
	}

	/**
	 * Handler untuk GET/POST /auth/confirmation
	 */
	public function actionConfirmation() {
		// Ambil token
		$token = $this->getToken();

		// @codeCoverageIgnoreStart
		// cek hasil konfirmasi
		$confirmationResult = ModelBase::factory('Auth')->confirm($token);

		if ($confirmationResult->get('success') == false) {
			throw new \InvalidArgumentException('Token konfirmasi tidak valid!');
		} else {
			$message = 'Konfirmasi akun anda berhasil';
			$alert = ModelBase::factory('Template')->render('blocks/alert/success.tpl', compact('message'));
			$this->setAlert('info', $alert,5000,true);

			// Login jika belum
			$this->setLogin($confirmationResult->get('data'));
		}

		// Render
		return $this->redirect('/home');
		// @codeCoverageIgnoreEnd
	}
}
