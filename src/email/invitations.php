<?php
namespace Planroom\Email;

require_once(__DIR__ . "/../db/orchestrators/jobOrch.php");
require_once(__DIR__ . "/../jwt/orch.php");
require_once(__DIR__ . "/../config/configReader.php");

use JobOrch;
use ConfigReader;

/**
 * Email invitations for jobs
 */
class Invitations {
	/**
	 * Sends a single invitation
	 * @param email address to send to
	 * @param jobId id of job to invite
	 * @param exp unix timestamp for link expiration
	 * @param container dependency container
	 */
	public static function sendInvitation($email, $jobId, $exp, $container) {
		$job = JobOrch::Read($jobId, $container);
		$mail = $container['mailer'];
		$mail->clearAddresses(); // try to avoid sending to extraneous addresses
		$mail->addAddress($email);

		$mail->isHTML(true);
		$mail->Subject = self::buildSubject($job, $container);
		$mail->Body = self::buildBody($email, $job, $exp, $container);
		$mail->AltBody = self::buildAltBody($email, $job, $exp, $container);

		$mail->send();
		$container['logger']->info('Invitation Sent', array('email' => $email, 'Job Id' => $jobId, 'Expiration' => $exp));
		$mail->clearAddresses(); // so they aren't there for the next email.
	}

	/**
	 * Generates the subject for the email
	 * @param job object of the job for invitation
	 * @param container dependency container
	 * 
	 * @returns subject
	 */
	public static function buildSubject($job, $container) {
		return "Invitation To Bid: " . $job['name'];
	}

	/**
	 * Creates the html body of the invitation
	 * @param email - email being invited
	 * @param job - object of the job in invitation
	 * @param exp - unix expiration time
	 * @param container - dependency container
	 * 
	 * @returns html body
	 */
	public static function buildBody($email, $job, $exp, $container) {
		$dt = new \DateTime('@' . $exp);
		$dt->setTimeZone(new \DateTimeZone('America/Indianapolis'));
		$expStr = $dt->format("F j, Y, g:i a");  
		$token = \Planroom\JWT\Orch::getSubcontractorToken($email, $jobId, $exp, $container);
		$link = ConfigReader::getBaseUrl() . '/jobs/' . $jobId . '?token=' . $token;
		$body = '<center>
	<img src="https://benchmarkmechanical.com/Images/logo1.jpg" />
	<br><br><br>
	<div style="width:60%;border:1px solid lightgrey">
		<h1>Invitation to Bid</h1>
		<h2>' . $job['name'] . '</h2>
		<a href="' . $link . '">Click Here</a> to access bidding documents and project details.<br>This link will expire ' . $expStr . '.
		<br><br><br>
		<span style="color:grey;font-size:10pt"><em>Please do not reply to this email. The mailbox is not monitored.</em></span>
	</div>
</center>';
		return $body;
	}
	/**
	 * Creates the text only alternate body of the invitation
	 * @param email - email being invited
	 * @param job - object of the job in invitation
	 * @param exp - unix expiration time
	 * @param container - dependency container
	 * 
	 * @returns alternate body
	 */
	public static function buildAltBody($email, $job, $exp, $container) {
		$dt = new \DateTime('@' . $exp);
		$dt->setTimeZone(new \DateTimeZone('America/Indianapolis'));
		$expStr = $dt->format("F j, Y, g:i a");  
		$token = \Planroom\JWT\Orch::getSubcontractorToken($email, $jobId, $exp, $container);
		$link = ConfigReader::getBaseUrl() . '/jobs/' . $jobId . '?token=' . $token;
		$body = 'This is an invitation from Benchmark Mechanical to bid on the ' . $job['name'] . ' project. Bidding documents';
		$body .= 'and project details are available at the link below. The link will expire ' . $expStr . '.';
		$body .= '\n\n';
		$body .= $link;
		$body .= '\n\n';
		$body .= 'Please do not reply to this email. The mailbox is not monitored';
		return $body;
	}
}