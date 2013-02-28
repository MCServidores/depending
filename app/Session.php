<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

namespace app;

use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Application Session
 *
 * @author depending.in Dev
 */
class Session extends SymfonySession
{

	/**
     * Constructor.
     *
     * @param SessionStorageInterface $storage    A SessionStorageInterface instance.
     * @param AttributeBagInterface   $attributes An AttributeBagInterface instance, (defaults null for default AttributeBag)
     * @param FlashBagInterface       $flashes    A FlashBagInterface instance (defaults null for default FlashBag)
     */
    public function __construct(SessionStorageInterface $storage = null, AttributeBagInterface $attributes = null, FlashBagInterface $flashes = null)
    {
    	if (defined('STDIN')) {
    		// This call came from CLI environment, stop!
    		$storage = new MockArraySessionStorage();
    	} 

    	parent::__construct($storage, $attributes, $flashes);
	}
}