<?php
class app extends appController {
	function index() {
		$this->assign("sIntro", "Hello world!");
		
		$this->loadView("index.php");
	}
}