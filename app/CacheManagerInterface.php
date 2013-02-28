<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

namespace app;

/**
 * Application Cache Manager Interface
 *
 * @author depending.in Dev
 */
interface CacheManagerInterface
{
	/**
	 * Check cache
	 *
	 * @param string cache key
	 * @return bool
	 */
	public function has($key);

	/**
	 * Get cache
	 *
	 * @param string cache key
	 * @return mixed False when fail, string when success
	 */
	public function get($key);

	/**
	 * Set cache
	 *
	 * @param string cache key
	 * @param string cache value
	 * @param int cache lifetime
	 * @return bool 
	 */
	public function set($key, $value, $lifetime);

	/**
	 * Delete cache
	 *
	 * @param string cache key
	 * @return bool
	 */
	public function remove($key);
}