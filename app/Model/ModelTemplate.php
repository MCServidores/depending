<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

namespace app\Model;

use app\Parameter;
use \Twig_SimpleFilter;
use \Twig_Loader_Filesystem;
use \Twig_Environment;

/**
 * ModelTemplate
 *
 * @author depending.in Dev
 */
class ModelTemplate extends ModelBase 
{
    const PADDING_VENDOR = 50;
    const PADDING_VERSION = 22;
    const PADDING_VERSION_LEFT = 10;
    const PADDING_VERSION_RIGHT = 11;
    protected $defaultData = array(
        'title' => 'Unknown Error',
        'content' => 'Something goes wrong. Please contact administrator.',
        'menu_top' => array(
            array('title' => 'Home', 'link' => '/'),
            array('title' => 'Masuk', 'link' => '/auth/login'),
            array('title' => 'Daftar', 'link' => '/auth/register'),
        ),
        'menu_bottom' => array(),
    );

    /**
     * Render data ke template via Twig
     *
     * @param string $template eg:layout.tpl
     * @param array $data 
     *
     * @return string HTML representation
     */
    public static function render($template, $data = array()) {
        $that = static::factory('Template');
        // Inisialisasi Twig. Load template yang berkaitan dan assign data.
        $loader = new Twig_Loader_Filesystem(APPLICATION_PATH . DIRECTORY_SEPARATOR . 'Templates');
        $templateEngine = new Twig_Environment($loader);

        // Filter declaration
        $filters = array(
            new Twig_SimpleFilter('limitHash', array($that, 'setLimitHash')),
            new Twig_SimpleFilter('limitTweet', array($that, 'setLimitTweet')),
            new Twig_SimpleFilter('translateToLogText', array($that, 'setLogText')),
            new Twig_SimpleFilter('translateToSuccessText', array($that, 'setSuccessText')),
            new Twig_SimpleFilter('toPackagist', array($that, 'setPackagistUrl')),
            new Twig_SimpleFilter('toStatus', array($that, 'setStatusMarkdown')),
            new Twig_SimpleFilter('toIcon', array($that, 'setProjectIcon')),
            new Twig_SimpleFilter('toStatusIcon', array($that, 'setLogStatusText')),
            new Twig_SimpleFilter('toVendorIcon', array($that,'setVendorIcon')),
            new Twig_SimpleFilter('toAlert', array($that,'setAlert')),
            new Twig_SimpleFilter('isRedStatus', array($that, 'isRepoOutOfDate')),
            new Twig_SimpleFilter('isYellowStatus', array($that, 'isRepoNeedUpdate')),
            new Twig_SimpleFilter('isGreenStatus', array($that,'isRepoUptodate')),
        );

        // Register filter
        foreach ($filters as $filter) $templateEngine->addFilter($filter);
        
        return $templateEngine->render($template, $data);
    }

    /**
     * Helper untuk parsing text 
     *
     * @param string $text
     * @param int $maxLength
     * @param bool $stripped
     * @return string $text Formatted text
     */
    public static function formatText($text = '', $maxLength = 0, $stripped = TRUE) {
        // Perlu escape?
        if ($stripped) {
            $text = strip_tags($text);
        }

        // Format
        if ($maxLength > 0) {
            if (strlen($text) > $maxLength) {
                $text = substr($text, 0, ($maxLength-3)).'...';
            }
        }
        
        return $text;
    }

