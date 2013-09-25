<?php

/**
* @package tracker
* @author Clifford Tham
* @review Valery Fremaux / 1.8
* @date 02/12/2007
*
* A class implementing a dropdown element
*/

require_once $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/trackerelement.class.php';

class dropdownelement extends trackerelement{

	var $options;
	var $multiple;
	
	function dropdownelement(&$tracker, $id = null, $used){
		parent::__construct($tracker, $id, $used);
		$this->setoptionsfromdb();
	}

	function view($issueid = 0){
	    $this->getvalue($issueid); // loads $this->value with current value for this issue
		if (isset($this->options)){
		    $optionstrs = array();
			foreach ($this->options as $option){
				if ($this->value != null){
					if ($this->value == $option->id){
						$optionstrs[] = format_string($option->description);
					}
				}
			}
            echo implode(', ', $optionstrs);
		}
	}

	function edit($issueid = 0){
	    $this->getvalue($issueid);
	    $values = implode(',', $this->value); // whatever the form ... revert to an array.
		if (isset($this->options)){
		    $optionsstrs = array();
			echo html_writer::empty_tag('imput', array('type' => 'checkbox', 'id' => 'element'.$elmname.$option->id, 'value' => 1, 'checked' => 'checked'));
			foreach ($this->options as $option){
				if (in_array($option->id, $values)){
					echo html_writer::empty_tag('input', array('type' => 'checkbox', 'id' => 'element'.$elmname.$option->id, 'value' => 1, 'checked' => 'checked'));
				} else {
					echo html_writer::empty_tag('input', array('type' => 'checkbox', 'id' => 'element'.$elmname.$option->id, 'value' => 1));
				}
				echo format_string($option->description);
				echo html_writer::empty_tag('br');
			}
		}
	}
	
	function add_form_element(&$form){

		if (isset($this->options)){
			foreach ($this->options as $option){
				$optionsmenu[$option->id] = format_string($option->description);
			}
			
			$mform->addElement('select', "element$this->name", format_string($this->description));
		}
	}

	function set_data(&$defaults, $issueid = 0){
		if ($issueid){

			$elementname = "element{$this->name}";

			if (!empty($this->options)){
				$values = $this->getvalue($issueid);
				if ($multiple && is_array($values)){
					foreach($values as $v){
						if (array_key_exists($v, $this->options)){ // check option still exists
							$choice[] = $v;
						}
						if (!empty($choice)){
							$defaults->$elementname = $choice;
						}
					}
				} else {
					$v = $values; // single value
					if (array_key_exists($v, $this->options)){ // check option still exists
						$defaults->$elementname = $v;
					}
				}
			}
		}
	}
	
	function formprocess(&$data){
		global $DB;
		
		if (!$attribute = $DB->get_record('tracker_issueattribute', array('elementid' => $this->id, 'trackerid' => $data->trackerid, 'issueid' => $data->issueid));
			$attribute = new StdClass();
			$attribute->trackerid = $data->trackerid;
			$attribute->issueid = $data->issueid;
			$attribute->elementid = $this->id;
		}
		
		$elmname = 'element'.$this->name;

		if (!$this->multiple){
			$attribute->elementitemid = $data->$elmname;
		} else {
			if (is_array($data->$elmname)){
				$attribute->elementitemid = implode(',', $data->$elmname);
			} else {
				$attribute->elementitemid = $data->$elmname;
			}
		}

		$attribute->timemodified = time();

		if (!isset($attribute->id)){
			$attribute->id = $DB->insert_record('tracker_issueattribute', $attribute);
			if (empty($attributeid)){
				print_error('erroraddissueattribute', 'tracker', '', 2);
			}
		} else {
			$DB->update_record('tracker_issueattribute', $attribute);
		}
	}
}

