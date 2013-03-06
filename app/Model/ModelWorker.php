<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

namespace app\Model;

use app\Parameter;
use app\Model\Orm\Logs;
use app\Model\Orm\Repos;

/**
 * ModelWorker
 *
 * @author depending.in Dev
 */
class ModelWorker extends ModelBase 
{
	const COMPOSER = 'composer.json';

	/**
	 * Main worker API to consume a task
	 *
	 * @param Logs The log task to consume
	 * @return bool TRUE only if success, otherwise Exception will thrown
	 * @throws RuntimeException on system failure
	 */
	public function run(Logs $log) {
		// Valid log task?
		if ($log->getStatus() > 0) return false;

		// Initialize the result skeleton
		$result = new Parameter(array(
			'logExecuted' => time(),	// Executed time
			'logStatus' => 0, 			// Whether the log satisfy the dependencies metrix
			'logData' => array(),		// The build results
			'repoIsPackage' => 0,		// Wheter a repo is a package or not
		));

		// Get related repository information
		$repo = current($log->getReposs());

		// First, check if the repository exists
		if ( ! $this->isCloneExists($repo)) {
			// Clone the repo
			if ( ! $this->doClone($repo)) {
				throw new \RuntimeException('Cannot clone the repository : '.$repo->getFullName());
			}
		}

		// Fetch the latest HEAD
		if ( ! $this->fetchOrigin($repo)) {
			throw new \RuntimeException('Cannot fetch the latest HEAD of repository : '.$repo->getFullName());
		}

		// Checkout the desired revision
		if ( ! $this->checkOutTo($log->getAfter(), $repo)) {
			throw new \RuntimeException('Cannot checkout to : '.$repo->getFullName().'/'.$log->getAfter());
		}
		
		// Now, inspect the composer if exists
		if ( ! $this->containsComposer($repo)) {
			// We're done!
			$result->set('logStatus', 1);
			$result->set('logExecuted', time());

			$this->terminateTask($log,$repo,$result);

			return true;
		}

		// inspect the composer
		$composer = new Parameter((array) $this->getComposer($repo));

		if (empty($composer)) {
			throw new \RuntimeException('Error Processing Composer. '.$this->getLastJsonError());
		}

		$depsStatus = $this->determineStatus($composer);

		if (empty($depsStatus)) {
			throw new \RuntimeException('Error occured when comparing dependencies versions.');
		}

		if ($composer->get('name') || $composer->get('type')) {
			// Set the repo flag as package
			$result->set('repoIsPackage', 1);
		} else {
			// Set the repo flag as non package
			$result->set('repoIsPackage', 0);
		}

		// Set the task flag
		$result->set('logStatus', $depsStatus->get('status'));
		$result->set('logData', array('depsDiff' => $depsStatus->get('depsDiff')->all()));
		$result->set('logExecuted', time());

		// We're done
		$this->terminateTask($log,$repo,$result);

		return true;
	}

	/**
	 * Determine the dependencies status
	 *
	 * @param mixed $composer
	 * @return mixed Dependencies status (0,1,2,3)
	 */
	public function determineStatus($composer) {
		// Empty composer get unknown status
		if (empty($composer)) return false;

		$dependencies = $this->getPackageDeps($composer);

		// Empty dependencies get unknown status
		if (empty($dependencies)) return false;

		// Check the dependencies diffs
		$status = 1;
		$depsDiff = $this->getDepsDiff($dependencies);
		
		if ( ! empty($depsDiff)) {
			// Get the percentage and its final status
			$outOfdatePackages = 0;
			$totalPackages = count($depsDiff);

			foreach ($depsDiff as $depDiff) {
				if ($depDiff['versionDiff'] < 0) {
					$outOfdatePackages;
				}
			}

			$percentage = (int) floor(($outOfdatePackages/$totalPackages)*100);

			if ($percentage == 100) {
				$status = 3;
			} elseif ($percentage > 75) {
				$status = 2;
			}
		}

		return new Parameter(compact('status','percentage','depsDiff'));
	}

