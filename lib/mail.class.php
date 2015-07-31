<?php
require_once '../3rdparty/AWSSDKforPHP/sdk.class.php';

class Lib_Mail{
	public function __construct() {
		$ses = new AmazonSES();
		$r = $ses->list_verified_email_addresses();

		var_dump($r);
	}
}
?>