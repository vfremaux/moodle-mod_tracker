<?php

/**
* @package tracker
* @author Clifford Tham
* @review Valery Fremaux / 1.8
* @date 02/12/2007
*
* A class implementing a checkbox element
*/

include_once $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/trackerelement.class.php';

class checkboxhorizelement extends trackerelement{
	
	function __construct(&$tracker, $id = null, $used = false){
		
		parent::__construct($tracker, $id, $used);		
		$this->setoptionsfromdb();
	}

	function edit($issueid = 0){
		global $OUTPUT;
		
	    $this->getvalue($issueid);
	    $values = explode(',', $this->value); // whatever the form ... revert to an array.
		if (isset($this->options)){
		    $optionsstrs = array();
			foreach ($this->options as $option){
				if (in_array($option->id, $values)){
					echo html_writer::empty_tag('input', array('type' => 'checkbox', 'name' => 'element'.$this->name.$option->id, 'value' => 1, 'checked' => 'checked'));
				} else {
					echo html_writer::empty_tag('input', array('type' => 'checkbox', 'name' => 'element'.$this->name.$option->id, 'value' => 1));
				}
				echo format_string($option->description);
				echo html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('spacer'), 'width' => 30, 'hight' => 1));
			}
		}
	}
		
	function view($issueid = 0){
	    $this->getvalue($issueid); // loads $this->value with current value for this issue
		if (!empty($this->value)){
			$values = explode(',',$this->value); 
			$choices = array();
			foreach ($values as $selected){
				$choices[] = format_string($this->options[$selected]->description);
			}					
			echo(implode(', ', $choices));
		}		
	}	

	function add_form_element(&$form){		
		if (isset($this->options)){
			$group = array();
			$form->addElement('header', "head{$this->name}", $this->description);
			foreach ($this->options as $option){
				$group[] = &$form->createElement('checkbox', "element{$this->name}{$option->id}", '', $option->description);
				$form->setType("element{$this->name}{$option->id}", PARAM_TEXT);
			}
			
			$form->addGroup($group, 'element' . $this->name.'_set');
		}
	}

	function set_data(&$defaults, $issueid = 0){
		if ($issueid){
			if (!empty($this->options)){
				$values = $this->getvalue($issueid);
				if (is_array($values)){
					foreach($values as $v){
						if (array_key_exists($v, $this->options)){ // check option still exists
							$elementname = "element{$this->name}{$option->id}";
							$defaults->$elementname = 1;
						}
					}
				} else {
					$v = $values; // single value
					if (array_key_exists($v, $this->options)){ // check option still exists
						$elementname = "element{$this->name}{$option->id}";
						$defaults->$elementname = 1;
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
		

		$elmvalues = array();
		if (!empty($this->options)){
			foreach($this->options as $optid => $opt){
				$elmname = 'element'.$this->name.$optid;
				$data->$elmname = optional_param($elmname, '', PARAM_TEXT);
				if (!empty($data->$elmname)){
					$elmvalues[] = $optid;
				}
			}
		}

		$attribute->elementitemid = implode(',', $elmvalues); // in this case we have elementitem id or idlist 
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

