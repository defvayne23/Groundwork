<?php
class Controller extends GW {
	public $model;
	
	public function __construct() {
		parent::__construct();
		
		## Auto-load model based on controller name
		if(!empty($sModel)) {
			$oModel = $this->load->model($sModel);
			
			if($oModel) {
				$this->model = $oModel;
			}
		}
	}
	
	public function redirect($sURL, $sResponse = 302) {
		header('Location: '.$sURL, true, $sResponse);
		exit;
	}
}