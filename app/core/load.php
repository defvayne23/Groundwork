<?php
class Load extends GW {
	public function controller($sController) {
		if(!class_exists($sController)) {
			if(is_file($this->root.'app/controllers/'.$sController.'.php')) {
				include($this->root.'app/controllers/'.$sController.'.php');
				
				if(class_exists($sController.'_model')) {
					$oController = new $sController;
				} else {
					$aTrace = debug_backtrace();
					$this->error->trigger('Could not load controller \''.$sController.'\'. Failed to find class.', 'ERROR', $aTrace[0]);
				}
			} else {	
					$aTrace = debug_backtrace();
					$this->error->trigger('Could not load controller \''.$sController.'\'. File not found. ('.$this->root.'app/controllers/'.$sController.'.php)', 'ERROR', $aTrace[0]);
			}
		} else {
			$oController = new $sController;
		}
		
		return $oController;
	}
	
	public function model($sModel, $sName = null) {
		if(!class_exists($sModel.'_model')) {
			if(is_file($this->root.'app/models/'.$sModel.'.php')) {
				include($this->root.'app/models/'.$sModel.'.php');
				
				if(class_exists($sModel.'_model')) {
					$sModel = $sModel.'_model';
					$oModel = new $sModel;
				} else {
					$aTrace = debug_backtrace();
					$this->error->trigger('Could not load model \''.$sModel.'\'. Failed to find class.', 'ERROR', $aTrace[0]);
				}
			} else {	
				$aTrace = debug_backtrace();
				$this->error->trigger('Could not load model \''.$sModel.'\'. File not found. ('.$this->root.'app/models/'.$sModel.'.php)', 'ERROR', $aTrace[0]);
			}
		} else {
			$sModel = $sModel.'_model';
			$oModel = new $sModel;
		}
		
		if(!empty($sName)) {
			$this->$sName = $oModel;
		} else {
			return $oModel;
		}
	}
	
	public function view($sTemplate, $aAssign = array(), $sReturn = false) {
		if(is_file($this->root.'app/views/'.$sTemplate)) {
			foreach($aAssign as $sName => $sValue) {
				$$sName = $sValue;
			}
			
			ob_start();
			
			include($this->root.'app/views/'.$sTemplate);
			
			if($sReturn == true) {
				$sView = ob_get_contents();
				ob_end_clean();
				return $sView;
			}
		} else {
			$aTrace = debug_backtrace();
			$this->error->trigger('Could not load view \''.$sTemplate.'\'. File not found. ('.$this->root.'app/views/'.$sTemplate.'.php)', 'ERROR', $aTrace[0]);
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