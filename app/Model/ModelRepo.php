<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

namespace app\Model;

use app\Model\Orm\Repos;
use app\Parameter;

/**
 * ModelRepo
 *
 * @author depending.in Dev
 */
class ModelRepo extends ModelBase 
{
	protected $entity = 'Repos';
	protected $query = 'ReposQuery';

	/**
	 * Determine whether the UID is repo owner
	 *
	 * @param $rid Repo id
	 * @param $uid User id
	 * @return bool
	 */
	public function isOwner($rid,$uid) {
		if ($repo = $this->getQuery()->findPK($rid)) {
			if (empty($repo)) return false;

			$user = current($repo->getUserss());

			return (empty($user)) ? false : $user->getUid() === $uid;
		}
	}

	/**
	 * Fetch repo lists
	 *
	 * @param int Limit result 
	 * @param int Pagination
	 * @param array Filter
	 *
	 * @return array Array of repo object wrapped in ParameterBag
	 */
	public function getAllRepo($limit = 0, $page = 1, $filter = array()) {
		// Inisialisasi
		$repos = array();

		// Create user query
		$query = $this->getQuery();

		// Limit
		if ( ! empty($limit)) $query->limit($limit);

		// Offset
		if ( ! empty($page)) $query->offset(($page-1)*$limit);

		// Filter
		if ( ! empty($filter)) {
			foreach ($filter as $where) {
				if ( ! $where instanceof Parameter) {
					$where = new Parameter($where);
				}

				$query = ModelBase::filterQuery($query, $where);
			}
		}

		// Order
		$query->orderByFullName();

		if (($allRepos = $query->find()) && ! empty($allRepos)) {

			foreach ($allRepos as $singleRepo) {
				// Convert to plain array and adding any necessary data
				$repoData = $this->extractRepo($singleRepo->toArray());
				$repos[] = $repoData->all();
			}
		}

		return new Parameter($repos);
	}

	/**
	 * Retrieve repo data
	 *
	 * @param int $id Repo RID
	 * @return Parameter
	 */
	public function getRepo($id = NULL) {
		// Silly
		if (empty($id)) return false;

		// Get repo
		$repo = $this->getQuery()->findPK($id);

		if ($repo) {
			// Get other misc data
			$repoData = $this->extractRepo($repo->toArray());
			$repo = $repoData;
		}
		
		return $repo;
	}

	/**
	 * Create repo
	 *
	 * @param int $id
	 * @param string $name
	 * @param string $fullname
	 * @param string $description
	 * @param int $isFork
	 * @param int $isPrivate
	 * @return Repos 
	 */
	public function createRepo($id, $name, $fullname, $description, $isFork = 0, $isPrivate = 0) {
		// Create
		$repo = $this->getEntity();
		$repo->setRid($id);
		$repo->setName($name);
		$repo->setFullName($fullname);
		$repo->setDescription($description);
		$repo->setIsFork((int) $isFork);
		$repo->setIsPrivate((int) $isPrivate);
		$repo->setCreated(time());
		$repo->setUrlHtml('https://github.com/'.$fullname);
		$repo->setUrlGit('git://github.com/'.$fullname.'.git');
		$repo->setUrlHook('https://api.github.com/repos/'.$fullname.'/hooks');
		$repo->setData(serialize(array()));

		$repo->save();

		return $repo;
	}

	/**
	 * Update data repo
	 *
	 * @param int $id Repo RID
	 * @param array $data regular data
	 *
	 * @return mixed
	 */
	public function updateRepo($id = NULL, $data = array()) {
		// Silly
		if (empty($id)) return false;

		// Get user
		$repo = $this->getQuery()->findPK($id);

		if ($repo) {
			foreach ($data as $key => $value) {
				$setMethod = 'set'.ucfirst($key);
				if (is_callable(array($repo,$setMethod))) {
					if ($key == 'userss') {
						$repo->setUserss($value);
					} else {
						$repo = call_user_func_array(array($repo,$setMethod), array($value));
					}
				}
			}
		} else {
			return false;
		}

		$repo->save();

		return $this->getRepo($repo->getRid());
	}

