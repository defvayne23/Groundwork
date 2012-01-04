<?php
class app extends Controller {
	public function index() {
		$this->load->view('index.php', array('sIntro' => 'Hello world!'), false);
	}
}