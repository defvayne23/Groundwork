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
	
	public static function getInstance() {
		static $instance;
		
		$class = __CLASS__;
		
		if ( ! $instance instanceof $class) {
			$instance = new $class($sLoad);
		}
		
		return $instance;
	}
	
	public function __construct() {
		global $aConfig, $sSiteRoot, $sController, $sAction, $aURL, $aURLVars, $oDatabase;
		
		// Methods
		$this->db = $oDatabase;
		
		// Variables
		$this->config = $aConfig;
		$this->root = $sSiteRoot;
		$this->controller = $sController;
		$this->action = $sAction;
		$this->url = $aURL;
		$this->param = $aURLVars;
	}
}