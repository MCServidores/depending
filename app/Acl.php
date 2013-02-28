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
	 * API utama untuk ACL
	 *
	 * API ini akan membaca anotasi yang berkaitan dengan resource
	 * yang akan diakses, untuk kemudian melakukan pengecekan terhadap
	 * status/role user dan menentukan apa yang boleh dan tidak boleh
	 *
	 * @param string Resource action  : article,forum,gallery,etc
	 * @param string Permission Type : read,write,delete
	 * @param int Resource id
	 * @param string Resource Jika tidak dipass, maka akan diambil dari request
	 * @return bool 
	 */
	public function isAllowed($permission = self::READ, $id = NULL, $action = '', $resource = NULL) {
		$granted = false;

		// Validasi resource
		$resource = (empty($resource) || ! class_exists($resource)) ? $this->getCurrentResource() : $resource;

		// Validasi action
		$action = (empty($action)) ? $action = $this->getCurrentAction() : $action;

		// Dapatkan driver
		$resourceReflection = new \ReflectionClass($resource);
		$driver = $this->reader->getClassAnnotation($resourceReflection, self::ANNOTATION);

		if ( ! empty($driver) && $driver instanceof AclDriverInterface && $driver->inRange($action)) {
			// Action ada dalam range, ambil config
			$config = $driver->getConfig($action);

			// Lihat permission
			$granted = $driver->grantPermission($permission, $config, $this->getCurrentRole());
		}

		return $granted;
	}

	/**
	 * Mengambil role user dalam request saat ini
	 *
	 * @return string User Role
	 */
	public function getCurrentRole() {
		return $this->session->get('role', 'guest');
	}

	/**
	 * Mengambil nama resource dalam request saat ini
	 *
	 * @return string Controller Class
	 */
	public function getCurrentResource() {
		return $this->request->get('class','Undefined');
	}

	/**
	 * Mengambil nama action dalam request saat ini
	 *
	 * @return string Controller Action
	 */
	public function getCurrentAction() {
		return $this->request->get('action','undefined');
	}

	/**
	 * isLogin
	 *
	 * Mengecek apakah user sedang login
	 */
	public function isLogin() {
		return (empty($this->session)) ? false : $this->session->get('login', false);
	}

	/**
	 * isContainGithubData
	 *
	 * Mengecek apakah user sedang login dengan Github
	 */
	public function isContainGithubData() {
		return (empty($this->session)) ? false : is_array($this->session->get('githubData'));
	}
}