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
        // Inisialisasi Twig. Load template yang berkaitan dan assign data.
        $loader = new Twig_Loader_Filesystem(APPLICATION_PATH . DIRECTORY_SEPARATOR . 'Templates');
        $templateEngine = new Twig_Environment($loader);

        // Filter declaration
        $filters = array(
            new Twig_SimpleFilter('limitHash', array(__CLASS__, 'setLimitHash')),
            new Twig_SimpleFilter('translateToLogText', array(__CLASS__, 'setLogText')),
            new Twig_SimpleFilter('translateToSuccessText', array(__CLASS__, 'setSuccessText')),
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
     * Custom Twig filter for limiting hash length
     */
    public function setLimitHash($hash) {
        return substr($hash, 0, 7).'...';
    }

    /**
     * Custom Twig filter for translate integer data to log status[scheduled|finished]
     */
    public function setLogText($status) {
        return ((int)$status) == 1 ? 'finished' : 'scheduled';
    }

    /**
     * Custom Twig filter for translating integer data into success text [success|warning]
     */
    public function setSuccessText($status) {
        return ((int)$status) == 1 ? 'success' : 'warning';
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