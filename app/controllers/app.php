<?php
class app extends Controller {
	function index() {
		$this->assign("sIntro", "Hello world!");
		
		$this->loadView("index.php");
	}
}