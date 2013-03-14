<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

// @codeCoverageIgnoreStart

/**
 * Global Constants
 */
defined('APPLICATION_DEBUG') OR define('APPLICATION_DEBUG', true);
defined('APPLICATION_PATH') OR define('APPLICATION_PATH', __DIR__);
defined('ASSET_PATH') OR define('ASSET_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public');
defined('CONFIG_PATH') OR define('CONFIG_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'conf');
defined('CACHE_PATH') OR define('CACHE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'cache');

if (($defaultAutoload = realpath(__DIR__ . '/../vendor/autoload.php')) && is_file($defaultAutoload)) {
	require $defaultAutoload;
} elseif (($autoload = realpath(__DIR__ . '/../../frameworks/vendor/autoload.php')) && is_file($autoload)) {
	require $autoload;
} else {
	die('Can not locate the vendor path');
}

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\EventListener\ExceptionListener;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\HttpKernel;
use Doctrine\Common\Annotations\AnnotationRegistry;
use app\Controller\ControllerBase;

include 'routes.php';

// Setting Propel
Propel::init(realpath(APPLICATION_PATH.'/../') . DIRECTORY_SEPARATOR . 'conf/connection.php');

// Setting Doctrine Component
AnnotationRegistry::registerAutoloadNamespace('app', realpath(__DIR__.'/../'));

$request = Request::createFromGlobals();

$context = new RequestContext();
$context->fromRequest($request);

$matcher = new UrlMatcher($routes, $context);

$dispatcher = new EventDispatcher();
$dispatcher->addSubscriber(new RouterListener($matcher));
$dispatcher->addSubscriber(new ExceptionListener(function (Request $request) {
	$handler = new ControllerBase($request);

    return $handler->handleException();
}));

$resolver = new ControllerResolver();

$kernel = new HttpKernel($dispatcher, $resolver);

$kernel->handle($request)->send();
// @codeCoverageIgnoreEnd
