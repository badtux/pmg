<?php
	/* set_include_path(implode(PATH_SEPARATOR, array_unique(array_merge(
		array(pmg_root.'pg'),
		explode(PATH_SEPARATOR, get_include_path())
	)))); */

	spl_autoload_register(function ($className) {
		//echo $className;
		$knownClasses = array(
			//'Facebook' => app_3rdparty_path . 'facebook'. DIRECTORY_SEPARATOR . 'facebook.php',
			//'twitter' => app_3rdparty_path . 'twitter'. DIRECTORY_SEPARATOR . 'twitter.php',
            'FacebookSession'=>app_3rdparty_path . 'facebook'. DIRECTORY_SEPARATOR . 'FacebookSession.php',
			'tmhOAuth' => app_3rdparty_path . 'twitter'. DIRECTORY_SEPARATOR . 'tmhOAuth.php',
			'tmhUtilities' => app_3rdparty_path . 'twitter'. DIRECTORY_SEPARATOR . 'tmhUtilities.php',
			'Services_Twilio'=>app_3rdparty_path.'twilio'.DIRECTORY_SEPARATOR.'Services/Twilio.php',
			'FeedWriter'=>app_3rdparty_path.'feed'.DIRECTORY_SEPARATOR.'aiofeedwriter.php',
			'SimplePie'=>app_3rdparty_path.'feed'.DIRECTORY_SEPARATOR.'simplepie.php',
			'Google_Client' => app_3rdparty_path . 'google-api-php-client/src'. DIRECTORY_SEPARATOR . 'Google_Client.php',
			'YahooOAuthApplication' => app_3rdparty_path . 'yahoo-api-lib'. DIRECTORY_SEPARATOR . 'Yahoo/YahooOAuthApplication.class.php',
			'LiveAPIClient' => app_3rdparty_path . 'live_api'. DIRECTORY_SEPARATOR . 'LiveAPIClient.php',
			'OpenGraph' => app_3rdparty_path . 'opengraph'. DIRECTORY_SEPARATOR . 'opengraph.php',
            'TwitterOAuth'=> app_3rdparty_path.'twitter_new'.DIRECTORY_SEPARATOR.'twitter.class.php',


			/*
			 * core classes
			 *
			 * */
			'Router' => app_class_path . DIRECTORY_SEPARATOR . 'router.class.php',
			'Ctrl' => app_class_path . DIRECTORY_SEPARATOR . 'ctrl.class.php',
			'Request' => app_class_path . DIRECTORY_SEPARATOR . 'request.class.php',
			'Ds' => app_class_path . DIRECTORY_SEPARATOR . 'ds.class.php',
			'Log' => app_class_path . DIRECTORY_SEPARATOR . 'log.class.php',
			'DbMySQLi' => app_class_path . DIRECTORY_SEPARATOR . 'dbmysqli.class.pgp',
			//'Message' => app_class_path . DIRECTORY_SEPARATOR . 'message.class.php',
			'Utils' => app_class_path . DIRECTORY_SEPARATOR . 'utils.class.php',
			'Qcurl'=>app_class_path . DIRECTORY_SEPARATOR . 'qcurl.class.php',
			'File' => app_class_path . DIRECTORY_SEPARATOR . 'file.class.php',
			'Session' => app_class_path . DIRECTORY_SEPARATOR . 'session.class.php',
			'Cipher' => app_class_path . DIRECTORY_SEPARATOR . 'cipher.class.php',
			'Queue' => app_class_path . DIRECTORY_SEPARATOR . 'queue.class.php',
			'Sanitizer' => app_class_path . DIRECTORY_SEPARATOR . 'sanitizer.class.php',
			'Analytic' => app_class_path . DIRECTORY_SEPARATOR . 'analytic.class.php',
			'Statistics' => app_class_path . DIRECTORY_SEPARATOR . 'statistics.class.php',

			//'Object'=>app_class_path . DIRECTORY_SEPARATOR . 'object.class.php',
			/*
			 * lib classes
			 *
			 * */
			'Lib_Validate' => app_lib_path . DIRECTORY_SEPARATOR . 'validate.class.php',
			'Lib_Mysql' => app_lib_path . DIRECTORY_SEPARATOR . 'mysql.class.php',
			'Lib_MysqlResult' => app_lib_path . DIRECTORY_SEPARATOR . 'mysqlresult.class.php',
			'Lib_Image' => app_lib_path . DIRECTORY_SEPARATOR . 'image.class.php',
			'Lib_Anet_Response'=> app_lib_path . DIRECTORY_SEPARATOR . 'anet.class.php',
			'Lib_Anet'=> app_lib_path . DIRECTORY_SEPARATOR . 'anet.class.php',
			'Lib_Cur'=> app_lib_path . DIRECTORY_SEPARATOR . 'cur.class.php',
			'Lib_Mail'=> app_lib_path . DIRECTORY_SEPARATOR . 'mail.class.php',
		);

		if (substr($className, 0,15) == '\Thirdparty\Yelp') {
			require_once app_3rdparty_path . 'yelp'. DIRECTORY_SEPARATOR . 'OAuth.php';
		}
		else if(substr($className, 0,12) == 'AuthorizeNet') {
			require_once app_3rdparty_path.'anet_php_sdk/AuthorizeNet.php';
		}
		else if(array_key_exists($className, $knownClasses)) {

            require_once $knownClasses[$className];
		}
		else if (substr($className, 0, 15) == 'Services_Twilio') {
			$file = str_replace('_', '/', $className);
			require_once app_3rdparty_path. 'twilio/'.$file.'.php';
		}
        else if(preg_match('/Facebook/', $className)){

            //$file=substr($className,9,-1);
            $file=explode("\\",$className);
            //print_r($file);
            require_once app_3rdparty_path . 'facebook/'.$file[1].'.php';
        }
        else if(preg_match('/ColorThief/',$className))
        {
            if(preg_match('/ImageLoader/',$className))
            {
                require_once app_3rdparty_path .'ColorThief/Image/ImageLoader.php';
            }
            elseif(preg_match('/ImagickImageAdapter/',$className))
            {
                 //echo $className;
                require_once app_3rdparty_path .'ColorThief/Image/Adapter/ImagickImageAdapter.php';

            }
            elseif(preg_match('/ImageAdapter/',$className))
            {
                if(substr($className,25)=="IImageAdapter")
                {
                    require_once app_3rdparty_path .'ColorThief/Image/Adapter/IImageAdapter.php';
                }
                else
                {
                require_once app_3rdparty_path .'ColorThief/Image/Adapter/ImageAdapter.php';
                }
            }
            elseif(preg_match('/VBox/',$className))
            {
                require_once app_3rdparty_path .'ColorThief/VBox.php';
            }
            elseif(preg_match('/PQueue/',$className))
            {
                require_once app_3rdparty_path .'ColorThief/PQueue.php';
            }
            elseif(preg_match('/CMap/',$className))
            {
                require_once app_3rdparty_path .'ColorThief/CMap.php';
            }

            else
            {
            require_once app_3rdparty_path .'ColorThief/ColorThief.php';
            }
        }


		else if(preg_match('/Google_/', $className)){
			require_once app_3rdparty_path . 'facebook/'.$className.'.php';
		}
		else if(preg_match('/Services_Soundcloud/', $className)){
			require_once app_3rdparty_path. 'soundcloud/Soundcloud.php';
		}
		else if(preg_match('/ApnsPHP/', $className)){
			require_once app_3rdparty_path. 'ApnsPHP/Autoload.php';
		}
		else {

			$classPath = null;
			if(strpos($className, '\\') !== false) {
				$classFile = pmg_root.strtolower(str_replace('\\','/',$className)).'.class.php';

				if(!file_exists($classFile)) {
					$classFile = app_root.str_replace('\\','/',$className).'.class.php';
				}

				if(file_exists($classFile)) {$classPath =  $classFile; }
			}
			else {
				$classPath = app_core_path . strtolower(str_replace('_', DIRECTORY_SEPARATOR, $className)) . '.class.php';
			}

			if(is_file($classPath)) {
				require_once $classPath;
			}
			else {
				throw new Exception('class ' . $className . ' not found');
				exit();
			}
		}
	});

	register_shutdown_function(array('Core\Utils','shutDown'));
?>