    /**
     * Provider for Build data log
     *
     * @param id $id Log id
     * @param bool $headerOnly Whether to printout the full build detail, or just the key information
     *
     * @return string 
     */
    public function getBuildData($id = 0, $headerOnly = false) {
        // Default data
        $clock = '--:--';
        $label = new Parameter(array(
            'Name' => str_pad('Description',self::PADDING_VENDOR),
            'UsedVersion' => str_pad('Used',self::PADDING_VERSION_LEFT),
            'LatestVersion' => str_pad('Latest',self::PADDING_VERSION_RIGHT),
        ));
        $build = new Parameter(array(
            'Title' => '<span class="b-yellow">Scheduled</span>',
            'ResultText' => str_pad('0 out of 0 passed',self::PADDING_VENDOR),
            'ResultStatus' => '<span class="b-red">'.str_pad('0%',self::PADDING_VERSION,' ',STR_PAD_BOTH).'</span>',
        ));
        $vendors = array(
            new Parameter(array(
                'Name' => str_pad('-',self::PADDING_VENDOR),
                'UsedVersion' => '<span class="b-red">'.str_pad('-',self::PADDING_VERSION_LEFT).'</span>',
                'LatestVersion' => '<span class="b-red">'.str_pad('-',self::PADDING_VERSION_RIGHT).'</span>',
            )),
        );

        // Check the actual log
        if (($log = ModelBase::factory('Log')->getLog($id)) && !empty($log)) {
            // Get the queued status
            $buildQueuedStatus = $log->get('Status') == 0 ? '<span class="b-yellow">Scheduled</span>' : '<span class="b-green">Finished</span>';

            // Set the build attributes
            $build->set('Title', $buildQueuedStatus."\n"
                                 .'Based by composer.json'."\n"
                                 .'rev: '.$log->get('After')."\n"
                                 .'msg: '.$this->setLimitHash($log->get('CommitMessage'),65)."\n"
                                 .'by : '.$log->get('CommitAuthor'));

            // Do we have executed log?
            if ($log->get('Status') > 0 && ($additionalData = $log->get('AdditionalData')) && ! empty($additionalData) && array_key_exists('depsDiff', $additionalData)) {
                // Get depedencies data (and security advices if exists)
                $depsDiff = $additionalData['depsDiff'];
                $advice = isset($additionalData['advice']) ? $additionalData['advice'] : array();

                // Build the vendor status
                if ( ! empty($depsDiff)) {
                    // Reset the vendors value
                    $vendors = array();

                    $outOfdatePackages = 0;

                    foreach ($depsDiff as $dep) {
                        $diffStatus = ($dep['versionDiff'] < 0) ? 'red' : 'green';
                        $rawVersion = $this->setLimitHash($dep['rawVersion'],self::PADDING_VERSION_LEFT);
                        $rawLatestVersion = $this->setLimitHash($dep['rawLatestVersion'],self::PADDING_VERSION_RIGHT);
                        $vendors[] = new Parameter(array(
                            'Name' => str_pad($dep['vendor'],self::PADDING_VENDOR),
                            'UsedVersion' => '<span class="b-'.$diffStatus.'">'.str_pad($rawVersion,self::PADDING_VERSION_LEFT).'</span>',
                            'LatestVersion' => '<span class="b-green">'.str_pad($rawLatestVersion,self::PADDING_VERSION_RIGHT).'</span>',
                        ));

                        if ($diffStatus == 'red') {
                            $outOfdatePackages++;
                        }
                    }

                    // Reset the build status
                    if ($outOfdatePackages == 0) {
                        $status = 'green';
                        $percentage = 100;
                    } elseif (count($depsDiff) == $outOfdatePackages) {
                        $status = 'red';
                        $percentage = 0;
                    } else {
                        $percentage = (int) floor(((count($depsDiff)-$outOfdatePackages)/count($depsDiff))*100);

                        if ($percentage >= 50) {
                            $status = 'yellow';
                        }
                    }

                    $statusText = count($depsDiff)-$outOfdatePackages.' out of '.count($depsDiff).' passed';

                    $build->set('ResultText', str_pad($statusText,self::PADDING_VENDOR));
                    $build->set('ResultStatus','<span class="b-'.$status.'">'.str_pad($percentage.'%',self::PADDING_VERSION,' ',STR_PAD_BOTH).'</span>');

                   
                }

                if ( ! empty($advice)) {
                    // Build advisories data
                    $advisories = '';

                    foreach ($advice as $vendor => $data) {
                        $adviceData = '';
                        if (is_array($data) && array_key_exists('advisories', $data)) {
                            foreach ($data['advisories'] as $adviceArray) {
                                $adviceData .= "\n".' - ';
                                if (isset($adviceArray['title'])) {
                                    $adviceData .= $this->setLimitHash($adviceArray['title'],40);
                                }
                                if (isset($adviceArray['link'])) {
                                    $link = '<a href="'.$adviceArray['link'].'" target="_blank">'
                                            .$this->setLimitHash($adviceArray['link'],20).'</a>';
                                    $adviceData .= ' (see '.$link.')';
                                }
                            }
                        }

                        $advisories .= $vendor.':'.$adviceData."\n";
                    }

                    $currentTitle = $build->get('Title');
                    $build->set('Title', $currentTitle."\n\n"
                                 .'Security advisories based by composer.lock'."\n"
                                 .$advisories);
                }

                // Set the clock
                $clock = date('H:i l d M, Y',$log->get('Executed'));
            }

        }

        $layout = ($headerOnly) ? 'blocks/report.tpl' : 'blocks/build.tpl';

        return self::render($layout,compact('label','clock','build','vendors'));
    }

