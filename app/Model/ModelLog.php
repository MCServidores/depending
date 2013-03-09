<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

namespace app\Model;

use app\Model\Orm\Logs;
use app\Parameter;

/**
 * ModelLog
 *
 * @author depending.in Dev
 */
class ModelLog extends ModelBase 
{
	protected $entity = 'Logs';
	protected $query = 'LogsQuery';

	/**
	 * Fetch log lists
	 *
	 * @param int Limit result 
	 * @param int Pagination
	 * @param array Filter
	 *
	 * @return array Array of log object wrapped in ParameterBag
	 */
	public function getAllLog($limit = 0, $page = 1, $filter = array()) {
		// Inisialisasi
		$logs = array();

		// Create user query
		$query = $this->getQuery();

		// Limit
		if ( ! empty($limit)) $query->limit($limit);

		// Offset
		if ( ! empty($page)) $query->offset(($page-1)*$limit);

		// Filter
		if ( ! empty($filter)) {
			foreach ($filter as $where) {
				if ( ! $where instanceof Parameter) {
					$where = new Parameter($where);
				}

				$query = ModelBase::filterQuery($query, $where);
			}
		}

		// Order
		$query->orderByCreated();

		if (($allLogs = $query->find()) && ! empty($allLogs)) {

			foreach ($allLogs as $singleLog) {
				// Convert to plain array and adding any necessary data
				$logData = $this->extractLog($singleLog->toArray());
				$logs[] = $logData->all();
			}
		}

		return new Parameter($logs);
	}

	/**
	 * Retrieve log data
	 *
	 * @param int $id Log ID
	 * @return Parameter
	 */
	public function getLog($id = NULL) {
		// Silly
		if (empty($id)) return false;

		// Get repo
		$log = $this->getQuery()->findPK($id);

		if ($log) {
			// Get other misc data
			$logData = $this->extractLog($log->toArray());
			$log = $logData;
		}
		
		return $log;
	}

	/**
	 * Create log
	 *
	 * @param string $before
	 * @param string $after
	 * @param string $ref
	 * @param string $commitUrl
	 * @param string $commitMessage
	 * @param string $commitAuthor
	 * @return Logs 
	 */
	public function createLog($before, $after, $ref, $commitUrl, $commitMessage, $commitAuthor) {
		// Create
		$log = $this->getEntity();
		$log->setBefore($before);
		$log->setAfter($after);
		$log->setRef($ref);
		$log->setCommitUrl($commitUrl);
		$log->setCommitMessage($commitMessage);
		$log->setCommitAuthor($commitAuthor);
		$log->setCreated(time());

		$log->save();
		$currentLog = $this->getQuery()->findOneByAfter($log->getAfter());
		
		return $currentLog;
	}

	/**
	 * Update data log
	 *
	 * @param int $id Log ID
	 * @param array $data regular data
	 *
	 * @return mixed
	 */
	public function updateLog($id = NULL, $data = array()) {
		// Silly
		if (empty($id)) return false;

		// Get user
		$log = $this->getQuery()->findPK($id);

		if ($log) {
			foreach ($data as $key => $value) {
				$setMethod = 'set'.ucfirst($key);
				if (is_callable(array($log,$setMethod))) {
					if ($key == 'reposs') {
						$log->setReposs($value);
					} else {
						$log = call_user_func_array(array($log,$setMethod), array($value));
					}
				}
			}
		} else {
			return false;
		}

		$log->save();

		return $this->getLog($log->getId());
	}

