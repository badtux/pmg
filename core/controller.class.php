<?php
namespace Core;

class Controller {

	public function __construct() {

		$this->__pmg_request = &$request;
		date_default_timezone_set('UTC');
/*
        if(is_callable(array($this,'pre_init'))) {
        	$this->pre_init();
        }

        try {
			$this->init();
        }
        catch (Exception $e) {
        	throw $e;
        }

		if(is_callable(array($this,'post_init'))) {
			$this->post_init();
		} */
	}

}
?>