<?php
abstract class GW {
	private $_app;
	private $_class;
	
	public function __construct() {
		$App = Application::getInstance();
		
		if(in_array(get_called_class(), array('Database', 'Load', 'Error'))) {
			$sKey = strtolower(get_called_class());
			$App->$sKey = $this;
		}
		
		$this->_app = $App;
		$this->_class = get_called_class();
	}
	
	public function __call($sName, $aArguments) {
		if(is_callable(array($this->_app, $sName))) {
			return call_user_func_array(array($this->_app, $sName), $aArguments);
		} else {
			$aTrace = debug_backtrace();
			$this->error->trigger('Call to undefined method '.$this->_class.'::'.$sName, 'NOTICE', $aTrace[1]);
			
			return null;
		}
	}
	
	public function __set($sName, $sValue) {
		$this->_app->$sName = $sValue;
	}

	public function __get($sName) {
		if (isset($this->_app->$sName)) {
			return $this->_app->$sName;
		}
		
		$aTrace = debug_backtrace();
		$this->error->trigger('Call to undefined method '.$this->_class.'::'.$sName, 'NOTICE', $aTrace[0]);
		
		return null;
	}
	
	public function __isset($sName) {
		return isset($this->_app->$sName);
	}
	
	public function __unset($sName) {
		unset($this->_app->$sName);
	}
	
	public function reloadInstance() {
		$this->__construct();
	}
}