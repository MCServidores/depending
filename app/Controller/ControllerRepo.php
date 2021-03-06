<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

namespace app\Controller;

use app\Acl;
use app\AclDriver;
use app\Parameter;
use app\Model\ModelBase;
use app\Model\ModelTemplate;

/**
 * ControllerRepo
 *
 * @author depending.in Dev
 * @AclDriver(
 *	name="Repo",
 * 	availableActions={"detail", "status"},
 *	config={
 *
 *		"detail"={
 *			Acl::READ="all",
 *			Acl::WRITE="owner,admin",
 *			Acl::EDIT="owner,admin",
 *			Acl::DELETE="admin"},
 * })
 */
class ControllerRepo extends ControllerBase
{
	protected $repo;

	/**
	 * Share the Repo resolver on beforeAction
	 */
	public function beforeAction() {
		parent::beforeAction();

		// Get the ID (from proxy controller or regular request)
		$id = $this->request->get('id');
		$repo = ModelBase::factory('Repo')->getQuery()->findPK($id);

		// If so far we couldnt locate the repo, then it was a non-valid request
		if (empty($repo) && ! preg_match('/\b(project|build|repo\/status)\b/i',substr($this->request->getPathInfo(),1))) {
			throw new \InvalidArgumentException('Could not locate the repo!');
		}

		$this->repo = $repo;
	}

	/**
	 * Handler untuk GET/POST /project
	 */
	public function actionIndex() {
		// Instantiate project section
		$listTitle = 'All Projects';
		$page = $this->data->get('getData[page]',1,true);
		$query = $this->data->get('getData[query]','',true);
		$filter = array();

		if ($_POST && isset($_POST['query'])) {
			$query = $_POST['query'];

			// Reset page
			$page = 1;
		}

		if ( ! empty($query)) {
			$listTitle = 'Searhing "'.$query.'"';

			$filter = array(
				array('column' => 'Name', 'value' => $query.'%', 'chainOrStatement' => TRUE),
				array('column' => 'FullName', 'value' => $query.'%', 'chainOrStatement' => TRUE),
			);
		}

		// Add filter for displaying only registered projects
		$filter[] = array('column' => 'Status', 'value' => 1);

		$searchQuery = $query;

		$repos = ModelBase::factory('Repo')->getAllRepo(10, $page, $filter);
		$pagination = ModelBase::buildPagination($repos,'ReposQuery', $filter, $page, 10);

		// Template configuration
		$this->layout = 'modules/repo/list.tpl';
		$data = ModelBase::factory('Template')->getRepoData(compact('repos','listTitle', 'listPage','pagination','searchQuery'));

		// Render
		return $this->render($data);
	}

