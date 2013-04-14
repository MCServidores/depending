<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

namespace app\Controller;

use Doctrine\Common\Annotations\AnnotationReader as Reader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\FlattenException;
use app\Acl;
use app\Session;
use app\Parameter;
use app\CacheManager;
use app\Model\ModelBase;
use \Phing;
use \ConfigurationException;

/**
 * ControllerBase
 *
 * @author depending.in Dev
 */
class ControllerBase {

	const PRODUCTION = 'depending.in';
	const BEFORE_ACTION = 'beforeAction';
	protected $request;
	protected $session;
	protected $acl;
	protected $data;
	protected $layout = 'layout.tpl';

	 /**
	 * This is internally used by Travis-CI
	 *
	 * @codeCoverageIgnore
	 * @see app/routes.php
	 */
	public function actionSynchronize() {
		$success = TRUE;
		$error = '';
		$userAgent = $this->request->server->get('HTTP_USER_AGENT');

		$timeStart = microtime(TRUE);

		// TODO : More filtering
		if (strpos($userAgent, 'curl') !== FALSE) {
			passthru('cd ../;git status;git checkout master;git fetch origin;git merge origin/develop;git checkout master;git push origin master;git log --name-status HEAD^..HEAD');
			
			// Generate ORM
			try {
				$propelPath = str_replace('depending.in/app', 'depending.in/vendor', APPLICATION_PATH).DIRECTORY_SEPARATOR.'propel/propel1/generator/build.xml';
				$projectPath = str_replace('depending.in/app','depending.in',APPLICATION_PATH);
				$args = array(
						'-f',
						$propelPath,
						'-Dusing.propel-gen=true',
						'-Dproject.dir='.$projectPath,
						'om',
						'diff',
						'migrate',
				);

				Phing::startup();
				Phing::fire($args);

			    // Invoke any shutdown routines.
			    Phing::shutdown();
			} catch (\ConfigurationException $x) {
				$error = $x->getMessage();
			} catch (\Exception $x) {
				$error = $x->getMessage();
			}

		}

		// Generate time report
		$timeElapsed = 'Total synchronize time: '.floor(microtime(TRUE) - $timeStart).' s'."\n";

		return $success ? $this->render('OK' . "\n" . $timeElapsed) : $this->render('FAIL' . "\n" . $error . "\n" . $timeElapsed);
	}

	/**
	 * Constructor.
	 *
	 * @param Request $request A Request instance
	 */
	public function __construct(Request $request) {
		$this->request = $request;

		// Flag the position
		$this->request->attributes->set('class', get_class($this));

		// Initialize the session
		if ( ! $this->request->hasPreviousSession()) {
			$this->session = new Session();
			$this->session->start();
			$this->request->setSession($this->session);
		} else {
			$this->session = $this->request->getSession();
		}

		// Initialize Acl and Data instances
		$this->acl = new Acl($this->request, new Reader());
		$this->data = new Parameter();

		// Before action hook
		if (is_callable(array($this, self::BEFORE_ACTION))) {
			$this->beforeAction();
		}
	}

	/**
	 * beforeAction hook (Global)
	 */
	public function beforeAction() {
		// Assign acl object
		$this->data->set('acl', $this->acl);

		if ($this->acl->isLogin()) {
			// Assign user data
			$this->data->set('user', ModelBase::factory('Auth')->getUser($this->session->get('userId')));

			// Periksa status konfirmasi
			if ( ! ModelBase::factory('Auth')->isConfirmed($this->session->get('userId'))) {
				$this->setAlert('confirmation', NULL, 6000);
			}
		}

		// Assign POST data
		if ($_POST || $this->session->get('postData')) {
			$postData = array();

			if ($_POST) $postData = array_merge($postData, $_POST);

			if ($this->session->get('postData')) {
				$postData = array_merge($postData, $this->session->get('postData'));
				// Unset
				$this->session->set('postData', NULL);
			}

			$this->data->set('postData', $postData);
		}

		// Assign GET data
		if ($_GET) {
			$this->data->set('getData', $_GET);
		}

		// Detect path information
		$currentUrl = $this->request->server->get('PATH_INFO');

		// Try workaround on CGI environment
		if (empty($currentUrl)) $currentUrl = $this->request->server->get('SCRIPT_URL');

		if ( ! empty($currentUrl)) {
			// Initialize
			$queryUrl = array();

			// Exceptions for this keys
			$postToGetKeys = array('query');
			$flashedKeys = array('page', $currentUrl);

			// add any detected keys that match exception elements
			foreach ($postToGetKeys as $postToGetKey) {
				if (isset($_POST[$postToGetKey])) $queryUrl[$postToGetKey] = $_POST[$postToGetKey];
			}

			// remove any flashed keys
			foreach($flashedKeys as $flashedKey) {
				if (isset($queryUrl[$flashedKey])) unset($queryUrl[$flashedKey]);
			}

			$currentQueryUrl = $currentUrl.'?';

			if ( ! empty($queryUrl)) {
				$currentQueryUrl .= http_build_query($queryUrl).'&';
			}

			// Set common URL variable
			$this->data->set('baseUrl','http://'.$this->request->server->get('HTTP_HOST'));
			$this->data->set('currentUrl', $currentUrl);
			$this->data->set('currentQueryUrl', $currentQueryUrl);
		}

		// Set for MENU 
		// @codeCoverageIgnoreStart
		switch($this->request->get('class')) {
			case 'app\Controller\ControllerHome':
				$this->data->set('menuHomeActive','active');
				break;

			case 'app\Controller\ControllerRepo':
				$this->data->set('menuProjectActive','active');
				break;
		}
		// @codeCoverageIgnoreEnd

		// Check for flash message
		if ($this->session->get('alert')) {
			// Get and unset the alert
			$alert = new Parameter(unserialize($this->session->get('alert')));

			$this->data->set('alertType', $alert->get('alertType', NULL));
			$this->data->set('alertMessage', $alert->get('alertMessage', NULL));
			$this->data->set('alertTimeout', $alert->get('alertTimeout', NULL));

			$this->session->set('alert',NULL);
		}

		// Check for tweet
		$this->checkTweet();
	}

