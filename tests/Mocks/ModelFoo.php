<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

class ModelFoo {
	
	public function isOwner($rid,$uid) {
		return $rid == $uid;
	}
	
}