	/**
	 * Update repos's and its logs data
	 *
	 * @param int Repo RID
	 * @param Parameter An array containing commits payload
	 */
	public function updateRepoLogs($rid, Parameter $logPayload) {
		$repo = ModelBase::factory('Repo')->getQuery()->findPK($rid);

		if ( ! empty($repo) && ! empty($logPayload)) {
			// Get the active log
			$logHash = $logPayload->get('after');
			$activeLog = $this->getQuery()->findOneByAfter($logHash);

			// Extract the payload
			$before = $logPayload->get('before');
			$after = $logPayload->get('after');
			$refFull = $logPayload->get('ref','master');
			$ref = str_replace('refs/heads/', '', $refFull);
			$commits = $logPayload->get('commits');
			$lastCommit = end($commits);
			$commitUrl = $lastCommit->url;
			$commitMessage = $lastCommit->message;
			$commitAuthor = $lastCommit->author->name;

			if ( ! $activeLog) {
				// Create
				$activeLog = $this->createLog($before, $after, $ref, $commitUrl, $commitMessage, $commitAuthor);
				$logId = $activeLog->getId();
			} else {
				$logId = $activeLog->getId();
			}

			// Update the log data
			$logData = array(
				'reposs' => $this->wrapCollection($repo),
			);

			$this->updateLog($logId, $logData);
		}
	}

	/**
	 * Update custom data log
	 *
	 * @param int $id Log ID
	 * @param array $data Custom data
	 *
	 * @return mixed
	 */
	public function updateLogData($id = NULL, $data = array()) {
		// Silly
		if (empty($id)) return false;

		// Get user
		$log = $this->getQuery()->findPK($id);

		if ($log) {
			// Get custom data
			$logData = new Parameter($log->toArray());
			$customData = $logData->get('Data');

			// @codeCoverageIgnoreStart
			if (empty($customData)) {
				// Straight forward
				$log->setData(serialize($data));
			} else {
				$logDataSerialized = @fread($customData,10000);
				try {
					$currentLogData = unserialize($logDataSerialized);
					if ( ! $currentLogData) $currentLogData = array();
					$currentLogData = array_merge($currentLogData, $data);
				} catch (\Exception $e) {
					$currentLogData = $data;
				}

				// Update custom data
				$log->setData(serialize($currentLogData));
			}
			// @codeCoverageIgnoreEnd
			
			$log->save();

			return $this->getLog($log->getId());
		} else {
			return false;
		}
	}

	/**
	 * Build a report preview about a log
	 *
	 * @param $id The log id
	 * @return string The generated html content
	 */
	public function buildReport($id) {
		$log = $this->getQuery()->findPK($id);

		if ( ! empty($log)) {
			// Determine the status
			$logInfo = new Parameter(ModelBase::factory('Template')->setLogStatusText($log->getStatus(), true));
			$status = $logInfo->get('status','inverse');
			$statusText = $logInfo->get('statusText','UNKNOWN');

			
			$repo = current($log->getReposs());
			$user = current($repo->getUserss());

			// Template configuration
			$content = ModelBase::factory('Template')->getBuildData($id, true);

			$title = 'Depedencies Report['.$repo->getFullName().']';
			$data = array(
				'title' => 'Build Report',
				'content' => $content,
				'link' => '/'.$repo->getFullName(),
				'linkClass' => 'btn-'.$status,
				'linkText' => $statusText,
			);

			$emailParameter = new Parameter(array(
				'toName' => $user->getName(),
				'toEmail' => $user->getMail(),
			));

			ModelBase::factory('Mailer', $emailParameter)->sendReport($title, $data);

			return true;
		}

		return false;
	}

	/**
	 * Extract log
	 *
	 * @param array
	 * @return Parameter
	 */
	protected function extractLog($logArrayData = array()) {
		$logData = new Parameter($logArrayData);
		$logCustomData = $logData->get('Data');

		// @codeCoverageIgnoreStart
		if ( ! empty($logCustomData)) {
			// Get data from opening stream
			$streamName = (string) $logCustomData;

			if (ModelBase::$stream->has($streamName)) {
				$logDataSerialized = ModelBase::$stream->get($streamName);
			} else {
				$logDataSerialized = @stream_get_contents($logCustomData);
				ModelBase::$stream->set($streamName, $logDataSerialized);
			}

			// Now write back
			$additionalData = unserialize($logDataSerialized);
			$logData->set('AdditionalData', $additionalData);
		}
		// @codeCoverageIgnoreEnd

		return $logData;
	}
}