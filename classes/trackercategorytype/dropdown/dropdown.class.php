<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
* @package tracker
* @author Clifford Tham
* @review Valery Fremaux / 1.8
* @date 02/12/2007
*
* A class implementing a dropdown element
*/

require_once $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/trackerelement.class.php';

class dropdownelement extends trackerelement {

    var $multiple;
    
    function dropdownelement(&$tracker, $id = null, $used = false) {
        parent::__construct($tracker, $id, $used);
        $this->setoptionsfromdb();
    }

    function view($issueid = 0) {

        $this->getvalue($issueid); // loads $this->value with current value for this issue
        if (isset($this->options)) {
            $optionstrs = array();
            foreach ($this->options as $option) {
                if ($this->value != null) {
                    if ($this->value == $option->name) {
                        $optionstrs[] = format_string($option->description);
                    }
                }
            }
            echo implode(', ', $optionstrs);
        }
    }

    function edit($issueid = 0) {

        $this->getvalue($issueid);

        $values = explode(',', $this->value); // whatever the form ... revert to an array.

        if (isset($this->options)) {
            foreach ($this->options as $optionobj) {
                $selectoptions[$optionobj->name] = $optionobj->description;
            }
            echo html_writer::select($selectoptions, $this->name, $values, array('' => 'choosedots'));
            echo html_writer::empty_tag('br');
        }
    }

    function add_form_element(&$form) {

        $form->addElement('header', "head{$this->name}", format_string($this->description));
        $form->setExpanded("head{$this->name}");
        if (isset($this->options)) {
            foreach ($this->options as $option) {
                $optionsmenu[$option->id] = format_string($option->description);
            }

            $form->addElement('select', $this->name, format_string($this->description), $optionsmenu);
        }
    }

    function set_data(&$defaults, $issueid = 0) {
        if ($issueid) {

            $elementname = $this->name;

            if (!empty($this->options)) {
                $values = $this->getvalue($issueid);
                if ($multiple && is_array($values)) {
                    foreach ($values as $v) {
                        if (array_key_exists($v, $this->options)) {
                            // Check option still exists.
                            $choice[] = $v;
                        }
                        if (!empty($choice)) {
                            $defaults->$elementname = $choice;
                        }
                    }
                } else {
                    $v = $values; // single value
                    if (array_key_exists($v, $this->options)) {
                        // Check option still exists.
                        $defaults->$elementname = $v;
                    }
                }
            }
        }
    }

    function formprocess(&$data) {
        global $DB;

        $sqlparams = array('elementid' => $this->id, 'trackerid' => $data->trackerid, 'issueid' => $data->issueid);
        if (!$attribute = $DB->get_record('tracker_issueattribute', $sqlparams)) {
            $attribute = new StdClass();
            $attribute->trackerid = $data->trackerid;
            $attribute->issueid = $data->issueid;
            $attribute->elementid = $this->id;
        }

        $elmname = $this->name;

        if (!$this->multiple) {
            $value = optional_param($elmname, '', PARAM_TEXT);
            $attribute->elementitemid = $value;
        } else {
            $valuearr = optional_param_array($elmname, '', PARAM_TEXT);
            if (is_array($data->$elmname)) {
                $attribute->elementitemid = implode(',', $valuearr);
            } else {
                $attribute->elementitemid = $data->$elmname;
            }
        }

        $attribute->timemodified = time();

        if (!isset($attribute->id)) {
            $DB->insert_record('tracker_issueattribute', $attribute);
        } else {
            $DB->update_record('tracker_issueattribute', $attribute);
        }
    }
}

