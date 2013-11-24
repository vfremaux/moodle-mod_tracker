<?php

/**
* @package tracker
* @author Clifford Tham
* @review Valery Fremaux / 1.8
* @date 02/12/2007
*
* A class implementing a textarea element and all its representations
*/

require_once $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/trackerelement.class.php';

class textareaelement extends trackerelement{

	function __construct(&$tracker, $id = null, $used = false){
		parent::__construct($tracker, $id, $used);
	}

	function view($issueid = 0){
		$this->getvalue($issueid);
		echo format_text(format_string($this->value), $this->format);
	}

	function edit($issueid = 0){
        $this->getvalue($issueid);
	    echo html_writer::start_tag('textarea', array('name' => 'element'.$this->name, 'cols' => 80, 'rows' => 15));
	    echo format_string($this->value);
	    echo html_writer::end_tag('textarea');
	}
	
	function viewsearch(){
	    echo "<input type=\"text\" name=\"element{$this->name}\" style=\"width:100%\" />";
	}

	function viewquery(){
	    echo "<input type=\"text\" name=\"element{$this->name}\" style=\"width:100%\" />";
	}

	function add_form_element(&$mform){
		$mform->addElement('header', "header{$this->name}", $this->description);
		$mform->addElement('textarea', "element{$this->name}", '', array('cols' => 60, 'rows' => 15));
		$mform->setType("element{$this->name}", PARAM_TEXT);
	}

	function set_data(&$defaults, $issueid = 0){
		if ($issueid){
			$elementname = "element{$this->name}";
			$defaults->$elementname = $this->getvalue($issueid);
		} else {
			$defaults->$elementname = '';
		}
	}
	
	function formprocess(&$data){
		global $DB;
		
		if (!$attribute = $DB->get_record('tracker_issueattribute', array('elementid' => $this->id, 'trackerid' => $data->trackerid, 'issueid' => $data->issueid))){
			$attribute = new StdClass();
			$attribute->trackerid = $data->trackerid;
			$attribute->issueid = $data->issueid;
			$attribute->elementid = $this->id;
		}
		
		$elmname = 'element'.$this->name;		
		$data->$elmname = required_param($elmname, PARAM_TEXT);
		$attribute->elementitemid = $data->$elmname; // in this case true value in element id
		$attribute->timemodified = time();

		if (!isset($attribute->id)){
			$attribute->id = $DB->insert_record('tracker_issueattribute', $attribute);
			if (empty($attribute->id)){
				print_error('erroraddissueattribute', 'tracker', '', 2);
			}
		} else {
			$DB->update_record('tracker_issueattribute', $attribute);
		}
	}
}