	/**
	 * Retrieve dependencies diff
	 *
	 * @param array $dependencies
	 * @return Parameter $depsDiff
	 */
	public function getDepsDiff($dependencies) {
		$depsDiff = array();

		if ( ! empty($dependencies)) {
			foreach ($dependencies as $dep) {
				$versions = array();

				if (array_key_exists('version', $dep) && array_key_exists('vendor', $dep)) {
					// Get current vendor and its version, then fetch its newest state information
					$vendor = $dep['vendor'];
					$currentVersion = $dep['version'];

					// Skip PHP, its not vendor!!!
					if ($vendor == 'php') continue;

					$response = ModelBase::factory('Github',new Parameter())->getData('https://packagist.org/feeds/package.'.$vendor.'.rss', array());
					
					if ($response->get('result') && in_array($response->get('head[http_code]',500,true),array(200,304)) && ($rss = $response->get('body')) && ! empty($rss)) {
						$package = new \SimpleXMLElement($rss);

						foreach ($package->channel->item as $item) {
							$title = $item->title;
							list($a,$b) = explode('(', $title);
							list($versionCandidate,$c) = explode(')', $b);

							if ( ! empty($versionCandidate)) {
								$versions[] = $versionCandidate;
							}
						}
					}
				}
				

				if ( ! empty($versions)) {
					// Itterate and get the latest version and its diff
					$diff = 0;
					$rawVersion = $rawLatestVersion = $dep['version'];
					$currentVersion = $this->normalizeVendorVersion($dep['version']);
					$latestVersion = $currentVersion;

					foreach ($versions as $version) {
						$nowVersion = $this->normalizeVendorVersion($version);
						if ($diff = version_compare($currentVersion, $nowVersion)) {
							if ($diff < 0) {
								$rawLatestVersion = $version;
								$latestVersion = $version;
								$diff = -1;
								break 1;
							}
						}
					}

					$depsDiff[] = array(
						'vendor' => $dep['vendor'],
						'rawVersion' => $rawVersion,
						'rawLatestVersion' => $rawLatestVersion,
						'version' => 'v'.$currentVersion,
						'latestVersion' => 'v'.$latestVersion,
						'versionDiff' => $diff,
					);
				}
			}
		}
		
		return new Parameter($depsDiff);
	}

	/**
	 * Retrieve package deps
	 *
	 * @param mixed $composer
	 * @return array $dependencies
	 */
	public function getPackageDeps($composer) {
		$dependencies = array();

		if ( ! empty($composer) && $composer instanceof Parameter && ($requiredPackages = $composer->get('require')) && ! empty($requiredPackages)) {
			// We get a valid composer
			$requiredPackagesArray = (array) $requiredPackages;

			// Extract the package and its version
			$packagesArrays = array_chunk(array_keys($requiredPackagesArray),1);
			$packagesVersionArrays = array_chunk(array_values($requiredPackagesArray),1);

			for ($i=0, $totalPackages = count($packagesArrays); $i < $totalPackages; $i++) { 
				$packagesArrays[$i] = array_combine(array('vendor','version'), array_merge($packagesArrays[$i],$packagesVersionArrays[$i]));
			}

			$dependencies = $packagesArrays;
		}

		return $dependencies;
	}

	/**
	 * Check if given repo has a composer.json
	 *
	 * @param Repos the repo to be checked
	 * @return bool 
	 */
	public function containsComposer(Repos $repo) {
		// Check if repo exists
		if ( ! $this->isCloneExists($repo)) return false;

		return is_file($this->getClonePath($repo).DIRECTORY_SEPARATOR.self::COMPOSER);
	}