    /**
     * Provider untuk template Home
     *
     * @param array $otherData Data dari model lain
     *
     * @return array $finalData
     * @see ModelTemplate::finalData
     */
    public function getHomeData($otherData = array()) {
        $data = array(
            'title' => 'Home',
            'content' => NULL,
        );

        return $this->prepareData($data, $otherData);
    }

    /**
     * Provider for template Repo
     *
     * @param array $otherData Data dari model lain
     *
     * @return array $finalData
     * @see ModelTemplate::finalData
     */
    public function getRepoData($otherData = array()) {
        $data = array(
            'title' => 'Repository',
            'content' => NULL,
        );

        return $this->prepareData($data, $otherData);
    }

     /**
     * Provider untuk template Setting
     *
     * @param array $otherData Data dari model lain
     *
     * @return array $finalData
     * @see ModelTemplate::finalData
     */
    public function getSettingData($otherData = array()) {
        $data = array(
            'title' => 'Setting',
            'content' => NULL,
            'menus' => array(
                new Parameter(array(
                    'liClass' => 'nav-header',
                    'text' => 'Setting',
                )),

                new Parameter(array('liClass' => 'divider')),

                new Parameter(array(
                    'liClass' => 'nav-header',
                    'text' => 'Profile',
                )),
                new Parameter(array(
                    'liClass' => '',
                    'text' => 'Information',
                    'link' => '/setting/info',
                    'icon' => 'icon-info-sign',
                )),

                new Parameter(array('liClass' => 'divider')),

                new Parameter(array(
                    'liClass' => 'nav-header',
                    'text' => 'Account',
                )),
                new Parameter(array(
                    'liClass' => '',
                    'text' => 'Github',
                    'link' => '/setting/github',
                    'icon' => 'icon-github-alt',
                )),
                new Parameter(array(
                    'liClass' => '',
                    'text' => 'Token',
                    'link' => '/setting/token',
                    'icon' => 'icon-lock',
                )),
                new Parameter(array(
                    'liClass' => '',
                    'text' => 'Email',
                    'link' => '/setting/mail',
                    'icon' => 'icon-envelope',
                )),
                 new Parameter(array(
                    'liClass' => '',
                    'text' => 'Password',
                    'link' => '/setting/password',
                    'icon' => 'icon-key',
                )),
            ),
        );

        return $this->prepareData($data, $otherData);
    }

    /**
     * Provider untuk template User
     *
     * @param array $otherData Data dari model lain
     *
     * @return array $finalData
     * @see ModelTemplate::finalData
     */
    public function getUserData($otherData = array()) {
        $data = array(
            'title' => 'User',
            'content' => NULL,
        );

        return $this->prepareData($data, $otherData);
    }

    /**
     * Provider untuk template Auth
     *
     * @param array $otherData Data dari model lain
     *
     * @return array $finalData
     * @see ModelTemplate::finalData
     */
    public function getAuthData($otherData = array()) {
        $data = array();

        return $this->prepareData($data, $otherData);
    }

    /**
     * Mendapat default data
     *
     * @return array Default data
     */
    public function getDefaultData() {
        return $this->defaultData;
    }

    /**
     * Custom Twig filter for determine repo state
     */
    public function isRepoOutOfDate($rid) {
        $t = ModelBase::factory('Template');
        return ($t->getRepoLatestLogStatus($rid) == 'outofdate') ? ' c-red' : '-blank';
    }

    /**
     * Custom Twig filter for determine repo state
     */
    public function isRepoNeedUpdate($rid) {
        $t = ModelBase::factory('Template');
        return ($t->getRepoLatestLogStatus($rid) == 'needupdate') ? ' c-yellow' : '-blank';
    }

