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
 * ControllerRepo
 *
 * @author depending.in Dev
 */
class ControllerRepo extends ControllerBase
{
	protected $repo;

	/**
	 * Share the Repo resolver on beforeAction
	 */
	public function beforeAction() {
		parent::beforeAction();

		// Either get the ID or get the FullName (from proxy controller)
		$id = $this->request->get('id');

		$repo = ModelBase::factory('Repo')->getQuery()->findPK($id);

		if ( ! $repo) {
			// Try the fullname
			$repo = ModelBase::factory('Repo')->getQuery()->findOneByFullName($id);
		}

		// If so far we couldnt locate the repo, then it was a non-valid request
		if (empty($repo)) {
			throw new \InvalidArgumentException('Could not locate the repo!');
		}

		$this->repo = $repo;
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
		$repoLogs = $this->repo->getLogss();

		if ( ! empty($repoLogs)) {
			$lastLog = end($repoLogs);
			reset($repoLogs);
			$repoLogsArray = $repoLogs->getData();
			krsort($repoLogsArray);
			$repoLogs = current(array_chunk(array_values($repoLogsArray),5));
		}

		// Inisialisasi build tab
		$buildTab = ModelBase::factory('Repo')->buildBuildTab($repoLogs, $this->data);

		// Inisialisasi deps tab
		$depsTab = NULL;

		// Finalisasi tabs
		$tabs = ModelBase::factory('Repo')->buildTabs($repo->get('rid'),$buildTab,$depsTab);

		// Template configuration
		$this->layout = 'modules/repo/index.tpl';
		$data = ModelBase::factory('Template')->getRepoData(compact('repo','owner','title','lastLog','tabs'));

		// Render
		return $this->render($data);
	}

	/**
	 * Handler untuk GET/POST /repo/status/:id
	 */
	public function actionStatus() {
		// Generate the image
		$file = ModelBase::factory('Repo')->getStatus($this->repo).'.png';
		$path = ASSET_PATH . DIRECTORY_SEPARATOR;
		$folder = 'status/';

		// Get the status image
		list($statusImageFile, $mime) = ModelBase::factory('Asset', new Parameter(compact('file','path','folder')))->getFileAttribute('img');

		// Get the image content
		$content = file_get_contents($statusImageFile);

		// Return image response
		return $this->render($content, 200, array('Content-Type' => $mime));
	}
}
