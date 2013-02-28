<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

namespace app;

use app\CacheBundleInterface;

/**
 * Application Cache Bundle
 *
 * @author depending.in Dev
 */
class CacheBundle implements CacheBundleInterface
{
	protected $content;
	protected $timestamp;

	/**
	 * Constructor
	 */
	public function __construct($content = '', $timestamp = 0) {
		$this->content = $content;
		$this->timestamp = date('d M Y,H:i',$timestamp);
	}

	/**
	 * Dump method
	 */
	public function dump() {
		return $this->content;
	}
}