	/**
	 * Handler untuk GET/POST /repo/detail/:id
	 */
	public function actionDetail() {
		// Return detail response
		$title = $this->repo->getFullName();
		$repo = ModelBase::factory('Repo')->getRepo($this->repo->getRid());
		$owners = ModelBase::factory('Repo')->getQuery()->findPK($this->repo->getRid())->getUserss();
		$owner = current($owners);
		$lastLog = new Parameter();
		$tabOption = NULL;

		// Set global permission of this resource
		$this->data->set('isAllowed',$this->acl->isAllowed(Acl::WRITE));

		if ($this->data->get('isAllowed')) {
			// Adding tab option to fetch the latest commits manually, for the owner
			$tabOption = array(
				'id' => 'refresh-log',
				'href' => $this->data->get('currentUrl').'?synchronize=1', 
				'text' => '<i class="icon icon-refresh"></i> Refresh'
			);

			// Need to synchronize?
			if ($this->data->get('getData[synchronize]','0',true) == '1') {
				// Get the hook status
				$hooked = false;
				$hook = ModelBase::factory('Github', new Parameter(array(
					'githubToken' => $this->getToken(),
					)))->getHookData($this->repo->getFullName());

				if ($hook instanceof Parameter) {
					$hookUrl = $hook->get('test_url');

					// Hit the hook
					$response = ModelBase::factory('Github', new Parameter(array(
					'githubToken' => $this->getToken(),
					)))->postData($hookUrl.'?access_token='.$this->getToken(), array());

					if ($response->get('result')) {
						$httpCode = $response->get('head[http_code]',500,true);
						$hooked = $httpCode == 204;
					}
				}
			}
		}

		// Get latest repo's logs
		$repoLogs = $this->repo->getLogss();

		// If there is no logs, generate one
		if (count($repoLogs) == 0) {
			ModelBase::factory('Repo')->generateFirstLog($this->repo);
			$repoLogs = ModelBase::factory('Repo')->getQuery()->findPK($this->repo->getRid())->getLogss();
		}

		if ($this->data->get('isAllowed')) {
			if ( ! empty($repoLogs)) {
				$lastLog = end($repoLogs);
				reset($repoLogs);

				$repoLogsArray = $repoLogs->getData();
				krsort($repoLogsArray);
				
				$repoLogs = current(array_chunk(array_values($repoLogsArray),5));
			}

			// Get the composer
			$composer = ModelBase::factory('Worker')->getComposer($this->repo);

			// Build tab and deps tab initialization
			$buildTab = ModelBase::factory('Repo')->buildBuildTab($repoLogs, $this->data);
			$depsTab = ModelBase::factory('Repo')->buildDepsTab($composer, $this->data);

			// Finalisasi tabs
			$tabs = ModelBase::factory('Repo')->buildTabs($repo->get('rid'),$buildTab,$depsTab);
		} else {
			// Empty id
			$id = 0;

			if ( ! empty($repoLogs) && count($repoLogs->getData()) > 0) {
				$repoLogsArray = $repoLogs->getData();
				krsort($repoLogsArray);
				$lastLog = current($repoLogsArray);
				reset($repoLogsArray);

				$id = $lastLog->getId();
			}


			$buildHtml = '<div class="bin">';

			// Validate the log
			$log = ModelBase::factory('Log')->getLog($id);

			if ( ! empty($log)) {
				// Trigger the build, if the user login and the log is not executed
				if ($this->acl->isLogin() && $log->get('Status') == 0) {
					$success = $this->doWork(false, $id, true);
				}

				$buildHtml .= ModelBase::factory('Template')->getBuildData($id);

				// Get the latest log
				$log = ModelBase::factory('Log')->getLog($id);

			}

			$buildHtml .= '</div>';
		}

		// Template configuration
		$this->layout = 'modules/repo/index.tpl';
		$data = ModelBase::factory('Template')->getRepoData(compact('repo','owner','title','lastLog','tabs', 'tabOption','isAllowed','buildHtml'));

		// Render
		return $this->render($data);
	}

	/**
	 * Handler untuk GET/POST /repo/status/:id
	 */
	public function actionStatus() {
		// Generate the image
		$status = (empty($this->repo)) ? 'unknown' : ModelBase::factory('Repo')->getStatus($this->repo);

		$file = $status.'.png';
		$path = ASSET_PATH . DIRECTORY_SEPARATOR;
		$folder = 'status/';

		// Get the status image
		list($statusImageFile, $mime) = ModelBase::factory('Asset', new Parameter(compact('file','path','folder')))->getFileAttribute('img');

		// Get the image content
		$content = file_get_contents($statusImageFile);

		// Return image response
		return $this->render($content, 200, array('Content-Type' => $mime));
	}

	/**
	 * Handler for GET/POST /build
	 */
	public function actionBuild() {
		// For AJAX call only
		if ( ! $this->request->isXmlHttpRequest()) throw new \RangeException('You seems trying to access a different side of this app. Please stop.');


		// @codeCoverageIgnoreStart

		// Initialize result
		$success = true;
		$status = 0;
		$html = '<div class="bin">';

		if (($id = $this->data->get('postData[id]',false,true)) && ! empty($id)) {
			// Validate the log
			$log = ModelBase::factory('Log')->getLog($id);

			if ( ! empty($log)) {
				// Trigger the build, if the user login and the log is not executed
				if ($this->acl->isLogin() && $log->get('Status') == 0) {
					$success = $this->doWork(false, $id, true);
				}

				$html .= ModelBase::factory('Template')->getBuildData($id);

				// Get the latest log
				$log = ModelBase::factory('Log')->getLog($id);

				if ( ! empty($log) && $log->get('Status') > 0) {
					// Update the status
					$status = $log->get('Status');
				}
			}
		}

		$html .= '</div>';
		// Just for the sake of jQuery ajax delay
		sleep(1);

		return $this->renderJson(compact('success','status','html'));
		// @codeCoverageIgnoreEnd
	}
}
