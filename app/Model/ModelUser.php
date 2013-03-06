<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

namespace app\Model;

use app\Model\ModelTemplate;
use app\Model\Orm\Users;
use app\Parameter;

/**
 * ModelUser
 *
 * @author depending.in Dev
 */
class ModelUser extends ModelBase 
{
	protected $entity = 'Users';
	protected $query = 'UsersQuery';

	/**
	 * Fetch user lists
	 *
	 * @param int Limit result 
	 * @param int Pagination
	 * @param array Filter
	 *
	 * @return array Array of user object wrapped in ParameterBag
	 */
	public function getAllUser($limit = 0, $page = 1, $filter = array()) {
		// Inisialisasi
		$users = array();

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
		$query->orderByName();

		if (($allUsers = $query->find()) && ! empty($allUsers)) {

			foreach ($allUsers as $singleUser) {
				// Convert to plain array and adding any necessary data
				$userData = $this->extractUser($singleUser->toArray());
				$users[] = $userData->all();
			}
		}

		return new Parameter($users);
	}

	/**
	 * Ambil data user 
	 *
	 * @param int $id User UID
	 * @return Parameter
	 */
	public function getUser($id = NULL) {
		// Silly
		if (empty($id)) return false;

		// Get user
		$user = $this->getQuery()->findPK($id);

		if ($user) {
			// Get other misc data
			$userData = $this->extractUser($user->toArray());
			$user = $userData;
		}
		
		return $user;
	}

	/**
	 * Buat user
	 *
	 * @param string $username
	 * @param string $email
	 * @param string $password
	 * @return Users 
	 */
	public function createUser($username, $email, $password) {
		// Get last user
		$lastUser = $this->getQuery()->orderByUid('desc')->findOne();
		$uid = empty($lastUser) ? 1 : ($lastUser->getUid() + 1);

		$user = $this->getEntity();
		$user->setUid($uid);
		$user->setName($username);
		$user->setMail($email);
		$user->setPass($password);
		$user->setCreated(time());
		$user->setData(serialize(array()));

		$user->save();

		return $user;
	}

	/**
	 * Update data user
	 *
	 * @param int $id User UID
	 * @param array $data regular data
	 *
	 * @return mixed
	 */
	public function updateUser($id = NULL, $data = array()) {
		// Silly
		if (empty($id)) return false;

		// Get user
		$user = $this->getQuery()->findPK($id);

		if ($user) {
			foreach ($data as $key => $value) {
				$setMethod = 'set'.ucfirst($key);
				if (is_callable(array($user,$setMethod))) {
					$user = call_user_func_array(array($user,$setMethod), array($value));
				}
			}
		} else {
			return false;
		}

		$user->save();

		return $this->getUser($user->getUid());
	}

	/**
	 * Update custom data user
	 *
	 * @param int $id User UID
	 * @param array $data Custom data
	 *
	 * @return mixed
	 */
	public function updateUserData($id = NULL, $data = array()) {
		// Silly
		if (empty($id)) return false;

		// Get user
		$user = $this->getQuery()->findPK($id);

		if ($user) {
			// Get custom data
			$userData = new Parameter($user->toArray());
			$customData = $userData->get('Data');

			// @codeCoverageIgnoreStart
			if (empty($customData)) {
				// Straight forward
				$user->setData(serialize($data));
			} else {
				$userDataSerialized = @fread($customData,10000);
				try {
					$currentUserData = unserialize($userDataSerialized);
					if ( ! $currentUserData) $currentUserData = array();
					$currentUserData = array_merge($currentUserData, $data);
				} catch (\Exception $e) {
					$currentUserData = $data;
				}

				// Update custom data
				$user->setData(serialize($currentUserData));
			}
			// @codeCoverageIgnoreEnd
			
			$user->save();

			return $this->getUser($user->getUid());
		} else {
			return false;
		}
	}

	/**
	 * Build project tab
	 *
	 * @param Parameter $user
	 * @param Parameter $data
	 * @return String 
	 */
	public function buildProjectTab(Parameter $user,Parameter $data) {
		$projectTab = NULL;

		// Get related repos
		$repos = ModelBase::factory('User')->getQuery()->findPK($user->get('Uid'))->getReposs();

		// @codeCoverageIgnoreStart
		if ( ! empty($repos)) {
			$projectTab = ModelTemplate::render('blocks/list/project.tpl', compact('repos'));
		}
		// @codeCoverageIgnoreEnd
		

		return $projectTab;
	}

	/**
	 * Build tabs data
	 *
	 * @param id $uid
	 * @param string $projectTab
	 * @param string $packageTab
	 * @return Parameter 
	 */
	public function buildTabs($uid = NULL,$projectTab = NULL, $packageTab = NULL) {
		$projectLiState = 'active';
		$projectTabState = 'active in';
		$packageLiState = ' ';
		$packageTabState = '';

		if (empty($projectTab) && !empty($packageTab)) {
			$packageLiState = 'active';
			$packageTabState = 'active in';
			$projectLiState = ' ';
			$projectTabState = '';
		}

		$tabs = array(
			// Aktifitas tab
			new Parameter(array(
				'id' => 'projects', 
				'link' => 'All Projects', 
				'liClass' => $projectLiState, 
				'tabClass' => $projectTabState,
				'data' => empty($projectTab) ? '' : $projectTab)),

			// Artikel tab
			new Parameter(array(
				'id' => 'packages', 
				'link' => 'All Packages', 
				'liClass' => $packageLiState,
				'tabClass' => $packageTabState,
				'data' => empty($packageTab) ? '' : $packageTab)),
		);

		return $tabs;
	}

	/**
	 * Extract user
	 *
	 * @param array
	 * @return Parameter
	 */
	protected function extractUser($userArrayData = array()) {
		$userData = new Parameter($userArrayData);
		$userCustomData = $userData->get('Data');

		// @codeCoverageIgnoreStart
		if ( ! empty($userCustomData)) {
			// Get data from opening stream
			$streamName = (string) $userCustomData;

			if (ModelBase::$stream->has($streamName)) {
				$userDataSerialized = ModelBase::$stream->get($streamName);
			} else {
				$userDataSerialized = @stream_get_contents($userCustomData);
				ModelBase::$stream->set($streamName, $userDataSerialized);
			}

			// Now write back
			$additionalData = unserialize($userDataSerialized);
			$userData->set('AdditionalData', $additionalData);
			$userData->set('Fullname', isset($additionalData['name']) ? $additionalData['name'] : '-');
			$userData->set('GithubToken', isset($additionalData['github_access_token']) ? $additionalData['github_access_token'] : NULL);
		}
		// @codeCoverageIgnoreEnd

		$userData->set('Avatar', 'https://secure.gravatar.com/avatar/' . md5($userData->get('Mail')));
		$userData->set('Date', 'Since '.date('d M Y', $userData->get('Created')));
		$userData->set('LastLogin', 'Last seen '.date('d M', $userData->get('Login')));

		return $userData;
	}
}