<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

namespace app\Model;

use app\Model\Orm\Repos;
use app\Model\Orm\Users;
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
	 * Fetch repo lists
	 *
	 * @param int Limit result 
	 * @param int Pagination
	 * @param array Filter
	 *
	 * @return array Array of user object wrapped in ParameterBag
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
	 * Update user's and its repositories data
	 *
	 * @param int User UID
	 * @param string Github AccessToken
	 * @param Parameter An array containing all of user's repos
	 * @return bool
	 */
	public function updateUserRepositories($uid, $accessToken, Parameter $repositories) {
		$user = ModelBase::factory('User')->getQuery()->findPK($uid);

		// Delete all existing repositories links
		$currentRepos = ModelBase::ormFactory('UsersReposQuery')->findByUsers($user);

		if ( ! empty($currentRepos)) $currentRepos->delete();

		if ( ! empty($user) && ! empty($repositories)) {
			foreach ($repositories as $r) {
				// Get the active repo
				$repoId = $r->id;
				$activeRepo = $this->getRepo($repoId);

				if ( ! $activeRepo) {
					// Create
					$activeRepo = $this->createRepo($r->id, $r->name, $r->full_name, $r->description, $r->fork, $r->private);
				} 

				// Get the hook status
				$status = ModelBase::factory('Github', new Parameter(array(
					'githubToken' => $accessToken,
					)))->getHookData($r->full_name);

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
	 * Get the final status of a repo object
	 *
	 * @param Repos $repo
	 * @return string $status
	 */
	public function getStatus(Repos $repo) {
		$status = 'unknown';

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