<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

namespace app\Controller;

use app\Parameter;
use app\Model\ModelBase;
use app\Model\ModelTemplate;
use \ModelCriteria;

/**
 * ControllerHome
 *
 * @author depending.in Dev
 */
class ControllerHome extends ControllerBase
{
	/**
	 * Handler for GET/POST /home/index
	 */
	public function actionIndex() {
		// @codeCoverageIgnoreStart
		// Exception untuk PHPUnit, yang secara otomatis selalu melakukan GET request ke / di akhir eksekusi
		if ($this->request->server->get('PHP_SELF', 'undefined') == 'vendor/bin/phpunit') {
			return $this->render('');
		}
		// @codeCoverageIgnoreEnd

		// If user already login, find out his repositories
		$repos = new Parameter(array());
		if ($this->session->get('login')) {
			$user = ModelBase::factory('User')->getQuery()->findPK($this->data->get('user')->get('Uid'));
			$filter = array(
				array('column' => 'Users', 'value' => $user),
			);

			$repos = ModelBase::factory('Repo')->getAllRepo(50,1,$filter);
		} else {
			$actives = ModelBase::factory('Log')
						->getQuery()
						->orderBy('Executed',ModelCriteria::DESC)
						->useReposLogsQuery()
						->groupBy('Rid')
						->endUse()
						->limit(10)
						->offset(0)
						->find();

			$packages = ModelBase::factory('Repo')
						->getQuery()
						->filterByIsPackage(1)
						->orderBy('Created',ModelCriteria::DESC)
						->useReposLogsQuery()
						->groupBy('Rid')
						->endUse()
						->limit(10)
						->offset(0)
						->find();

			$projects = ModelBase::factory('Repo')
						->getQuery()
						->filterByIsPackage(0)
						->orderBy('Created',ModelCriteria::DESC)
						->useReposLogsQuery()
						->groupBy('Rid')
						->endUse()
						->limit(10)
						->offset(0)
						->find();

			$repos = compact('packages','projects', 'actives');
		}

		// Template configuration
		$this->layout = 'modules/home/index.tpl';
		$data = ModelBase::factory('Template')->getHomeData(compact('repos'));

		// Render
		return $this->render($data);
	}

	/**
	 * Handler for GET/POST /home/work
	 *
	 * This is main log consumer, that either run by cron or by regular request
	 */
	public function actionWork() {
		$userAgent = $this->request->server->get('HTTP_USER_AGENT');

		// @codeCoverageIgnoreStart
		if (strpos($userAgent, 'curl') !== FALSE) {
			return $this->doWork(TRUE);
		} else {
		// @codeCoverageIgnoreEnd
			// Redirect to regular page
			return $this->redirect('/home');
		}
	}

	/**
	 * Handler for GET/POST /home/accept
	 *
	 * This is main payload handler, that accept Json data from Github
	 * @codeCoverageIgnore
	 */
	public function actionAccept() {
		// Authentication, for registered service
		if ($this->request->getUser() && $this->request->getPassword()) {
			// For valid service only
			$username = $this->request->getUser();
			$password = $this->request->getPassword();

			if ($username == 'github') {
				// Skip the github service tests
				if (md5($username) == $password) return $this->render('OK', 200);

				// Validate and block invalid user
				if ( strlen($password) < 32 || ! ModelBase::factory('Auth')->isLikePassword($password)) {
					return $this->render('AUTH FAIL', 500);
				}
			}

			// Transform the payload into valid JSON object
			$rawPayload = $this->request->getContent();
			$payloadJson = urldecode($rawPayload);
			if (strpos($payloadJson, 'payload=') !== false) {
				$payload = str_replace('payload=', '', $payloadJson);
			} else {
				$payload = $payloadJson;
			}

			// Log the payload for further inspection if necessary
			/* file_put_contents(CACHE_PATH.'/payload_'.time().'.log', $payload); */

			// Wrap the payload
			$payloadObject = json_decode($payload);

			// Out of control
			if (empty($payloadObject)) {
				$possibleCause = ModelBase::factory('Worker')->getLastJsonError();

				throw new \InvalidArgumentException('Error Processing JSON Request. Possible cause : '.$possibleCause);
			}

			$payloadParam = new Parameter((array) $payloadObject);

			// Findout the payload owner
			$repo = $payloadParam->get('repository');
			$repoName = $repo->url;

			if (preg_match('%^((https?://)|(www\.))([a-z0-9-].?)+(:[0-9]+)?(/.*)?$%i', $repoName, $matches) && count($matches) > 3) {
				$protocol = $matches[1];
				$repoName = str_replace($protocol.'github.com/', '', $repoName);
			}

			$existsRepo = ModelBase::factory('Repo')->getQuery()->findOneByFullName($repoName);

			if ($existsRepo) {
				ModelBase::factory('Log')->updateRepoLogs($existsRepo->getRid(), $payloadParam);

				return $this->render('OK', 201);
			} else {
				return $this->render('Requested repository could not be found', 404);
			}
		} else {
			return $this->render('TOKEN NOT FOUND', 500);
		}
	}

