<?php
class Controller {
	public $model;
	
	public function __construct($sModel = null) {
		$App = Application::getInstance(false);
		
		foreach($App as $key => $value) {
			$this->$key = $value;
		}
		
		## Auto-load model based on controller name
		if(!empty($sModel)) {
			$oModel = $this->load->model($sModel);
			
			if($oModel) {
				$this->model = $oModel;
			}
		}
	}
	
	public function redirect($sURL, $sResponse = 302) {
		header("Location: ".$sURL, true, $sResponse);
		exit;
	}
}