	/**
	 * Update custom data repo
	 *
	 * @param int $id Repo RID
	 * @param array $data Custom data
	 *
	 * @return mixed
	 */
	public function updateRepoData($id = NULL, $data = array()) {
		// Silly
		if (empty($id)) return false;

		// Get repo
		$repo = $this->getQuery()->findPK($id);

		if ($repo) {
			// Get custom data
			$repoData = new Parameter($repo->toArray());
			$customData = $repoData->get('Data');

			// @codeCoverageIgnoreStart
			if (empty($customData)) {
				// Straight forward
				$repo->setData(serialize($data));
			} else {
				$repoDataSerialized = @fread($customData,10000);
				try {
					$currentRepoData = unserialize($repoDataSerialized);
					if ( ! $currentRepoData) $currentRepoData = array();
					$currentRepoData = array_merge($currentRepoData, $data);
				} catch (\Exception $e) {
					$currentRepoData = $data;
				}

				// Update custom data
				$repo->setData(serialize($currentRepoData));
			}
			// @codeCoverageIgnoreEnd
			
			$repo->save();

			return $this->getRepo($repo->getRid());
		} else {
			return false;
		}
	}

	/**
	 * Generate a log by hitting the hook
	 *
	 * @param Repo
	 */
	public function generateFirstLog(Repos $repo) {
		// Get the owner, and extract their token
		$owner = count($repo->getUserss()) == 1 ? current($repo->getUserss()) : NULL;
		$ownerData = ModelBase::factory('User')->getUser($owner->getUid());
        $token = $ownerData->get('GithubToken');

        // Only process valid request
		if (count($repo->getLogss()) == 0 && $ownerData->get('GithubToken')) {
			$hooked = false;
			$hook = ModelBase::factory('Github', new Parameter(array(
						'githubToken' => $ownerData->get('GithubToken'),
					)))->getHookData($repo->getFullName());

			if ($hook instanceof Parameter) {
				$hookUrl = $hook->get('test_url');

				// Hit the hook
				$response = ModelBase::factory('Github', new Parameter(array(
								'githubToken' => $ownerData->get('GithubToken'),
							)))->postData($hookUrl.'?access_token='.$ownerData->get('GithubToken'), array());

				if ($response->get('result')) {
					$httpCode = $response->get('head[http_code]',500,true);
					$hooked = $httpCode == 204;
				}
			}
		}
	}

	/**
	 * Update user's and its repositories data
	 *
	 * @param int User UID
	 * @param string Github AccessToken
	 * @param Parameter An array containing all of user's repos
	 */
	public function updateUserRepositories($uid, $accessToken, Parameter $repositories) {
		$user = ModelBase::factory('User')->getQuery()->findPK($uid);

		if ( ! empty($user) && ! empty($repositories)) {
			foreach ($repositories as $r) {
				// Get the active repo
				$repoId = $r->id;
				$activeRepo = $this->getRepo($repoId);

				if ( ! $activeRepo) {
					// Try to find by the full name
					$activeRepo = $this->getQuery()->findOneByFullName($r->full_name);

					if ($activeRepo) {
						// Use the exists id
						$repoId = $activeRepo->getRid();
					} else {
						// Create
						$activeRepo = $this->createRepo($r->id, $r->name, $r->full_name, $r->description, $r->fork, $r->private);
					}
				} 

				// Get the hook status
				$status = ModelBase::factory('Github', new Parameter(array(
					'githubToken' => $accessToken,
					)))->getHookData($r->full_name);

				if ($status instanceof Parameter) {
					$status = true;
				}

				// Update the repo data
				$repoData = array(
					'name' => $r->name,
					'fullName' => $r->full_name,
					'description' => $r->description,
					'isFork' => (int) $r->fork,
					'isPrivate' => (int) $r->private,
					'status' => (int) $status,
					'userss' => $this->wrapCollection($user),
				);

				$this->updateRepo($repoId, $repoData);
			}
		}
	}

