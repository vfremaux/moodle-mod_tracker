<?php

/**
* @package tracker
* @author Clifford Tham
* @review Valery Fremaux / 1.8
* @date 02/12/2007
*
* A class implementing a radio button (exclusive choice) element horizontally displayed
*/

require_once $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/trackerelement.class.php';

class radiohorizelement extends trackerelement{

	function __construct(&$tracker, $id = null, $used = false){
		parent::__construct($tracker, $id, $used);
		$this->setoptionsfromdb();
	}
	
	function view($issueid = 0){
		$this->getvalue($issueid);

		$optbynames = array();
		foreach($this->options as $opt){
			$optbynames[$opt->name] = format_string($opt->description);
		}
		
		if (!empty($this->options) && !empty($this->value) && array_key_exists($this->value, $optbynames)){
			echo $optbynames[$this->value];
		}
	}

	function edit($issueid = 0){
		global $OUTPUT;
		
	    $this->getvalue($issueid);
		if (isset($this->options)){
			$optbynames = array();
			foreach($this->options as $opt){
				$optbynames[$opt->name] = format_string($opt->description);
			}

			foreach ($optbynames as $name => $option){
				if ($this->value == $name){
					echo html_writer::empty_tag('input', array('type' => 'radio', 'name' => 'element'.$this->name, 'value' => $name, 'checked' => 'checked'));
				} else {
					echo html_writer::empty_tag('input', array('type' => 'radio', 'name' => 'element'.$this->name, 'value' => $name));
				}
				echo format_string($option);
				echo html_writer::empty_tag('br');
			}
		}
	}

	function add_form_element(&$form){
		if (isset($this->options)){
			$group = array();
			$form->addElement('header', "head{$this->name}", $this->description);
			foreach ($this->options as $option){
				$group[] = &$form->createElement('radio', 'element'.$this->name, '', $option->description, $option->name);
				$form->setType('element'.$this->name, PARAM_TEXT);
			}
			
			$form->addGroup($group, 'element' . $this->name.'_set', '', false);
		}
	}

	function set_data(&$defaults, $issueid = 0){
		if ($issueid){
			if (!empty($this->options)){
				$elmvalues = $this->getvalue($issueid);
				$values = explode(',', $elmvalues);
				if (!empty($values)){
					foreach($values as $v){
						if (array_key_exists($v, $this->options)){ // check option still exists
							$elementname = "element{$this->name}{$option->id}";
							$defaults->$elementname = 1;
						}
					}
				}
			}
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
		$data->$elmname = optional_param($elmname, '', PARAM_TEXT);
		$attribute->elementitemid = $data->$elmname; // in this case we have elementitem id or idlist 
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
