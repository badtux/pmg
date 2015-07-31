<?php
namespace Core;

class Queue {
	const HANDLER_METHOD = 'handler';
	const GEARMAN_SERVER = '127.0.0.1';
	const GEARMAN_PORT = '4730';
	private static $client = null;

	public static function getGearmanClient() {
		if(!self::$client instanceof GearmanClient) {
			self::$client = new GearmanClient();
			self::$client->addServer(self::GEARMAN_SERVER,self::GEARMAN_PORT);
		}
		return self::$client;
	}
}
?>