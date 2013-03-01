<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

namespace app\Model;

use app\Parameter;

/**
 * ModelSetting
 *
 * @author depending.in Dev
 */
class ModelSetting extends ModelBase 
{
    /**
     * Handle info
     *
     * @param Parameter $data 
     *
     * @return Parameter $content
     */
    public function handleInfo(Parameter $data) {
        $content = new Parameter(array(
            'title' => 'Information',
        ));

        // Get user data and handle any POST data if exists
        $user = $data->get('user');
        $post = new Parameter($data->get('postData', array()));

        // @codeCoverageIgnoreStart
        if ($post->get('fullname') || $post->get('signature')) {
            $uid = $user->get('Uid');

            if ($post->get('fullname')) {
                // Update custom data
                $updated = ModelBase::factory('User')->updateUserData($uid, array('fullname' => $post->get('fullname')));

                if ( ! empty($updated)) {
                    $content->set('updated', true);
                    $user = $updated;
                }
            }

            if ($post->get('signature')) {
                // Update regular data
                $updated = ModelBase::factory('User')->updateUser($uid, array('signature' => $post->get('signature')));
                $user = empty($updated) ? $user : $updated;

                if ( ! empty($updated)) {
                    $content->set('updated', true);
                    $user = $updated;
                }
            }
        }
        // @codeCoverageIgnoreEnd

        $fullName = str_replace('-', '', $user->get('Fullname'));
        $signature = $user->get('Signature');

       
        // Build inputs
        $inputs = array(
            new Parameter(array(
                'type' => 'text',
                'size' => '4',
                'name' => 'fullname',
                'placeholder' => 'Full Name',
                'value' => $fullName,
            )),
            new Parameter(array(
                'type' => 'textarea',
                'size' => '4',
                'name' => 'signature',
                'placeholder' => 'About you',
                'value' => $signature,
            )),
        );

        $content->set('inputs', $inputs);

        return $content;
    }

    /**
     * Handle email
     *
     * @param Parameter $data 
     *
     * @return Parameter $content
     */
    public function handleMail(Parameter $data) {
        $content = new Parameter(array(
            'title' => 'Email Account',
        ));

        // Get user data and handle any POST data if exists
        $user = $data->get('user');
        $post = new Parameter($data->get('postData', array()));

        // @codeCoverageIgnoreStart
        if ($post->get('email')) {
            $email = filter_var($post->get('email'), FILTER_VALIDATE_EMAIL);

            if ($email) {
                $uid = $user->get('Uid');

                // Update regular data
                $updated = ModelBase::factory('User')->updateUser($uid, array('mail' => $post->get('email')));
                $user = empty($updated) ? $user : $updated;

                if ( ! empty($updated)) {
                    $content->set('updated', true);
                    $user = $updated;
                }
            } else {
                $content->set('error', 'Invalid email!');
            }
        }
        // @codeCoverageIgnoreEnd

        $email = $user->get('Mail');

       
        // Build inputs
        $inputs = array(
            new Parameter(array(
                'type' => 'text',
                'size' => '4',
                'name' => 'email',
                'placeholder' => 'Email that used by this account',
                'value' => $email,
            )),
        );

        $content->set('inputs', $inputs);

        return $content;
    }

    /**
     * Handle password
     *
     * @param Parameter $data 
     *
     * @return Parameter $content
     */
    public function handlePassword(Parameter $data) {
        $content = new Parameter(array(
            'title' => 'Password Account',
        ));

        // Get user data and handle any POST data if exists
        $user = $data->get('user');
        $post = new Parameter($data->get('postData', array()));

        // @codeCoverageIgnoreStart
        if ($post->get('password') || $post->get('cpassword')) {

            $password = $post->get('password');
            $cpassword = $post->get('cpassword');

            if (empty($password) || empty($cpassword)) {
                $content->set('error', 'Fill password and confirmation password fields!');
            } elseif ($password != $cpassword) {
                $content->set('error', 'Password and its confirmation missmatch!');
            } else {
                $uid = $user->get('Uid');

                // Update regular data
                $hashedPassword = ModelBase::factory('Auth')->hashPassword($password);
                $updated = ModelBase::factory('User')->updateUser($uid, array('pass' => $hashedPassword));
                $user = empty($updated) ? $user : $updated;

                if ( ! empty($updated)) {
                    $content->set('updated', true);
                    $user = $updated;
                }
            }
        }
        // @codeCoverageIgnoreEnd

        // Build inputs
        $inputs = array(
            new Parameter(array(
                'type' => 'password',
                'size' => '4',
                'name' => 'password',
                'placeholder' => 'New Password',
                'value' => '',
            )),
            new Parameter(array(
                'type' => 'password',
                'size' => '4',
                'name' => 'cpassword',
                'placeholder' => 'Password confirmation',
                'value' => '',
            )),
        );

        $content->set('inputs', $inputs);

        return $content;
    }
}