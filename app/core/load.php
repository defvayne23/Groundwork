<?php
class GW_Load {
	public function __construct() {
		$App = Application::getInstance(false);
		
		foreach($App as $key => $value) {
			$this->$key = $value;
		}
	}
	
	public function controller($sController) {
		if(!class_exists($sController)) {
			if(is_file($this->root.'app/controllers/'.$sController.'.php')) {
				include($this->root.'app/controllers/'.$sController.'.php');
				
				if(class_exists($sController.'_model')) {
					$oController = new $sController;
				} else {
					return false;
				}
			} else {
				return false;
			}
		} else {
			$oController = new $sController;
		}
		
		return $oController;
	}
	
	public function model($sModel) {
		if(!class_exists($sModel.'_model')) {
			if(is_file($this->root.'app/models/'.$sModel.'.php')) {
				include($this->root.'app/models/'.$sModel.'.php');
				
				if(class_exists($sModel.'_model')) {
					$sModel = $sModel.'_model';
					$oModel = new $sModel;
				} else {
					return false;
				}
			} else {
				return false;
			}
		} else {
			$sModel = $sModel.'_model';
			$oModel = new $sModel;
		}
		
		return $oModel;
	}
	
	public function view($sTemplate, $aAssign = array(), $sReturn = false) {
		if(is_file($this->root.'app/views/'.$sTemplate)) {
			foreach($aAssign as $sName => $sValue) {
				$$sName = $sValue;
			}
			
			$this->load = $this;
			
			ob_start();
			
			include($this->root.'app/views/'.$sTemplate);
			
			if($sReturn === true) {
				$sView = ob_get_contents();
				@ob_end_clean();
				return $sView;
			}
		} else {
			return false;
		}
	}
	
	public function helper($sHelper) {
		if(!function_exists($sHelper.'_helper')) {
			if(is_file($this->root.'app/helpers/'.$sHelper.'.php')) {
				include($this->root.'app/helpers/'.$sHelper.'.php');
				
				if(!function_exists($sHelper.'_helper')) {
					return false;
				}
			} else {
				return false;
			}
		}
		
		return true;
	}
}