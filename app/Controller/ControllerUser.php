<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

namespace app\Controller;

use app\Acl;
use app\AclDriver;
use app\Model\ModelBase;

/**
 * ControllerUser
 *
 * @author depending.in Dev
 * @AclDriver(
 *	name="User",
 * 	availableActions={"profile"},
 *	config={
 *
 *		"profile"={
 *			Acl::READ="all",
 *			Acl::WRITE="member,admin",
 *			Acl::EDIT="owner,admin",
 *			Acl::DELETE="admin"},
 * })
 */
class ControllerUser extends ControllerBase
{
	/**
	 * Handler untuk GET/POST /user/profile
	 */
	public function actionProfile() {
		$item = ModelBase::factory('User')->getUser($this->request->get('id'));

		// Check one or other mandatory fields
		// @codeCoverageIgnoreStart
		if ( empty($item) || ! $item->get('Mail') || ! $item->get('Name')) {
			throw new \LogicException('Maaf, ada yang salah dengan user_'.$this->request->get('id'));
		}
		// @codeCoverageIgnoreEnd

		// Project tab and package tab initialization
		$projectTab = ModelBase::factory('User')->buildProjectTab($item, $this->data);
		$packageTab = ModelBase::factory('User')->buildPackageTab($item, $this->data);

		// Finalisasi tabs
		$tabs = ModelBase::factory('User')->buildTabs($item->get('id'),$projectTab,$packageTab);

		// Template configuration
		$title = $item->get('Name');
		$this->layout = 'modules/user/profile.tpl';
		$data = ModelBase::factory('Template')->getUserData(compact('item', 'tabs', 'title'));

		// Render
		return $this->render($data);
	}
}