	/**
	 * Transform composer.json into array representation
	 *
	 * @param Repos the repo to be checked
	 * @return array The Composer values 
	 */
	public function getComposer(Repos $repo) {
		// Check if composer exists
		if ( ! $this->containsComposer($repo)) return array();

		// Create temporary data
		$composerJson = file_get_contents($this->getClonePath($repo).DIRECTORY_SEPARATOR.self::COMPOSER);

		// We're done
		return json_decode($composerJson);
	}

	/**
	 * Get the latest Json error
	 *
	 * @return string
	 */
	public function getLastJsonError() {
		// @codeCoverageIgnoreStart
		switch (json_last_error()) {
			case JSON_ERROR_NONE:
				$possibleCause = 'No errors';
				break;
			case JSON_ERROR_DEPTH:
				$possibleCause = 'Maximum stack depth exceeded';
				break;
			case JSON_ERROR_STATE_MISMATCH:
				$possibleCause = 'Underflow or the modes mismatch';
				break;
			case JSON_ERROR_CTRL_CHAR:
				$possibleCause = 'Unexpected control character found';
				break;
			case JSON_ERROR_SYNTAX:
				$possibleCause = 'Syntax error, malformed JSON';
				break;
			case JSON_ERROR_UTF8:
				$possibleCause = 'Malformed UTF-8 characters, possibly incorrectly encoded';
				break;
			default:
				$possibleCause = 'Unknown error';
				break;
		}
		// @codeCoverageIgnoreEnd

		return $possibleCause;
	}

	/**
	 * Check if given repo existance
	 *
	 * @param Repos the repo to be checked
	 * @return bool 
	 */
	public function isCloneExists(Repos $repo) {
		return is_dir($this->getClonePath($repo));
	}

	/**
	 * Transform repo name into cacheable path 
	 *
	 * @param Repos
	 * @return string The clone path
	 */
	public function getClonePath(Repos $repo) {
		return CACHE_PATH . '/repos/'.str_replace('/', '.', $repo->getFullName());
	}

	/**
	 * Clone a repository
	 *
	 * @param Repos the repo to be clone
	 * @return bool 
	 */
	public function doClone(Repos $repo) {
		$cloneUrl = $repo->getUrlGit();

		return $this->execute('git clone '.$cloneUrl.' '.$this->getClonePath($repo));
	}

	/**
	 * Fetch latest head
	 *
	 * @param Repos the repo to be fetched
	 * @return bool 
	 */
	public function fetchOrigin(Repos $repo) {
		return $this->execute('cd '.$this->getClonePath($repo).';git fetch origin');
	}

	/**
	 * Checkout to a revision
	 *
	 * @param string the revision hash
	 * @param Repos the repo to be checked out
	 * @return bool 
	 */
	public function checkOutTo($revision, Repos $repo) {
		return $this->execute('cd '.$this->getClonePath($repo).';git checkout '.$revision);
	}

	/**
	 * Sys executor
	 *
	 * @param string Command to be execute
	 * @return bool UNIX exit status evaluation
	 */
	public function execute($command) {
		$success = 1;
		passthru($command, $success);
		return $success == 0;
	}

	/**
	 * Normalize vendor version
	 *
	 * @param string $rawVersion
	 * @param string $versionCandidate
	 */
	public function normalizeVendorVersion($rawVersion) {
		list($versionCandidate) = explode('-', $rawVersion);

		return str_replace('v','',$versionCandidate);
	}

	/**
	 * End of a task runner
	 *
	 * @param Logs $log
	 * @param Repos $repo
	 * @param Parameter $runResult
	 * @return void
	 */
	protected function terminateTask(Logs $log, Repos $repo, Parameter $runResult) {
		// Update the repo
		$repo->setIsPackage($runResult->get('repoIsPackage'));
		$repo->save();

		// Update the log blobs
		ModelBase::factory('Log')->updateLogData($log->getId(),$runResult->get('logData'));

		// Terminate the log
		$log->setStatus($runResult->get('logStatus'));
		$log->setExecuted($runResult->get('logExecuted'));
		$log->save();
	}
}