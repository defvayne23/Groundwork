<?php
class Application {
	// Methods
	public $load;
	public $db;
	
	// Variables
	public $root;
	public $controller;
	public $action;
	public $url;
	public $param;
	
	public static function getInstance($sLoad = true) {
		static $instance;
		$class = __CLASS__;
		
		if ( ! $instance instanceof $class) {
			$instance = new $class($sLoad);
		}
		
		return $instance;
	}
	
	public function __construct($sLoad = true) {
		global $sSiteRoot, $sController, $sAction, $aURL, $aURLVars, $oDatabase;
		
		// Methods
		if($sLoad === true) { // Keeps from creating an infinate loop when Load extends Application
			$this->load = new GW_Load($this->root);
		}
		$this->db = $oDatabase;
		
		// Variables
		$this->root = $sSiteRoot;
		$this->controller = $sController;
		$this->action = $sAction;
		$this->url = $aURL;
		$this->param = $aURLVars;
	}
	
	public function error($sError = "404") {
		switch($sError) {
			case "403":
				header('HTTP/1.1 403 Forbidden');
				$this->load->view("error/403.php");
				break;
			case "404":
				header("HTTP/1.1 404 Not Found");
				$this->load->view("error/404.php");
				break;
			case "500":
				header("HTTP/1.1 500 Internal Server Error");
				$this->load->view("error/500.php");
				break;
		}
		exit;
	}
}