    /**
     * Custom Twig filter for determine repo state
     */
    public function isRepoUptodate($rid) {
        $t = ModelBase::factory('Template');
        return (in_array($t->getRepoLatestLogStatus($rid),array('uptodate','none'))) ? ' c-green' : '-blank';
    }

    /**
     * Custom Twig filter for limiting tweet length
     */
    public function setLimitTweet($tweet) {
        return ModelBase::factory('Template')->setLimitHash($tweet,85);
    }

    /**
     * Custom Twig filter for limiting hash length
     */
    public function setLimitHash($hash, $count = 0) {
        if ($count > 0) {
            return (strlen($hash) <= $count) ? $hash : substr($hash, 0, ($count-2)).'..';
        }

        return substr($hash, 0, 7).'...';
    }

    /**
     * Custom Twig filter for translate integer data to log status[scheduled|finished]
     */
    public function setLogText($status) {
        return ((int)$status) > 0 ? 'finished' : 'scheduled';
    }

    /**
     * Custom Twig filter for translating integer data into success text [success|warning]
     */
    public function setSuccessText($status) {
        return ((int)$status) > 0 ? 'success' : 'warning';
    }

    /**
     * Custom Twig filter for translating integer data into build status text [grey|red|yellow|green]
     *
     * @param int The log status
     * @param bool Whether to return the full details or just the text
     */
    public function setLogStatusText($logStatus,$asArray = false) {
        switch ($logStatus) {
            case 1:
                $text = 'red';
                $statusText = 'OUT OF DATE';
                $status = 'error';
                break;

            case 2:
                $text = 'yellow';
                $statusText = 'NEED TO UPDATE';
                $status = 'warning';
                break;

            case 3:
            case 4:
                $text = 'green';
                $statusText = 'UP TO DATE';
                $status = 'success';
                break;
            
            default:
                $text = 'grey';
                $statusText = 'UNKNOWN';
                $status = 'inverse';
                break;
        }

        return ($asArray) ? compact('text','status','statusText') : $text;
    }

    /**
     * Custom Twig filter for translating vendor into packagist url
     */
    public function setPackagistUrl($vendor) {
        if ($vendor == 'php') return 'http://php.net/downloads.php';
        return 'https://packagist.org/packages/'.$vendor;
    }

    /**
     * Custom Twig filter for translating repo into its markdown status
     */
    public function setStatusMarkdown($repoId) {
        $repo = ModelBase::factory('Repo')->getQuery()->findPK($repoId);
        $markdown = '[![Dependencies Status](https://depending.in/'.$repo->getFullName().'.png)](http://depending.in/'.$repo->getFullName().')';

        return $markdown;
    }

    /**
     * Custom Twig filter for translating repo state into icon
     */
    public function setProjectIcon($isPackage) {
        return $isPackage == 1 ? 'icon-inbox' : 'icon-github-sign';
    }

    /**
     * Custom Twig filter for translating vendor name into appropriate icon
     */
    public function setVendorIcon($name) {
        return strtolower($name) == 'php' ? 'icon-cogs' : 'icon-inbox';
    }

    /**
     * Custom Twig filter for generate bootstrap alert
     */
    public function setAlert($message = '', $type = 'error') {
        return '<div class="alert alert-'.$type.'">'.$message.'</div>';
    }

    /**
     * Get latest log repo status
     * @param int
     * @return string
     */
    protected function getRepoLatestLogStatus($rid) {
        $repo = ModelBase::factory('Repo')->getQuery()->findPK($rid);
        $repoCopy = clone $repo;

        if (empty($repoCopy)) return 'unknown';

        $latestStatus = ModelBase::factory('Repo')->getStatus($repoCopy);

        return $latestStatus;
    }

    /**
     * PrepareData
     *
     * @param array $data Data default tiap section
     * @param array $otherData Data dari model lain
     *
     * @return array $finalData
     */
    protected function prepareData($data = array(), $otherData = array()) {
        $finalData = $this->defaultData;

        // Hanya merge jika terdapat data
        if ( ! empty ($data)) {
            $finalData = array_merge($finalData,$data);
        }

        if ( ! empty ($otherData)) {
            $finalData = array_merge($finalData, $otherData);
        }

        return $finalData;
    }
}