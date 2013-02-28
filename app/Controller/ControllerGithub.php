<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

namespace app\Controller;

use app\Parameter;
use app\Model\ModelBase;
use app\Model\ModelGithub;

/**
 * ControllerGithub
 *
 * @author depending.in Dev
 */
class ControllerGithub extends ControllerBase
{
	/**
	 * Handler untuk GET/POST /home/index
	 */
	public function actionIndex() {
		// Set redirectUrl
		$redirectUrl = $this->data->get('baseUrl').$this->data->get('currentUrl');

		if ($this->request->server->get('code') || $this->data->get('getData[code]',NULL,true)) {
			$body = '';
			$code = $this->data->get('getData[code]',NULL,true);

			if (empty($code)) {
				$code = $this->request->server->get('code');
			}

			// Create a authentification request to github
			$accessToken = ModelBase::factory('Github', new Parameter(compact('redirectUrl')))
							->getAccessToken($code);

			if ( ! $accessToken) {
				// Try until die!
	            return $this->redirect($this->data->get('currentUrl'));
	        } 

	        // Success
			$this->session->set('GithubToken', $accessToken);

			return $this->redirect('/github/update');
		}
		
		// authorize
		return $this->redirect(ModelBase::factory('Github', new Parameter(compact('redirectUrl')))->getLoginUrl());
	}

	/**
	 * Handler untuk GET/POST /github/update
	 */
	public function actionUpdate() {
		// Set redirectUrl
		$redirectUrl = $this->data->get('baseUrl').$this->data->get('currentUrl');
		$githubToken = $this->session->get('GithubToken');
		$params = new Parameter(compact('redirectUrl','githubToken'));
		$user = ModelBase::factory('Github', $params)->getUser();

		if ( ! $user instanceof Parameter) {
			return $this->redirect(ModelBase::factory('Github', new Parameter(compact('redirectUrl','githubToken')))->getLoginUrl());
		}

		// @codeCoverageIgnoreStart
		if ($this->session->get('login') == false) {
			// Set flag di session
			$this->session->set('githubData', $user->all());
			
			// Tentukan proses apakah mau login atau daftar
			$loginResult = false;

			if ($this->session->get('loginGithub') == true) {
				$loginResult = ModelBase::factory('Auth')->loginGithub($user->all(), $this->session->get('GithubToken'));
			} 

			if ($loginResult instanceof Parameter && $loginResult->get('success') == true) {
				// User valid, proses authentifikasi berhasil
				$this->setLogin($loginResult->get('data'));

				return $this->redirect($this->session->get('redirectAfterLogin', '/home'));
			} else {
				// Persiapkan user untuk daftar
				$this->session->set('postData', $user->all());

				return $this->redirect('/auth/register');
			}

		} else {
			return $this->redirect($this->session->get('redirectAfterAuthenticated','/home'));
		}
		// @codeCoverageIgnoreEnd
	}

	
}
