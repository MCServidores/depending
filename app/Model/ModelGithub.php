<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

namespace app\Model;

use app\Parameter;

/**
 * ModelGithub
 *
 * @author depending.in Dev
 */
class ModelGithub extends ModelBase 
{
    const ACCESS_TOKEN_URL = 'https://github.com/login/oauth/access_token';
    const AUTHORIZE_URL = 'https://github.com/login/oauth/authorize';
    const API_URL = 'https://api.github.com/';
    protected $clientId = '460190b3cf77dd2305ec';
    protected $clientSecret = '4c34311721e46c38c342420b5d101dd5670f16f6';
    protected $scope = 'user:email,public_repo';
    protected $accessToken = '';
    protected $redirectUrl = '';

    /**
     * Constructor
     */
    public function __construct(Parameter $params) {
        $this->accessToken = $params->get('githubToken');
        $this->redirectUrl = $params->get('redirectUrl');
    }

    /**
     * Get user data
     */
    public function getUser() {
        if (empty($this->accessToken)) return false;

        $response = $this->getData(self::API_URL.'user', array('access_token' => $this->accessToken));

        if ($response->get('result') == false || strpos($response->get('body'),'login')===false) {
            return false;
        }

        // Parsing the body
        if (($user = json_decode($response->get('body'))) && empty($user)) {
            return false;
        }

        $user = (array) $user;
        $userData = new Parameter($user);
        $userData->set('username', $userData->get('login'));

        return $userData;
    }

    /**
     * Retrieve access parameter from code
     * @param string Code
     */
    public function getAccessToken($code) {
        $params = array(
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUrl,
            'code' => $code,
        );

        $response = $this->postData(self::ACCESS_TOKEN_URL,$params);

        if (strpos($response->get('body'), 'access_token') !== false) {
            // Get access token and token type
            list($accessTokenQuery,$tokenTypeQuery) = explode('&', $response->get('body'));

            return str_replace('access_token=', '', $accessTokenQuery);
        } else {
            return false;
        }
    }

    /**
     * Build login url
     */
    public function getLoginUrl() {
        $params = array(
            'client_id' => $this->clientId,
            'scope' => $this->scope,
            'redirect_uri' => $this->redirectUrl,
        );

        return self::AUTHORIZE_URL.'?'.http_build_query($params);
    }

    public function postData($url, $data) {
         try {
            // Start output buffer
            ob_start();

            //open connection
            $request = curl_init();

            curl_setopt($request, CURLOPT_URL, $url);
            curl_setopt($request, CURLOPT_POSTFIELDS, http_build_query($data));

            //execute post
            $result  = curl_exec($request);
            $err     = curl_errno($request); 
            $errmsg  = curl_error($request) ;
            $head    = curl_getinfo($request);

            //close connection
            curl_close($request);

            // Capture the buffer and assign into content holder
            $body = ob_get_clean();
        } catch (\Exception $e) {
            throw new \RuntimeException('cURL POST error');
        }

        return new Parameter(compact('result', 'err', 'errmsg', 'head', 'body'));
    }

    public function getData($url, $data) {
        $url .= '?'.http_build_query($data);

        try {
            // Start output buffer
            ob_start();

            //open connection
            $request = curl_init();

            curl_setopt($request, CURLOPT_URL, $url);

            //execute post
            $result  = curl_exec($request);
            $err     = curl_errno($request); 
            $errmsg  = curl_error($request) ;
            $head    = curl_getinfo($request);

            //close connection
            curl_close($request);

            // Capture the buffer and assign into content holder
            $body = ob_get_clean();
        } catch (\Exception $e) {
            throw new \RuntimeException('cURL GET error');
        }

        return new Parameter(compact('result', 'err', 'errmsg', 'head', 'body'));
    }
    
}