	/**
	 * Build Build tab
	 *
	 * @param mixed $repoLogs
	 * @param Parameter $data
	 * @return String 
	 */
	public function buildBuildTab($repoLogs,Parameter $data) {
		$buildTab = NULL;

		// @codeCoverageIgnoreStart
		if ( ! empty($repoLogs) && count($repoLogs) > 0) {
			$data->set('logs',$repoLogs);
			$buildTab = ModelTemplate::render('blocks/list/log.tpl', $data->all());
		}
		// @codeCoverageIgnoreEnd
		

		return $buildTab;
	}

	/**
	 * Build Deps tab
	 *
	 * @param mixed $composer
	 * @param Parameter $data
	 * @return String 
	 */
	public function buildDepsTab($composer,Parameter $data) {
		$depsTab = NULL;

		// Get the packagesArrays
		$packagesArrays = ModelBase::factory('Worker')->getPackageDeps(new Parameter((array) $composer));
		
		// @codeCoverageIgnoreStart
		if ( ! empty($packagesArrays)) {
			// Build new array contains all above information
			$deps = new Parameter($packagesArrays);
			$data->set('deps',$deps);

			$depsTab = ModelTemplate::render('blocks/list/deps.tpl', $data->all());
		}
		// @codeCoverageIgnoreEnd
		

		return $depsTab;
	}

	/**
	 * Build tabs data
	 *
	 * @param id $rid
	 * @param string $buildTab
	 * @param string $depsTab
	 * @return Parameter 
	 */
	public function buildTabs($rid = NULL,$buildTab = NULL, $depsTab = NULL) {
		$depsLiState = 'active';
		$depsTabState = 'active in';
		$buildLiState = ' ';
		$buildTabState = '';

		if (empty($depsTab) && !empty($buildTab)) {
			$buildLiState = 'active';
			$buildTabState = 'active in';
			$depsLiState = ' ';
			$depsTabState = '';
		}

		$tabs = array(
			// Aktifitas tab
			new Parameter(array(
				'id' => 'deps', 
				'link' => 'All Dependencies', 
				'liClass' => $depsLiState, 
				'tabClass' => $depsTabState,
				'data' => empty($depsTab) ? '' : $depsTab)),

			// Artikel tab
			new Parameter(array(
				'id' => 'builds', 
				'link' => 'All Builds', 
				'liClass' => $buildLiState,
				'tabClass' => $buildTabState,
				'data' => empty($buildTab) ? '' : $buildTab)),
		);

		return $tabs;
	}

	/**
	 * Get the final status of a repo object
	 *
	 * @param Repos $repo
	 * @return string $status
	 */
	public function getStatus(Repos $repo) {
		$status = 'unknown';

		// Check whether this repo has an finished log
		if (($logs = $repo->getLogss()) && !empty($logs) && count($logs) > 0) {
			$repoLogsArray = $logs->getData();
			krsort($repoLogsArray);

			foreach ($repoLogsArray as $log) {
				if ($log->getStatus() > 0) {
					// The latest commit status
					switch ($log->getStatus()) {
						case 1:
							$status = 'outofdate';
							break;

						case 2:
							$status = 'needupdate';
							break;
						
						case 3:
							$status = 'uptodate';
							break;

						case 4:
							$status = 'none';
							break;
						
						default:
							$status = 'unknown';
							break;
					}

					break;
				}
			}
		}

		return $status;
	}

	/**
	 * Extract repo
	 *
	 * @param array
	 * @return Parameter
	 */
	protected function extractRepo($repoArrayData = array()) {
		$repoData = new Parameter($repoArrayData);
		$repoCustomData = $repoData->get('Data');

		// @codeCoverageIgnoreStart
		if ( ! empty($repoCustomData)) {
			// Get data from opening stream
			$streamName = (string) $repoCustomData;

			if (ModelBase::$stream->has($streamName)) {
				$repoDataSerialized = ModelBase::$stream->get($streamName);
			} else {
				$repoDataSerialized = @stream_get_contents($repoCustomData);
				ModelBase::$stream->set($streamName, $repoDataSerialized);
			}

			// Now write back
			$additionalData = unserialize($repoDataSerialized);
		}
		// @codeCoverageIgnoreEnd

		return $repoData;
	}
}