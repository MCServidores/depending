<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

namespace app\Model;

use app\Parameter;
use app\CacheManager;
use app\Model\Orm\Logs;
use app\Model\Orm\Repos;

/**
 * ModelWorker
 *
 * @author depending.in Dev
 */
class ModelWorker extends ModelBase 
{
	const PROVIDER_VENDOR = 'https://packagist.org/packages/';
	const SECURITY_VENDOR = 'https://security.sensiolabs.org/check_lock';
	const PREFIX_VENDOR = 'response_packagist_';
	const COMPOSER = 'composer.json';
	const LOCK = 'composer.lock';
	private $silent = false;
	private $bufferContent;

	/**
	 * Set up mode
	 */
	public function setUp() {
		$this->bufferContent = new Parameter();
	}

	/**
	 * Main worker API to consume a task
	 *
	 * @param Logs The log task to consume
	 * @param bool The flag to block any output to be sent/rendered
	 * @return bool TRUE only if success, otherwise Exception will thrown
	 * @throws RuntimeException on system failure
	 */
	public function run(Logs $log, $silent = false) {
		// Set the flag
		$this->silent = $silent;

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
		if ( ! $this->checkOutTo($log->getRef(), $log->getAfter(), $repo)) {
			throw new \RuntimeException('Cannot checkout to : '.$repo->getFullName().'/'.$log->getRef().'/'.$log->getAfter());
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
		$composer = new Parameter($this->getComposer($repo));

		if (empty($composer)) {
			throw new \RuntimeException('Error Processing Composer. '.$this->getLastJsonError());
		}

		$depsStatus = $this->determineStatus($composer);

		if (empty($depsStatus)) {
			throw new \RuntimeException('Error occured when comparing dependencies versions.');
		}

		// inspect the lock
		$advice = $this->getAdvice($repo);

		if ($composer->get('name') || $composer->get('type')) {
			// Set the repo flag as package
			$result->set('repoIsPackage', 1);
		} else {
			// Set the repo flag as non package
			$result->set('repoIsPackage', 0);
		}

		// Set the task flag
		$result->set('logStatus', $depsStatus->get('status'));
		$result->set('logData', array('percentage' => $depsStatus->get('percentage'),
		                              'depsDiff' => $depsStatus->get('depsDiff')->all(),
		                              'advice' => $advice));
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
					$outOfdatePackages++;
				}
			}

			if ($outOfdatePackages == 0) {
                $status = 3;
                $percentage = 100;
            } elseif (count($depsDiff) == $outOfdatePackages) {
                $status = 1;
                $percentage = 0;
            } else {
                $percentage = (int) floor((($totalPackages-$outOfdatePackages)/$totalPackages)*100);

                if ($percentage >= 50) {
                    $status = 2;
                }
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

					// Skip *dev*
					if (stripos($currentVersion, 'dev') !== false) continue;

					$packageInformation = $this->getPackageInfo($vendor);

					if ( ! empty($packageInformation) && is_array($packageInformation)) {
						$package = new Parameter($packageInformation);

						foreach ($package->get('package[versions]',array(),true) as $versionKey => $versionData) {
							if (stripos($versionKey, 'dev') !== false) continue;
							$versions[] = $versionKey;
						}
					}
				}
				

