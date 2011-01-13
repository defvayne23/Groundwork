<?php
class appController {
	private $viewData;
	
	public $root;
	public $controller;
	public $action;
	public $url;
	public $param;
	public $db;
	public $model;
	
	public function __construct($sModel = null) {
		global $sSiteRoot, $sController, $sAction, $aURL, $aURLVars, $oDatabase;
		
		$this->root = $sSiteRoot;
		$this->controller = $sController;
		$this->action = $sAction;
		$this->url = $aURL;
		$this->param = $aURLVars;
		$this->db = $oDatabase;
		
		## Auto-load model based on controller name
		if(!empty($sModel)) {
			$oModel = $this->loadModel($sModel);
			
			if($oModel) {
				$this->model = $oModel;
			}
		}
	}
	
	public function loadModel($sModel) {
		if(!class_exists($sModel."_model")) {
			if(is_file($this->root."app/models/".$sModel.".php")) {
				include($this->root."app/models/".$sModel.".php");
				
				if(class_exists($sModel."_model")) {
					$sModel = $sModel."_model";
					$oModel = new $sModel;
				}
			} else {
				return false;
			}
		} else {
			$sModel = $sModel."_model";
			$oModel = new $sModel;
		}
		
		return $oModel;
	}
	
	public function loadView($sTemplate) {
		if(is_file($this->root."app/views/".$sTemplate)) {
			foreach($this->viewData as $sName => $sValue) {
				$$sName = $sValue;
			}
			
			include($this->root."app/views/".$sTemplate);
		} else {
			return false;
		}
	}
	
	public function assign($sName, $sValue) {
		$this->viewData[$sName] = $sValue;
	}
}