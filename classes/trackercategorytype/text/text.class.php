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

defined('MOODLE_INTERNAL') || die();

/**
 * @package tracker
 * @author Clifford Tham
 * @review Valery Fremaux / 1.8
 * @date 17/12/2007
 *
 * A class implementing a textfield element
 */
require_once($CFG->dirroot.'/mod/tracker/classes/trackercategorytype/trackerelement.class.php');

class textelement extends trackerelement {

    function __construct(&$tracker, $id = null, $used = false) {
        parent::__construct($tracker, $id, $used);
    }

    function view($issueid = 0) {
        $this->getvalue($issueid);
        return format_text(format_string($this->value), $this->format);
    }

    function edit($issueid = 0) {
        $this->getvalue($issueid);
        echo html_writer::empty_tag('input', array('type' => 'text', 'name' => 'element'.$this->name, 'value' => format_string($this->value), 'size' => 80));
    }

    function add_form_element(&$mform) {
        $mform->addElement('header', "header{$this->name}", '');
        $mform->setExpanded("header{$this->name}");
        $mform->addElement('text', "element{$this->name}", format_string($this->description), array('size' => 80));
        $mform->setType("element{$this->name}", PARAM_TEXT);
        if (!empty($this->mandatory)) {
            $mform->addRule('element'.$this->name, null, 'required', null, 'client');
        }
    }

    function set_data(&$defaults, $issueid = 0) {
        if ($issueid) {
            $elementname = "element{$this->name}";
            $defaults->$elementname = $this->getvalue($issueid);
        } else {
            $defaults->$elementname = '';
        }
    }

    /**
     * updates or creates the element instance for this issue
     */
    function formprocess(&$data) {
        global $DB;

        if (!$attribute = $DB->get_record('tracker_issueattribute', array('elementid' => $this->id, 'trackerid' => $data->trackerid, 'issueid' => $data->issueid))) {
            $attribute = new StdClass();
            $attribute->trackerid = $data->trackerid;
            $attribute->issueid = $data->issueid;
            $attribute->elementid = $this->id;
        }

        $elmname = 'element'.$this->name;
        if ($this->private) {
            $data->$elmname = optional_param($elmname, '', PARAM_TEXT);
        } else {
            $data->$elmname = required_param($elmname, PARAM_TEXT);
        }
        $attribute->elementitemid = $data->$elmname; // in this case true value in element id
        $attribute->timemodified = time();

        if (!isset($attribute->id)) {
            $attribute->id = $DB->insert_record('tracker_issueattribute', $attribute);
            if (empty($attribute->id)) {
                print_error('erroraddissueattribute', 'tracker', '', 2);
            }
        } else {
            $DB->update_record('tracker_issueattribute', $attribute);
        }
    }
}

