<?php
namespace Core;
use App\Job;
use Core\Utils;
use Html2Text\Html2Text;


class Mailer {
	const MAIL_TYPE_TEXT = 'text';
	const MAIL_TYPE_HTML = 'html';

	/**
	 * @param string $fromMail
	 * @param array $recipient
	 * @param path $template
	 * @param array or string $body
	 * @param string $templatesequence
	 */
	public static function submit($fromMail, $recipients, $subject, $body, $template = null, $templatesequence = null){
		try{
			$mail_queue_collection = Ds::connect(ds_mail_queue);
			$type = self::MAIL_TYPE_TEXT;
			
			if(!is_array($recipients)){
				throw New Exception('recipients must be an array');
			}
			
			if(is_null($templatesequence)){
				$templatesequence = md5(serialize($recipients).'|'.$subject.'|'.time());
			}
			
			$tpls = $mail_queue_collection->findOne(array('tpsq' => $templatesequence));
			if(!is_null($tpls)){
				throw New Exception('email already queued with this tpl seq - '.$templatesequence);
			}
			
			if(is_array($body)){
				if(is_null($template)){
					throw New Exception('template cannot be null for html email');
				}
				$type = self::MAIL_TYPE_HTML;
				$body = Utils::parseMe($template, $body);
			}
			
			$mailData = array(
					'from' => $fromMail,
					'subj' => $subject,
					'recp' => $recipients,
					'body' => $body,
					'tpsq' => $templatesequence,
					'type' => $type,
					'qts' => time()
			);
			
			$mail_queue_collection->insert($mailData);
			
			if(isset($mailData['_id'])){
				if(defined('gearman_server') && defined('gearman_port')){
					self::invokeGearmanJob((string)$mailData['_id']);
				}
				else{
					Log::write(__METHOD__.' gearman is not configured for this app');
				}				
				throw New Exception('mail ' .(string)$mailData['_id']. ' queued', 200);
			}
			else{
				throw New Exception('mail queuing failed');
			}			
		}
		catch(Exception $e){
			Log::write(__METHOD__.' ' .$e->getMessage(). ' '.$e->getCode());
			if($e->getCode() != 200){
				throw $e;
			}
		}
	}
	
	public static function processGearmanJob($id){
		$mail_queue_collection = Ds::connect(ds_mail_queue);
		$mail = $mail_queue_collection->findOne(array('_id' => new \MongoId($id),'cts' => array('$exists' => false), 'err' => array('$exists' => false)));
		if(is_null($mail)){
			Log::write(__METHOD__.' job '.$id.' is either already done or could not be found');
		}
		else{
			self::sendIt($mail);
		}
	}
	
	/**
	 * invokes a gearman job for the given mail id
	 * @param string $id
	 * @throws Exception
	 */
	private static function invokeGearmanJob($id){
		Job::addJob($id, 'processMailJob', array('mailid' => $id));
	}
	
	/**
	 * process all pending mails in db, use this for crons
	 */
	public static function process(){
		$mail_queue_collection = Ds::connect(ds_mail_queue);
		$cur = $mail_queue_collection->find(array('cts' => array('$exists' => false), 'err' => array('$exists' => false)));
		
		Log::write(__METHOD__ . ' '.$cur->count() . ' mails found');
		foreach ($cur as $mail) {
			self::sendIt($mail);
		}
	}	
	
	/**
	 * this is where the mailer will pass the job to the mailer
	 */
	private static function sendIt($mail){
		$mailerState = self::sendMail(
/*				array(
						'to' => $mail['recp'],
						'cc' => (isset($mail['cc']) ? $mail['cc'] : array()),
						'bcc' => (isset($mail['bcc']) ? $mail['bcc'] : array())
				),*/
				$mail['recp'],
				$mail['subj'],
				$mail['body'],
				$mail['type'] == self::MAIL_TYPE_HTML,
				$mail['from']
		);
		
		if(self::mailerCompleted($mail['_id'], $mailerState)) {
			Log::write(__METHOD__,' mailer said : '.(string)$mail['_id'] . ' ' .(string)$mailerState);
		}
	}

	/**
	 * mail was sent
	 */
	private static function mailerCompleted($id, $state = true) {
		$mail_queue_collection = Ds::connect(ds_mail_queue);
		$err = null;
		if ($state !== true) { 
			$err = (string)$state; 
		}

		$mail_queue_collection->update(array('_id' => $id), array('$set' => array('cts' => time(), 'err' => $err)));
		return true;
	}

	/**
	 * 
	 * @param array $recipients
	 * @param string $subject
	 * @param string $mailBody
	 * @param boolean $isHtml
	 * @param boolean $from
	 */
	private static function sendMail($recipients, $subject, $mailBody, $isHtml, $from = false) {
		if(!(bool)strlen($mailBody)) {
			$msg = 'Empty mail body found. Sending defered';
			return false;
		}
		else {
			try{
				$mail = new Phpmailer();
				// $mail->IsSendmail();
				$fromEmail = app_mail_from_email; 
				$fromName = app_mail_from_name;
				
				if($from && is_array($from) && !empty($from)) {
					if(isset($from['fromEmail'])) {  $fromEmail = $from['fromEmail']; } 
					if(isset($from['fromName'])) { $fromName = $from['fromName']; }
				}
				
				$mail->Subject = $subject;
				$mail->Encoding = 'base64'; //'quoted-printable';
				$mail->Hostname = app_mail_host;
				$mail->Sender = $fromEmail;
				$mail->set('Return-Path', $fromName);
				$mail->AddReplyTo($fromEmail,$fromName);
				$mail->SetFrom($fromEmail, $fromName);

				if((bool)$isHtml) {
					$mail->MsgHTML((string)$mailBody);
					require_once pmg_root . 'includes' . DIRECTORY_SEPARATOR . 'html2textnew.inc.php';
					$mailtotext = new \Html2Text\Html2Text((string)$mailBody);
                    //$mail->AltBody = convert_html_to_text((string)$mailBody);
                    $mail->AltBody = $mailtotext->get_text();

				}
				else {
					$mail->Body = (string)$mailBody;
				}
				//$mail->SetWordWrap();
				if(SANDBOX_MODE) {
					$mail->AddAddress('tech@sorewarding.com',$toName);
				}
				else {

					foreach ($recipients['to'] as $recipient){
						$toName = isset($recipient['toName']) ? $recipient['toName'] : '';

						if (!isset($recipient['toMail'])) {
							if (filter_var($recipient,FILTER_VALIDATE_EMAIL)) {
								$recipient['toMail'] = $recipient;	
							}
							else{
								throw new Exception("Error Processing Request");
							}
						}
						$mail->AddAddress($recipient['toMail'],$toName);
					}

					foreach ($recipients['cc'] as $recipient){
						$mail->AddCC($recipient['ccEmail'],$recipient['ccName']);
					}

					foreach ($recipients['bcc'] as $recipient){
						$mail->AddBCC($recipient['bccEmail'],$recipient['bccName']);
					}
				}

				if($mail->Send()) {
					Log::write(__METHOD__.' Mail sent to '.json_encode($recipients['to']));
					$mail->SmtpClose();
					return true;
				}
				else {
					$mail->SmtpClose();
					return $mail->ErrorInfo;
				}
			}
			catch(Exception $e){
				Log::write($e->getMessage());
				return false;
			}
		}
	}	
} ?>