	/**
	 * Exception handler
	 *
	 * @return Response
	 */
	public function handleException() {
		// @codeCoverageIgnoreStart
		// Try looking for valid user or repository
		$pathInfo = $this->request->getPathInfo();
		$candidate = substr($pathInfo, 1);
		$isImage = strpos($candidate, '.png') !== false;

		if ($isImage) $candidate = str_replace('.png', '', $candidate);

		$validUser = ModelBase::factory('User')->getQuery()->findOneByName($candidate);

		if ($validUser) {
			$request = clone $this->request;
			$request->query->set('exception', NULL);
			$request->attributes->set('action', 'profile');
			$request->attributes->set('id', $validUser->getUid());

			$proxyHandler = new ControllerUser($request);

		    return $proxyHandler->actionProfile();
		}

		$validRepo = ModelBase::factory('Repo')->getQuery()->findOneByFullName($candidate);

		if ($validRepo) {
			$request = clone $this->request;
			$request->query->set('exception', NULL);
			$request->attributes->set('action', ($isImage) ? 'status' : 'detail');
			$request->attributes->set('id', $validRepo->getRid());

			$proxyHandler = new ControllerRepo($request);

		    return ($isImage) ? $proxyHandler->actionStatus() : $proxyHandler->actionDetail();
		}
		// @codeCoverageIgnoreEnd

		$e = $this->request->get('exception');

		if (empty($e) || ! $e instanceof FlattenException) $e = new FlattenException();

		// @codeCoverageIgnoreStart
		if ($this->request->server->get('HTTP_HOST') == self::PRODUCTION) {
			$e->setMessage('<strong>Sorry for this, but it seems that you\'re hit us with invalid request.</strong><br/>No? This is some sort of unknown bug?<br/>Then, fork <a href="https://github.com/toopay/depending">this open-source project</a> and help us to fix this.');
		}
		// @codeCoverageIgnoreEnd

		$this->layout = 'modules/error/general.tpl';
		$data = ModelBase::factory('Template')->getDefaultData();

		// Additional setter
		$this->data->set('title', 'Y U not working!');
		$this->data->set('content', $e);

		return $this->render($data);
	}

	/**
	 * API untuk Session
	 *
	 * @return Session
	 */
	public function getSession() {
		return $this->session;
	}

	/**
	 * API untuk ACL
	 *
	 * @return Acl
	 */
	public function getAcl() {
		return $this->acl;
	}

	/**
	 * API untuk Data
	 *
	 * @return Parameter
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Pencarian token dalam GET
	 *
	 * @return string
	 */
	public function getToken() {
		$token = $this->data->get('getData[token]','',true);
		
		if (empty($token)) {
			// Try to see the session if login
			if ($this->session->get('login') && $this->session->get('githubToken')) {
				$token = $this->session->get('githubToken');
			} else {
				throw new \InvalidArgumentException('Token not found!');
			}
		}

		return $token;
	}

	/**
	 * Login setter
	 *
	 * @param $id User ID
	 */
	public function setLogin($id) {
		// Catat waktu login
		$currentUser = ModelBase::ormFactory('UsersQuery')->findPK($id);
		$currentUser->setLogin(time());
		$currentUser->save();

		$currentUser = ModelBase::factory('User')->getUser($id);

		// Set login status
		$this->session->set('login', true);
		$this->session->set('userId', $currentUser->get('Uid'));
		$this->session->set('githubToken', $currentUser->get('GithubToken'));
		$this->session->set('role', 'member');
	}

