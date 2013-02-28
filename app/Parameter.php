<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

namespace app;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Application Parameter
 *
 * @author depending.in Dev
 */
class Parameter extends ParameterBag
{
	/**
	 * Overide sebagai property accessor
	 */
	public function __call($method, $arguments = array()) {
		// If try to get parameter key
		if (empty($arguments) && ($attribute = $this->get($method)) && ! empty($attribute)) return $this->get($method);
	}
}