	/**
	 * Handler for GET/POST /home/import
	 */
	public function actionImport() {
		// For AJAX call only
		if ( ! $this->request->isXmlHttpRequest()) throw new \RangeException('You seems trying to access a different side of this app. Please stop.');

		// @codeCoverageIgnoreStart

		// Get data param
		$type = $this->data->get('postData[data]','all',true);

		// Initialize result
		$success = false;
		$html = '';
		$repos = array();

		if ($this->session->get('login')) {
			// Check his repository
			try {
				$uid = $this->session->get('userId');
				$accessToken = $this->getToken();
				$repositories = ModelBase::factory('Github', new Parameter(array(
					'githubToken' => $accessToken,
					)))->getRepositories($type);

				// Update the repositories data
				if ($repositories instanceof Parameter) {
					ModelBase::factory('Repo')->updateUserRepositories($uid,$accessToken,$repositories);
				}

				if (!$repositories->isEmpty()) {
					$user = ModelBase::factory('User')->getQuery()->findPK($this->session->get('userId'));
					$repos = $user->getReposs();
				}

				$success = ! empty($repos);
				$html = ModelTemplate::render('blocks/list/repo.tpl',compact('repos'));
			} catch (\Exception $e) {
				$exception = true;
				$success = false;

				// Cast invalid token
				if ($e instanceof \InvalidArgumentException && $e->getMessage() == 'Token not found!') {
					$html = ModelTemplate::render('blocks/token.tpl');
				} else {
					$html = ModelBase::factory('Template')->setAlert($e->getMessage());
				}
			}
			
		}

		// Just for the sake of jQuery ajax delay
		sleep(1);

		return $this->renderJson(compact('success','html', 'exception'));
		// @codeCoverageIgnoreEnd
	}

	/**
	 * Handler for GET /send_batch
	 */
	public function actionBatch()
	{
		$userAgent = $this->request->server->get('HTTP_USER_AGENT');

		// @codeCoverageIgnoreStart
		if (strpos($userAgent, 'curl') !== FALSE) {
			// Get user's
			$users = ModelBase::factory('User')->getQuery()->find();

			// Fetch news
			if (($news = realpath(APPLICATION_PATH.'/../news.json')) && is_file($news)) {
				$data = json_decode(file_get_contents($news), true);
				$signature = filemtime($news);

				foreach ($users as $user) {
					$uid = $user->getUid();
					$userData = ModelBase::factory('User')->getUser($uid);
					$receiveNews = $userData->get('AdditionalData[news]',0,true);

					if ($receiveNews < $signature) {
						$emailParameter = new Parameter(array(
							'toName' => $user->getName(),
							'toEmail' => $user->getMail(),
						));

						$sent = ModelBase::factory('Mailer', $emailParameter)->sendReport('Depending.in News [Update]', $data);

						if ($sent) {
							$updated = ModelBase::factory('User')->updateUserData($uid, array('news' => $signature));

							if (!empty($updated)) {
								return $this->render('Sent to :'.$user->getMail(), 201);
								break;
							}
						}
					}
				}

				return $this->render('All user already get the news');
			}

			return $this->render('No newsfeed available.');
		} else {
		// @codeCoverageIgnoreEnd
			// Redirect to regular page
			return $this->redirect('/home');
		}
	}
}