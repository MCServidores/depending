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
 * ControllerSetting
 *
 * @author depending.in Dev
 */
class ControllerSetting extends ControllerBase
{
	public function beforeAction() {
		parent::beforeAction();

		// All setting actions only accessible from logged-in user
		if ( ! $this->acl->isLogin()) {
			throw new \BadMethodCallException('You must login to continue!');
		}
	}

	/**
	 * Handler untuk GET/POST /setting/index
	 */
	public function actionIndex() {
		// Template configuration
		$this->layout = 'modules/setting/index.tpl';
		$data = ModelBase::factory('Template')->getSettingData();

		// Render
		return $this->render($data);
	}

	/**
	 * Handler untuk GET/POST /setting/info
	 */
	public function actionInfo() {
		$content = ModelBase::factory('Setting')->handleInfo($this->data);

		if ($content->get('updated')) $this->setAlert('info', 'Information updated!');

		// Template configuration
		$this->layout = 'modules/setting/index.tpl';
		$data = ModelBase::factory('Template')->getSettingData(compact('content'));

		// Render
		return $this->render($data);
	}

	/**
	 * Handler untuk GET/POST /setting/github
	 */
	public function actionGithub() {
		$content = ModelBase::factory('Setting')->handleGithub($this->data);

		if ($content->get('updated')) {
			$this->session->set('redirectAfterAuthenticated',$this->data->get('currentUrl'));

			return $this->redirect('/github');
		}

		// Template configuration
		$this->layout = 'modules/setting/index.tpl';
		$data = ModelBase::factory('Template')->getSettingData(compact('content'));

		// Render
		return $this->render($data);
	}

	/**
	 * Handler untuk GET/POST /setting/email
	 */
	public function actionMail() {
		$content = ModelBase::factory('Setting')->handleMail($this->data);

		if ($content->get('updated')) $this->setAlert('info', 'Email updated!');

		// Template configuration
		$this->layout = 'modules/setting/index.tpl';
		$data = ModelBase::factory('Template')->getSettingData(compact('content'));

		// Render
		return $this->render($data);
	}

	/**
	 * Handler untuk GET/POST /setting/password
	 */
	public function actionPassword() {
		$content = ModelBase::factory('Setting')->handlePassword($this->data);

		if ($content->get('updated')) $this->setAlert('info', 'Password updated!');

		// Template configuration
		$this->layout = 'modules/setting/index.tpl';
		$data = ModelBase::factory('Template')->getSettingData(compact('content'));

		// Render
		return $this->render($data);
	}

	/**
	 * Handler for AJAX POST to enable a repo hook
	 */
	public function actionEnable() {
		// For AJAX call only
		if ( ! $this->request->isXmlHttpRequest()) {
			throw new \RangeException('You seems trying to access a different side of this app. Please stop.');
		}

		// Just for the sake of jQuery ajax delay
		sleep(1);

		// Initialize result
		$success = false;
		$postData = new Parameter($this->data->get('postData'));

		if ($postData->get('repo')) {
			$uid = $this->session->get('userId');
			$accessToken = $this->getToken();

			// Enable repository hook
			$hookSetup = ModelBase::factory('Github', new Parameter(array(
				'githubToken' => $accessToken,
				)))->setHookData($postData->get('repo'));

			// Update the repo status data, if success
			if ($hookSetup) {
				$activeRepo = ModelBase::factory('Repo')->getQuery()->findOneByFullName($postData->get('repo'));
				$activeRepo->setStatus(1);
				$activeRepo->save();
			}

			$success = $hookSetup;
		}

		return $this->renderJson(compact('success'));
	}

	/**
	 * Handler for AJAX POST to enable a repo hook
	 */
	public function actionDisable() {
		// For AJAX call only
		if ( ! $this->request->isXmlHttpRequest()) {
			throw new \RangeException('You seems trying to access a different side of this app. Please stop.');
		}

		// Just for the sake of jQuery ajax delay
		sleep(1);

		// Initialize result
		$success = false;
		$postData = new Parameter($this->data->get('postData'));

		if ($postData->get('repo')) {
			$uid = $this->session->get('userId');
			$accessToken = $this->getToken();

			// Enable repository hook
			$hookSetup = ModelBase::factory('Github', new Parameter(array(
				'githubToken' => $accessToken,
				)))->removeHookData($postData->get('repo'));

			// Update the repo status data, if success
			if ($hookSetup) {
				$activeRepo = ModelBase::factory('Repo')->getQuery()->findOneByFullName($postData->get('repo'));
				$activeRepo->setStatus(0);
				$activeRepo->save();
			}

			$success = $hookSetup;
		}

		return $this->renderJson(compact('success'));
	}
}
