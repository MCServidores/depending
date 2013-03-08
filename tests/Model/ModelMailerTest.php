<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

use app\Parameter;
use app\Model\ModelMailer;

class ModelMailerTest extends DependingInTestCase {

	/**
	 * Cek sendReport
	 */
	public function testCekSendReportModelMailer() {
		$receiver = new Parameter(array(
			'toName' => 'Mr. Y U not work',
			'toEmail' => 'foo@bar.com',
		));

		$mailer = new ModelMailer($receiver);

		$this->assertTrue($mailer->sendReport('Hey there',array('content' => 'I have i build report here...')));
	}
}