				if ( ! empty($versions)) {
					// Get the latest version and its diff
					$versionDiff = 0;
					$vendor = $dep['vendor'];
					$rawVersion = $dep['version'];
					$version = $this->normalizeVendorVersion($rawVersion);
					list($rawLatestVersion,$latestVersion) = $this->getHighestVersion($versions);

					$versionDiff = version_compare($version, $latestVersion);

					$depsDiff[] = compact('vendor','rawVersion','rawLatestVersion','version','latestVersion','versionDiff');
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
			$requiredPackagesArray = $requiredPackages;

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
	 * Retrieve package information from Packagist
	 *
	 * @param string $vendor
	 * @return array $information
	 */
	public function getPackageInfo($vendor = '') {
		// Get responses and cache it!
		$cacheManager = new CacheManager();
		if ($cacheManager->has(self::PREFIX_VENDOR.$vendor)) {
			$response = $cacheManager->get(self::PREFIX_VENDOR.$vendor);
		} else {
			$response = $this->getData(self::PROVIDER_VENDOR.$vendor.'.json', array());

			// Only cache successful request
			if ($response->get('result') && in_array($response->get('head[http_code]',500,true),array(200,304))) {
				$cacheManager->set(self::PREFIX_VENDOR.$vendor,$response,3600);
			}
		}

		return (($json = $response->get('body')) && ! empty($json)) ? json_decode($json,true) : array();
	}

	/**
	 * Retrieve security advisories
	 *
	 * @param Repos $repo
	 * @return array 
	 */
	public function getAdvice(Repos $repo) {
		$advice = array();

		if ($this->containsLock($repo)) {
			$lock = '@'.$this->getClonePath($repo).DIRECTORY_SEPARATOR.self::LOCK;
			$check = $this->postData(self::SECURITY_VENDOR,array(),array(
				array(CURLOPT_HTTPHEADER => array('Accept: application/json')),
				array(CURLOPT_POSTFIELDS => array('lock' => $lock)),
				array(CURLOPT_RETURNTRANSFER => 1),
			));

			if (($result = $check->get('result')) && !empty($result)) {
				$advice = json_decode($result,true);
			}
		}

		return $advice;
	}

	/**
	 * Check if given repo has a composer.lock
	 *
	 * @param Repos the repo to be checked
	 * @return bool 
	 */
	public function containsLock(Repos $repo) {
		// Check if repo exists
		if ( ! $this->isCloneExists($repo)) return false;

		return is_file($this->getClonePath($repo).DIRECTORY_SEPARATOR.self::LOCK);
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
		return json_decode($composerJson, true);
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
	public function checkOutTo($branch = 'master', $revision, Repos $repo) {
		// Direct checkout to revision
		$checkoutStatus = $this->execute('cd '.$this->getClonePath($repo).';git checkout '.$revision);

		if ( ! $checkoutStatus) {
			// If last attemp failed, checkout to specific branch revision
			$checkoutStatus = $this->execute('cd '.$this->getClonePath($repo).';git checkout '.$branch.' '.$revision);
		}

		return $checkoutStatus;
	}

	/**
	 * Sys executor
	 *
	 * @param string Command to be execute
	 * @return bool UNIX exit status evaluation
	 */
	public function execute($command) {
		$success = 1;
		if ($this->silent) {
			if (!ob_get_level()) ob_start();
		}

		passthru($command, $success);

		if ($this->silent) {
			$this->bufferContent->set($command, ob_get_clean());
		}

		return $success == 0;
	}

	/**
	 * Get buffer content
	 */
	public function getBufferContent() {
		return $this->bufferContent;
	}

	/**
	 * Get highest version from an array of versions
	 *
	 * @param array $versions
	 * @return array raw latestVersion and normalized version
	 */
	public function getHighestVersion($versions = array()) {
		$latestVersion = 'v0.0.0';
		$rawLatestVersion = '0.0.0';

		foreach ($versions as $version) {
			$nowVersion = $this->normalizeVendorVersion($version);

			if (version_compare($latestVersion, $nowVersion) >= 0) {
				continue;
			}

			$latestVersion = $nowVersion;
			$rawLatestVersion = $version;
		}

		return array($rawLatestVersion, $latestVersion);
	}

	/**
	 * Normalize vendor version
	 *
	 * @param string $rawVersion
	 * @param string $versionCandidate
	 */
	public function normalizeVendorVersion($rawVersion) {
		// Remove the package link (alpha, RC, beta, etc)
		list($versionCandidate) = explode('-', $rawVersion);

		// Strip the 'v' prefix if exists
		$versionCandidate = str_replace('v','',$versionCandidate);

		// Transform the ranges
		if (strpos($versionCandidate, ',') !== false) {
			$versionsRange = explode(',', $versionCandidate);
			// Reset the candidate
			$versionCandidate = '0.0.0';

			// Get the higher range
			foreach ($versionsRange as $versionRange) {
				$nowVersion = $this->determineVersionValue($versionRange);

				if (version_compare($versionCandidate, $nowVersion) < 0) {
					// Range is higher
					$versionCandidate = $nowVersion;
				}
			}
		} else {
			// Done
			$versionCandidate = $this->determineVersionValue($versionCandidate);
		}

		return $versionCandidate;
	}

	/**
	 * Determine version value and handle wildcards and comparison operator
	 *
	 * @param string $rawVersion
	 * @return string $version
	 */
	public function determineVersionValue($rawVersion) {
		// Transform any wildcard into possible highest value
		$version = str_replace('*', '999', $rawVersion);

		// Handle operator
		if (preg_match('/^([\~\>\<\=\!]+)([0-9\.]+)$/', $version, $m) && count($m) == 3) {
			$operator = $m[1];
			$version = $m[2]; 

			// Hope for the best (finger crossed...)
			$versionAnnotations = explode('.', $version);

			if (count($versionAnnotations) == 3) {
				// Everything ok
				list($major,$minor,$patch) = $versionAnnotations;
			} else {
				switch (count($versionAnnotations)) {
					case 2:
						list($major,$minor) = $versionAnnotations;
						$patch = 999;
						break;
					
					default:
						$major = $versionAnnotations;
						$minor = 999;
						$patch = 999;
						break;
				}
			}

			// Determine the closest possible value
			if (strpos($operator, '>')!==false || strpos($operator, '!')!==false || strpos($operator, '~')!== false) {
				// Increase the patch and minor version to the max
				$version = $major.'.999.999';
			} elseif (strpos($operator, '<') !== false) {
				// Decrease the patch and minor version to the min
				if ($patch == 0) {
					$version = (((int)$major)-1).'.999.999';
				} else {
					$version = $major.'.0.0';
				}
			}
		}

		return $version;
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

		// Send the report
		ModelBase::factory('Log')->buildReport($log->getId());
	}
}