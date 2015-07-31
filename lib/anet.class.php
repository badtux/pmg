<?php
	/*
	 * LibAnet response class
	 *
	 * */
	class Lib_Anet_Response {
		public $state = false;
		public $text = '';
		public $code = '';
		public $message = 'An unhandled error occurred';
		public $payment_profile_id = null;
		public $transaction_id = null;
		public $authorization_code = null;
		public $last_digit = null;
		public $card_number = null;
		public $card_type = null;
		public $expiration = null;
	}

	/*
	 * Authorizenet payment helper class
	 *
	 * */
	class Lib_Anet {
		public static $TRANS_AUTHONLY = 'AuthOnly';
		public static $TRANS_PRIORAUTHCAPTURE = 'PriorAuthCapture';
		public static $TRANS_AUTHCAPTURE = 'AuthCapture';
		public static $TRANS_CREDIT = 'Credit';
		public static $TRANS_VOID = 'Void';

		public static function getCardProfiles($customerProfileId) {
			$paymentProfiles = array();
			$request = new AuthorizeNetCIM;
			$request->setSandbox(SANDBOX_MODE);
			$response = $request->getCustomerProfile($customerProfileId);

			if($response->isOk()) {
				foreach ($response->xpath('profile/paymentProfiles') as $sequence => $d) {
					//Log::write(json_encode($d));
					$paymentProfiles[] = array(
						'id' => (string)$d->customerPaymentProfileId,
						'cardnumber' => (string)$d->payment->creditCard->cardNumber,
						'expiration' => (string)$d->payment->creditCard->expirationDate
					);
				}
			}

			if($response->isError()) {
				Log::write(__METHOD__. ' '.$response->getMessageCode().': '.$response->getMessageText());
			}

			return $paymentProfiles;
		}

		public static function doTransaction($type,$transactionData=array()) {
			$request = new AuthorizeNetCIM;
			$requestAim = new AuthorizeNetAIM();


			Log::write(__METHOD__.' sandbox '.(int)SANDBOX_MODE);

			$request->setSandbox(SANDBOX_MODE);
			$transaction = new AuthorizeNetTransaction;
			$libAnetResponse = new Lib_Anet_Response();

			switch ($type) {
				case self::$TRANS_AUTHONLY:
					$amount = $customer_profile_id = $payment_profile_id = $invoice_id = null;
					extract($transactionData, EXTR_OVERWRITE);

					Log::write(__METHOD__.' cp :'.$customer_profile_id.' pp :'.$payment_profile_id.' inv :'.$invoice_id.' amt :'.$amount);
					$cps = Lib_Anet::getCardProfiles($customer_profile_id);
					$transaction->amount = $amount;
				    $transaction->customerProfileId = $customer_profile_id;
				    $transaction->customerPaymentProfileId = $payment_profile_id;
				    $transaction->order->invoiceNumber = $invoice_id;
				    $response = $request->createCustomerProfileTransaction(self::$TRANS_AUTHONLY, $transaction);
				//	$request->createCustomerProfileTransaction($transactionType, $transaction);
				    if($response->isOk()){
				    	Log::write(__METHOD__.' ok');
						$transactionResponse = $response->getTransactionResponse();
						$libAnetResponse->state = true;
						$libAnetResponse->transaction_id = $transactionResponse->transaction_id;
						$libAnetResponse->authorization_code = $transactionResponse->authorization_code;
						$libAnetResponse->message = $transactionResponse->response_reason_text;
						$libAnetResponse->text = $response->getMessageText();
						$libAnetResponse->code = $response->getMessageCode();
						$libAnetResponse->last_digit = trim(str_replace('X', '', $transactionResponse->account_number));
				    }

				    if($response->isError()) {
				    	Log::write(__METHOD__.' err');
				    	$libAnetResponse->state = false;
				    	$libAnetResponse->text = $response->getMessageText();
						$libAnetResponse->code = $response->getMessageCode();
				    }

				    Log::write(__METHOD__.' '.$response->getMessageCode(). ' '.$response->getMessageText());
				    Log::write(__METHOD__.' '.json_encode($libAnetResponse));

				    if($libAnetResponse->text == 'A duplicate transaction has been submitted.') {
				    	$libAnetResponse->text = 'Try again in 2 minutes';
				    }

				    return $libAnetResponse;
					break;

				case self::$TRANS_PRIORAUTHCAPTURE:
					$transaction_id = $amount = null;
					extract($transactionData, EXTR_OVERWRITE);

					$transaction->transId = $transaction_id;
				    $transaction->amount = $amount;
				    $response = $request->createCustomerProfileTransaction(self::$TRANS_PRIORAUTHCAPTURE, $transaction);

					if($response->isOk()){
						$transactionResponse = $response->getTransactionResponse();
						$libAnetResponse->state = true;
						$libAnetResponse->transaction_id = $transactionResponse->transaction_id;
						$libAnetResponse->authorization_code = $transactionResponse->authorization_code;
						$libAnetResponse->message = $transactionResponse->response_reason_text;
						$libAnetResponse->text = $response->getMessageText();
						$libAnetResponse->code = $response->getMessageCode();
				    }

				    if($response->isError()) {
				    	$libAnetResponse->state = false;
						/*$returnResponse->message = $transactionResponse->response_reason_text;*/
						$libAnetResponse->text = $response->getMessageText();
						$libAnetResponse->code = $response->getMessageCode();
				    }

				    return $libAnetResponse;
					break;

				case self::$TRANS_AUTHCAPTURE:
					$amount = $customer_profile_id = $payment_profile_id = null;
					extract($transactionData, EXTR_OVERWRITE);

					$transaction->amount = $amount;
					$transaction->customerProfileId = $customer_profile_id;
					$transaction->customerPaymentProfileId = $payment_profile_id;
					$response = $request->createCustomerProfileTransaction(self::$TRANS_AUTHCAPTURE, $transaction);

				    if($response->isOk()){
						$transactionResponse = $response->getTransactionResponse();
						$libAnetResponse->state = true;
						$libAnetResponse->transaction_id = $transactionResponse->transaction_id;
						$libAnetResponse->authorization_code = $transactionResponse->authorization_code;
						$libAnetResponse->message = $transactionResponse->response_reason_text;
						$libAnetResponse->text = $response->getMessageText();
						$libAnetResponse->code = $response->getMessageCode();
						$libAnetResponse->last_digit = trim(str_replace('X', '', $transactionResponse->account_number));
				    }

				    if($response->isError()) {
				    	$libAnetResponse->state = false;
				    	$libAnetResponse->text = $response->getMessageText();
						$libAnetResponse->code = $response->getMessageCode();
				    }

				    return $libAnetResponse;
					break;

				case self::$TRANS_CREDIT:
					$amount = $customerProfileId = $paymentProfileId = null;
					$transaction->amount = $transactionData->amount;
					$requestAim->setSandbox(SANDBOX_MODE);
					$response = $requestAim->credit($transactionData->id,$transactionData->amount,$transactionData->lfor);
					return true;
					if(!$response->error){
						return true;
					}
					else{
						throw new Exception($response->response_reason_text,512);
					}
					break;

				case self::$TRANS_VOID:
					$amount = $customer_profile_id = $payment_profile_id = $invoice_id = null;
					extract($transactionData, EXTR_OVERWRITE);
					$transaction->amount = $amount;
					$transaction->customerProfileId = $customer_profile_id;
					$transaction->customerPaymentProfileId = $payment_profile_id;
					$transaction->order->invoiceNumber = $invoice_id;
					$response = $request->createCustomerProfileTransaction(self::$TRANS_AUTHONLY, $transaction);
					break;

			}
		}
	}
?>
