<?php
namespace Core;

class Ds {
	private static $stores = array();
    private static $instance;

    public static function connect($dsConfig) {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c($dsConfig);
        }

        return self::getStore($dsConfig);
    }

    private static function getStore($dsConfig) {
    	$dsIdentifier = md5(serialize($dsConfig));
    	$dsConfig = (object)(parse_url($dsConfig));

    	if(!isset(self::$stores[$dsIdentifier])) {
    		if($dsConfig->scheme == 'mysql') {
				$libClassName = 'Lib_' . ucfirst($dsConfig->scheme);
	            self::$stores[$dsIdentifier] = new $libClassName($dsConfig);
    		}

    		if($dsConfig->scheme == 'mongodb') {
				if(property_exists($dsConfig, 'host') && property_exists($dsConfig, 'port')) {
					$database = $collection = null;
					@list($database, $collection) = explode('/', trim($dsConfig->path, '/'));

					try {
						if(app_mongo_no_auth) {
							// Log::write('-----> DB CONN');

							if(phpversion() <= 5.6){
								$mongo = new \MongoClient('mongodb://'. $dsConfig->host,array('db'=>$database,'connect'=>true,'connectTimeoutMS' => 2000));
							}else if(phpversion() >= 8.1){
								$client = new \MongoDB\Client('mongodb://'. $dsConfig->host, [
									'db' => $database, 'connect' => true, 'connectTimeoutMS' => 2000
								], [
									'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']
								]);
							}
							else {
								throw new Exception('Unsupported Parser version '.phpversion());
							}
						}
						else {
							throw new Exception('Unsupported DB mode');
						}

						if(!is_null($database) && !is_null($collection)) {
							//self::$stores[$dsIdentifier] = $mongo->{$database}->{$collection};
							self::$stores[$dsIdentifier] = $client->{$database}->{$collection};
						}
						else {
							if(!is_null($database)) {
								//self::$stores[$dsIdentifier] = $mongo->{$database};
								self::$stores[$dsIdentifier] = $client->{$database};
							}
							else {
								throw new Exception('Missing Database or Collection');
							}
						}
					}
					catch (MongoConnnectionException $e) {
						Log::write($e->getMessafge());
					}
				}
				else {
					throw new Exception('Missing Parameters');
				}
    		}
        }

        return self::$stores[$dsIdentifier];
    }
}
?>