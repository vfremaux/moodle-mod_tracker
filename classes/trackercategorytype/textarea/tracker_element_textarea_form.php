<?php

require_once '../tracker_element_form.php';

class tracker_element_textarea_form extends tracker_moodle_form{
	
	function definition(){		
		$this->start_form();
		$this->end_form();
	}

	function validation($data){
		return parent::validation($data);
	}	
}