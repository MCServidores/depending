<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

namespace app;

use app\AclInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Annotations\AnnotationReader as Reader;
use app\AclDriver;

/**
 * Application ACL
 *
 * @author depending.in Dev
 */
class Acl implements AclInterface
{
	protected $request;
	protected $session;
	protected $reader;

	/**
	 * Constructor.
	 *
	 * @param Request $request Current request instance
	 * @param Reader  $reader Annotation reader instance
	 */
	public function __construct(Request $request, Reader $reader) {
		// Get request and extract the session
		$this->request = $request;
		$this->session = $this->request->getSession();

		// Get reader engine
		$this->reader = $reader;
	}

	/**
	 * isAllowed
	 *
	 * Main API for ACL
	 *
	 * this method will read related resource annotation (defined in its controller) 
	 * that accessed, and determine the user's role and his permission
	 *
	 * @param string Permission Type : read,write,delete (default to write, for security)
	 * @param int Resource id
	 * @param string Resource action  : article,forum,gallery,etc
	 * @param string Resource 
	 * @return bool 
	 */
	public function isAllowed($permission = self::WRITE, $id = NULL, $action = '', $resource = NULL) {
		$granted = false;

		// Resource validation
		$resource = (empty($resource) || ! class_exists($resource)) ? $this->getCurrentResource() : $resource;

		// Action validation
		$action = (empty($action)) ? $action = $this->getCurrentAction() : $action;

		// Get the driver
		$resourceReflection = new \ReflectionClass($resource);
		$driver = $this->reader->getClassAnnotation($resourceReflection, self::ANNOTATION);

		if ( ! empty($driver) && $driver instanceof AclDriverInterface && $driver->inRange($action)) {
			// Action is within valid range, get the config
			$config = $driver->getConfig($action);

			// See the permission
			$granted = $driver->grantPermission($permission, $config, $this->getCurrentRole());
		}

		return $granted;
	}

	/**
	 * Get current user role
	 *
	 * @return string User Role
	 */
	public function getCurrentRole() {
		// Run to check whether this user is the resource owner
		if ($this->isLogin()) {
			if ($this->isOwner($this->getCurrentResource(), $this->getCurrentAction())) return 'owner';
		}

		return $this->session->get('role', 'guest');
	}

	/**
	 * Get the accessed resource from request
	 *
	 * @return string Controller Class
	 */
	public function getCurrentResource() {
		return $this->request->get('class','Undefined');
	}

	/**
	 * Get the action within request instance
	 *
	 * @return string Controller Action
	 */
	public function getCurrentAction() {
		return $this->request->get('action','undefined');
	}

	/**
	 * Check if the current user is match with given UID
	 *
	 * @param $uid User id
	 * @return bool
	 */
	public function isMe($uid) {
		return $this->isLogin() && $this->session->get('userId',0) == $uid;
	}

	/**
	 * Check if the current user is owner with matched resource id
	 *
	 * @param $class Resource handler
	 * @param $action Resource action
	 * @return bool
	 */
	public function isOwner($class,$action) {
		$isOwner = false;
		$modelClass = str_replace('Controller', 'Model', $class);

		if (class_exists($modelClass)) {
			$model = new $modelClass();

			if (is_callable(array($model,'isOwner'))) {
				$isOwner = call_user_func_array(array($model,'isOwner'), 
				                                array($this->request->get('id',0),$this->session->get('userId',0)));
			}
		}

		return !empty($isOwner);
	}

	/**
	 * isLogin
	 *
	 * Check the request session to determine user login state
	 */
	public function isLogin() {
		return (empty($this->session)) ? false : $this->session->get('login', false);
	}

	/**
	 * isContainGithubData
	 *
	 * Check the request session and see whether it contain github data or not
	 */
	public function isContainGithubData() {
		return (empty($this->session)) ? false : is_array($this->session->get('githubData'));
	}
}