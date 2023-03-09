<?php
	/* set_include_path(implode(PATH_SEPARATOR, array_unique(array_merge(
		array(pmg_root.'pg'),
		explode(PATH_SEPARATOR, get_include_path())
	)))); */
	require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor'. DIRECTORY_SEPARATOR . 'autoload.php';
	
	spl_autoload_register(function ($className) {
		$knownClasses = array(
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

			'HTMLToPDF' => app_class_path . DIRECTORY_SEPARATOR . 'htmltopdf.class.php',

			//'Object'=>app_class_path . DIRECTORY_SEPARATOR . 'object.class.php',
			/*
			 * lib classes
			 *
			 * */
			'Lib_Validate' => app_lib_path . DIRECTORY_SEPARATOR . 'validate.class.php',
			'Lib_Mysql' => app_lib_path . DIRECTORY_SEPARATOR . 'mysql.class.php',
			'Lib_MysqlResult' => app_lib_path . DIRECTORY_SEPARATOR . 'mysqlresult.class.php',
			'Lib_Image' => app_lib_path . DIRECTORY_SEPARATOR . 'image.class.php',
			//'Lib_Anet_Response'=> app_lib_path . DIRECTORY_SEPARATOR . 'anet.class.php',
			//'Lib_Anet'=> app_lib_path . DIRECTORY_SEPARATOR . 'anet.class.php',
			'Lib_Cur' => app_lib_path . DIRECTORY_SEPARATOR . 'cur.class.php',
			//'Lib_Mail'=> app_lib_path . DIRECTORY_SEPARATOR . 'mail.class.php',
			//'Lib_Mail'=> app_lib_path . DIRECTORY_SEPARATOR . 'mail.class.php',
			//'MongoDB\Client' => app_vendor_path . DIRECTORY_SEPARATOR . 'autoload.php',
		);

		if(array_key_exists($className, $knownClasses)) {
            require_once $knownClasses[$className];
		}
		else {
			$classPath = null;

			if(strpos($className, '\\') !== false) {

				$classFile = pmg_root.strtolower(str_replace('\\','/',$className)).'.class.php';

				if(!file_exists($classFile)) {
                    $classFile = app_root.str_replace('\\','/',$className).'.class.php';
                }

				if(file_exists($classFile)) { $classPath =  $classFile; }
			}
			else {
				$classPath = app_core_path . strtolower(str_replace('_', DIRECTORY_SEPARATOR, $className)) . '.class.php';
			}

			if(is_file($classPath)) {
				require_once $classPath;
			}
			else {
				require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor'. DIRECTORY_SEPARATOR . 'autoload.php';
				//throw new Exception('class ' . $className . ' not found');
				//exit();
			}
		}
	});

	register_shutdown_function(array('Core\Utils','shutDown'));
?>
