<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

namespace app\Model;

use app\Parameter;
use \Swift_Mailer;
use \Swift_Message;
use \Swift_Attachment;
use \Swift_SendmailTransport;

/**
 * ModelMailer
 *
 * @author depending.in Dev
 */
class ModelMailer extends ModelBase 
{
	protected $subject;
	protected $fromName;
	protected $fromEmail;
	protected $toName;
	protected $toEmail;
	protected $messageBody;
	protected $messageType;
	protected $attachmentFile;

	/**
	 * Constructor
	 */
	public function __construct(Parameter $parameter) {
		// Initialize all parameter
		$this->subject = $parameter->get('subject', 'undefined');
		$this->fromName = $parameter->get('fromName', 'depending.in');
		$this->fromEmail = $parameter->get('fromEmail', 'no-reply@depending.in');
		$this->toName = $parameter->get('toName', 'undefined');
		$this->toEmail = $parameter->get('toEmail', 'undefined');
		$this->messageBody = $parameter->get('messageBody', 'undefined');
		$this->messageType = $parameter->get('messageType', 'text/plain');
		$this->attachmentFile = $parameter->get('attachmentFile', '');
	}

	/**
	 * Send register confirmation
	 *
	 * @param string $link URL untuk konfirmasi
	 */
	public function sendRegisterConfirmation($link = '') {
		// Sily
		if (empty($link)) return false;

		// Kumpulkan data
		$data = array(
			'title' => 'Registration Confirmation',
			'content' => 'Thanks for register at depending.in. To complete the registration process, please visit below link.',
			'link' => $link,
			'linkText' => 'Confirm',
		);

		// Message parameter
		$this->subject = 'Registration Confirmation';
		$this->messageType = 'text/html';
		$this->messageBody = ModelBase::factory('Template')->render('email.tpl', $data);

		return $this->send();
	}

	/**
	 * Send register confirmation
	 *
	 * @param string $link URL untuk konfirmasi
	 */
	public function sendResetPassword($link = '') {
		// Sily
		if (empty($link)) return false;

		// Kumpulkan data
		$data = array(
			'title' => 'Password Reset',
			'content' => 'Recently there was a request to reset your password. If you\'re not requesting for this action, just ignore this message. Otherwise, please visit bellow link.',
			'link' => $link,
			'linkText' => 'Password Reset',
		);

		// Message parameter
		$this->subject = 'Password Reset';
		$this->messageType = 'text/html';
		$this->messageBody = ModelBase::factory('Template')->render('email.tpl', $data);

		return $this->send();
	}

	/**
	 * Send report
	 *
	 * @param string $reportTitle 
	 * @param array $reportData 
	 */
	public function sendReport($reportTitle, $reportData) {
		// Sily
		if (empty($reportData)) return false;

		// Kumpulkan data
		$data = $reportData;

		// Message parameter
		$this->subject = $reportTitle;
		$this->messageType = 'text/html';
		$this->messageBody = ModelBase::factory('Template')->render('email.tpl', $data);

		return $this->send();
	}

    /**
     * Send the message
     *
     * @param bool $log Whether to log process or not 
     *
     * @return bool 
     */
    public function send($log = false) {
    	// Initialize the transport and mailer instances
		$transport = Swift_SendmailTransport::newInstance('/usr/sbin/sendmail -bs');
		$mailer = Swift_Mailer::newInstance($transport);
		
		//Create a message
		$message = Swift_Message::newInstance($this->subject.'[depending.in]')
			  ->setFrom($this->fromEmail, $this->fromName)
			  ->setTo($this->toEmail, $this->toName)
			  ->setBody($this->messageBody, $this->messageType);

		// Check for attachment
		// @codeCoverageIgnoreStart
		if ( ! empty($this->attachmentFile) && file_exists($this->attachmentFile)) {
			$message->attach(Swift_Attachment::fromPath($this->attachmentFile)); 
		}
		// @codeCoverageIgnoreEnd
		  
		//Send the message
		if (defined('STDIN')) {
			// Avoid sending email from unit-testing
			return true;
		}

		// @codeCoverageIgnoreStart
		try {
			$result = $mailer->send($message); 
		} catch (\Exception $e) {
			$result = false;
		}

		return $result;
		// @codeCoverageIgnoreEnd
	}
}