	/**
	 * Set alert message to either current data or session if necessary
	 *
	 * @param string $type Alert type
	 * @param string $message Alert message
	 * @param int $timeout Alert timeout
	 * @param bool $next 
	 *
	 * @return void
	 */
	public function setAlert($type = NULL, $message = NULL, $timeout = 0, $next = false) {
		// Prepare message alert
		switch ($type) {
			case 'confirmation':
				$type = 'info';
				$alertMessage = ModelBase::factory('Template')->render('blocks/alert/confirmation.tpl');
				break;
			
			case 'info':
				$alertMessage = ModelBase::factory('Template')->render('blocks/alert/success.tpl', compact('message'));
				break;

			default:
				$alertMessage = ModelBase::factory('Template')->render('blocks/alert/general.tpl', compact('message'));
				break;
		}

		// Build alert element
		$alert = array(
			'alertType' => $type,
			'alertMessage' => $alertMessage,
			'alertTimeout' => $timeout,
		);

		if ($next) {
			// Save to session
			$this->session->set('alert', serialize($alert));
		} else {
			// Save to data
			foreach ($alert as $key => $value) {
				$this->data->set($key,$value);
			}
		}
	}

	/**
	 * Main worker endpoint
	 *
	 * @param bool TRUE will returning the Response instance while FALSE will returning work status
	 * @param int Could specify the log to be build
	 * @param bool Whether to output directly or use the buffer to blocks any output
	 * @return mixed Could be Response intance or Boolean
	 * @codeCoverageIgnore
	 */
	public function doWork($returnResponse = false, $logId = 0, $silent = false) {

		if (empty($logId)) {
			// Get the latest un-processed log and its repository
			$latestLog = ModelBase::factory('Log')->getQuery()->orderByCreated()->findOneByStatus(0);
		} else {
			// Get the requested log
			$latestLog = ModelBase::factory('Log')->getQuery()->filterByStatus(0)->findPK($logId);
		}

		// Only work on something!
		if ( ! empty($latestLog)) {
			try {
				$success = ModelBase::factory('Worker')->run($latestLog, $silent);

				$statusCode = 200;
				$statusText = 'OK. Log ID:'.$latestLog->getId();
			} catch (\RuntimeException $e) {
				$statusCode = 500;
				$statusText = 'FAIL. Reason :'.$e->getMessage();
			}

			return ($returnResponse) ? $this->render($statusText, $statusCode) : ($statusCode == 200);
		}

		return ($returnResponse) ? $this->notModified() : true;
	}

	/**
	 * Not modified response
	 *
	 * @return Response
	 */
	public function notModified() {
		return Response::create()->setNotModified();
	}

	/**
	 * Redirect response.
	 *
	 * @param string $url URL Path
	 * @param int $status HTTP status code
	 * @param Array $headers HTTP Headers
	 *
	 * @return RedirectResponse A Response instance
	 */
	public function redirect($url = '', $status = 302, $headers = array()) {
		return new RedirectResponse($url, $status, $headers);
	}

	/**
	 * Render JSON response.
	 *
	 * @param array $data A Response data
	 * @param int $status HTTP status code
	 *
	 * @return Response A Response instance
	 */
	public function renderJson($data = array(), $status = 200) {
		$jsonData = json_encode($data);
		$jsonHeader = array('Content-Type' => 'application/json');

		return $this->render($jsonData, $status, $jsonHeader);
	}

	/**
	 * Render response.
	 *
	 * @param mixed $content A Response content
	 * @param int $status HTTP status code
	 * @param Array $headers HTTP Headers
	 *
	 * @return Response A Response instance
	 */
	public function render($data = NULL, $status = 200, $headers = array()) {
		// Persiapkan data yang akan di-render
		if (is_array($data) && array_key_exists('title', $data) && array_key_exists('content', $data)) {
			// Data yang dikirim memiliki parameter minimal yakni 'title' dan 'content'
			// sertakan semua global data object yang mungkin diperlukan
			$data = array_merge($data, $this->data->all());

			// Panggil render method
			$content = ModelBase::factory('Template')->render($this->layout, $data);
		} else {
			// Data yang dikirim tidak memenuhi kriteria
			// Outputkan raw data
			$content = $data;
		}

		return new Response($content, $status, $headers);
	}

	/**
	 * Check last tweet
	 *
	 * @codeCoverageIgnore
	 */
	private function checkTweet() {
		$cm = new CacheManager();

		if ( ! $cm->has('tweet')) {
			$url = "https://api.twitter.com/1/statuses/user_timeline/depending_in.xml?count=1";
			$xml = simplexml_load_file($url);

			if ($xml) {
				foreach($xml->status as $status){
					$tweet = (string) $status->text;
				}
				
				$cm->set('tweet',$tweet,600);
			} else {
				$cm->set('tweet', '...',5);
			}
		}

		$this->data->set('lastTweet', $cm->get('tweet'));
	}
}
