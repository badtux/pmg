<?php
class Router {
	protected $module;
	protected $object;
	protected $action;
	private $passBack = null;
	protected $parameters;

	public static function init() {
		$router = null;
		if(isset($_SESSION['__pmg']) && isset($_SESSION['__pmg']['session'][__CLASS__])) {
			$router = unserialize($_SESSION['__pmg']['session'][__CLASS__]);
		}

		if($router instanceof Router) {
			return $router;
		}
		else {
			return new Router();
		}
	}

	private function __construct() {
		$this->loadRequest();
	}

	public function __wakeup() {
		$this->loadRequest();
	}

	public function __destruct() {
		$_SESSION['__pmg']['session'][__CLASS__] = serialize($this);
	}

	public function __sleep() {
		return array('passBack');
	}

	private function loadRequest() {
		if(!isset($_GET['route'])) { throw new Exception('found a screwed up htaccess'); exit(); }
		$route = explode('/', trim($_GET['route'], '/'));

		switch (count($route)) {
			case 1:
				if($route[0] == '') {
					$this->module = '';
				}
				else {
					if(is_file(app_core_path . $route[0] . DIRECTORY_SEPARATOR . 'controller.php')) {
						$this->module = $route[0];
					}
					else {
						$this->object = $route[0];
					}
				}
				$this->parameters = $this->loadParameters();
				break;

			case 2:
					if(is_file(app_core_path . $route[0] . DIRECTORY_SEPARATOR . 'controller.php')) {
						$this->module = $route[0];
					    $this->object = $route[1];
					}
					else {
					    $this->module = '';
					    $this->object = $route[1];
					    $this->action = $route[0];
					}
				    $this->parameters = $this->loadParameters();
				break;

			case 3:
				    $this->module = $route[0];
                    $this->object = $route[1];
                    $this->action = $route[2];
                    $this->parameters = $this->loadParameters();
                break;

			case 4:
				    $this->module = $route[0];
                    $this->object = $route[1];
                    $this->action = $route[2];
                    $this->parameters = $this->loadParameters($route[3]);
                break;
		}
	}

	private function loadParameters($paramStr = '') {
		if($paramStr != '') {
			$param = explode ( ':', $paramStr );
			$parameters = array ();
			foreach ($param as $part) {
				$paramValuePair = explode ('=', $part);
				if (sizeof($paramValuePair) > 1) {
					$parameters[trim($paramValuePair[0])] = trim ($paramValuePair[1]);
				}
			}
		}

		foreach ($_COOKIE as $key => $value) {
			$parameters[$key] = $value;
			if(isset($_REQUEST[$key])) { unset($_REQUEST[$key]);}
		}

		if (sizeof($_REQUEST) > 0) {
			foreach ($_REQUEST as $key => $value) {
				if(is_scalar($value)) {
					$parameters[trim($key)] = $value;
				}
				else if (is_array($value)) {
					$parameters[trim($key)] = $value;
				}
			}

			foreach ($_FILES as $key => $upload) {
				if(!is_array($upload)) {
					foreach ($upload as $attrib => $value) {
						$parameters[trim($key)][$attrib] = trim($value);
					}
				}
			}
		}

		return $parameters;
	}

	public function getRequest() {
		$controlFile = app_core_path . $this->module . DIRECTORY_SEPARATOR . 'controller.php';
		if(is_file($controlFile)) {
			require_once($controlFile);
		}
		else {
			header('Location: /');
			exit();
		}

		$request = new Request();
		$request->userPassBack($this->passBack);
		$request->m = $this->module;
		$request->o = $this->object;
		$request->a = $this->action;
		$request->p = $this->parameters;
		return $request;
	}
}
?>