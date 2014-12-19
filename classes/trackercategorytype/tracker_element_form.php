<?php

include_once $CFG->libdir.'/formslib.php';

class tracker_moodle_form extends moodle_form{
	
	function start_form(){
		$mform = $this->_form;
		$mform->addElement('text', 'name', get_string('name'), '');
		$mform->setType('name', PARAM_CLEANHTML);
	  	$mform->addRule('name', null, 'required', null, 'client');

		$mform->addElement('textarea', 'description', get_string('description'));
	}

	function end_form(){
		$mform = $this->_form;
		$mform->addElement('checkbox', 'shared', print_string('sharethiselement', 'tracker'), 1);
		$this->add_action_buttons();
	}

	function validation($data, $files){
		return;
	}
}