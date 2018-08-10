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
	public static function submit($fromMail, $recipients, $subject, $body, $template = null, $templateSequence = null, $attachments=[]){
		try{
			if(!app_live) { $subject = 'TEST MAIL - '.$subject; }
			$mail_queue_collection = Ds::connect(ds_mail_queue);
			$type = self::MAIL_TYPE_TEXT;
			
			if(!is_array($recipients)){
				throw New Exception('recipients must be an array');
			}
			
			if(is_null($templateSequence)){
				$templateSequence = md5(serialize($recipients).'|'.$subject.'|'.time());
			}
			
			$tpls = $mail_queue_collection->findOne(array('tpsq' => $templateSequence));
			if(!is_null($tpls)){
				throw New Exception('email already queued with this tpl seq - '.$templateSequence);
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
				'tpsq' => $templateSequence,
				'type' => $type,
				'atch' => $attachments,
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
		$mail = $mail_queue_collection->findOne([
			'_id' => new \MongoId($id),
			'cts' => ['$exists' => false],
			'err' => ['$exists' => false]
		]);

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
			$mail['recp'],
			$mail['subj'],
			$mail['body'],
			$mail['type'] == self::MAIL_TYPE_HTML,
			$mail['from'],
			$mail['atch']
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

	public static function sendMailNow($recipients, $subject, $mailBody, $isHtml, $from = false, $attachments=[]) {
		self::sendMail($recipients, $subject, $mailBody, $isHtml, $from, $attachments);
    }
	/**
	 * 
	 * @param array $recipients
	 * @param string $subject
	 * @param string $mailBody
	 * @param boolean $isHtml
	 * @param boolean $from
	 */
	private static function sendMail($recipients, $subject, $mailBody, $isHtml, $from = false, $attachments=[]) {
		if(!(bool)strlen($mailBody)) {
			$msg = 'Empty mail body found. Sending defered';
			return false;
		}
		else {
			try{
				$mail = new Phpmailer();
				$fromEmail = app_mail_from_email; 
				$fromName = app_mail_from_name;
				
				if($from && is_array($from) && !empty($from)) {
					if(isset($from['fromEmail'])) {  $fromEmail = $from['fromEmail']; } 
					if(isset($from['fromName'])) { $fromName = $from['fromName']; }
				}

				Log::write(__METHOD__ . ' before');

				$mail->SMTPDebug = 3;

				if(defined('app_mail_host')){
					Log::write('defined');
				}
				else {
					Log::write('not defined');
				}

				if(defined('app_mail_host') && defined('app_mail_port') && defined('app_mail_username') && defined('app_mail_password')){
                    Log::write(__METHOD__.' in SMTP with '.app_mail_host.':'.app_mail_port.' via '.app_mail_username);
                    $mail->isSMTP();
					$mail->Host = 'tls://'.app_mail_host.':'.app_mail_port;
                    //$mail->Hostname = app_mail_host;
                    //$mail->Port = app_mail_port;
                    //$mail->SMTPSecure = 'tls';
                    $mail->SMTPAuth = true;
                    $mail->Username = app_mail_username;
                    $mail->Password = app_mail_password;

					$mail->SMTPOptions = array(
						'ssl' => array(
							'verify_peer' => false,
							'verify_peer_name' => false,
							'allow_self_signed' => true
						)
					);
                }
                else {
                    Log::write(__METHOD__.' in Sendmail');
                    $mail->IsSendmail();
                }

                $mail->Subject = $subject;
				$mail->Encoding = 'base64'; //'quoted-printable';

				$mail->Sender = $fromEmail;
				$mail->set('Return-Path', $fromName);
				$mail->AddReplyTo($fromEmail,$fromName);
				$mail->SetFrom($fromEmail, $fromName);

				foreach ($attachments as $attachment){
                    Log::write(__METHOD__.' '.$attachment);

					$mail->addAttachment($attachment);
				}

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
                    Log::write(__METHOD__.' in Sandbox mode');
					$mail->AddAddress('viraj.abayarathna@gmail.com','Support - Rype3');
				}
				else {
                    Log::write(__METHOD__.' in Production mode');
					foreach($recipients as $kind => $addresses) {
						foreach($addresses as $address) {
							if($kind === 'to') {
								$mail->AddAddress($address['toMail'], $address['toName']);
							}
							elseif($kind === 'cc') {
								$mail->AddCC($address['toMail'], $address['toName']);
							}
							else {
								$mail->AddBCC($address['toMail'], $address['toName']);
							}
						}
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
