<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

namespace app\Controller;

use app\Parameter;
use app\Model\ModelBase;
use app\Model\ModelTemplate;

/**
 * ControllerHome
 *
 * @author depending.in Dev
 */
class ControllerHome extends ControllerBase
{
	/**
	 * Handler untuk GET/POST /home/index
	 */
	public function actionIndex() {
		// @codeCoverageIgnoreStart
		// Exception untuk PHPUnit, yang secara otomatis selalu melakukan GET request ke / di akhir eksekusi
		if ($this->request->server->get('PHP_SELF', 'undefined') == 'vendor/bin/phpunit') {
			return $this->render('');
		}
		// @codeCoverageIgnoreEnd

		// If user already login, find out his repositories
		$repos = new Parameter(array());
		if ($this->session->get('login')) {
			$user = ModelBase::factory('User')->getQuery()->findPK($this->data->get('user')->get('Uid'));
			$filter = array(
				array('column' => 'Users', 'value' => $user),
			);

			$repos = ModelBase::factory('Repo')->getAllRepo(50,1,$filter);
		}

		// Template configuration
		$this->layout = 'modules/home/index.tpl';
		$data = ModelBase::factory('Template')->getHomeData(compact('repos'));

		// Render
		return $this->render($data);
	}

	/**
	 * Handler untuk GET/POST /home/import
	 */
	public function actionImport() {
		// For AJAX call only
		if ( ! $this->request->isXmlHttpRequest()) throw new \RangeException('You seems trying to access a different side of this app. Please stop.');

		// @codeCoverageIgnoreStart

		// Initialize result
		$success = false;
		$html = '';
		$repos = array();

		if ($this->session->get('login')) {
			$uid = $this->session->get('userId');
			$accessToken = $this->getToken();

			// Check his repository
			$repositories = ModelBase::factory('Github', new Parameter(array(
				'githubToken' => $accessToken,
				)))->getRepositories();

			// Update the repositories data
			if ($repositories instanceof Parameter) {
				ModelBase::factory('Repo')->updateUserRepositories($uid,$accessToken,$repositories);
			}

			if (!empty($repositories)) {
				$user = ModelBase::factory('User')->getQuery()->findPK($this->session->get('userId'));
				$repos = $user->getReposs();
			}

			$success = ! empty($repos);
			$html = ModelTemplate::render('blocks/list/repo.tpl',compact('repos'));
		}

		// Just for the sake of jQuery ajax delay
		sleep(1);

		return $this->renderJson(compact('success','html'));
		// @codeCoverageIgnoreEnd
	}
}
