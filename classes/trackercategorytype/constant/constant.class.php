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
 * @author Valery Fremaux / 1.8
 * @date 06/08/2015
 *
 * A class implementing a constant element from an internal configuration value or
 * an instance setting value
 */
require_once($CFG->dirroot.'/mod/tracker/classes/trackercategorytype/trackerelement.class.php');

class constantelement extends trackerelement {

    function __construct(&$tracker, $id = null, $used = false) {
        parent::__construct($tracker, $id, $used);
    }

    function has_mandatory_option() {
        return false;
    }

    function view($issueid = 0) {
        $this->getvalue($issueid);
        return $this->value;
    }

    function edit($issueid = 0) {
        $this->getvalue($issueid);
        $str = '';
        $str .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'element'.$this->name, 'value' => format_string($this->value)));
        $str .= html_writer::empty_tag('input', array('type' => 'text', 'name' => 'element'.$this->name.'_disabled', 'value' => format_string($this->value), 'disabled' => 'disabled', 'size' => 120));
        return $str;
    }

    function add_form_element(&$mform) {
        $mform->addElement('header', "header{$this->name}", '');
        $mform->setExpanded("header{$this->name}");

        $mform->addElement('hidden', "element{$this->name}");
        $mform->setDefault("element{$this->name}", 'name');
        $mform->setType("element{$this->name}", PARAM_URL);

        $mform->addElement('text', "element{$this->name}shadow", get_string('autourl', 'tracker'));
        $mform->setType("element{$this->name}shadow", PARAM_URL);
        $mform->disabledIf("element{$this->name}shadow", "element{$this->name}");
        $mform->setDefault("element{$this->name}shadow", $_SERVER['HTTP_REFERER']);
    }

    function set_data(&$defaults, $issueid = 0) {
        if ($issueid) {
            $elementname = "element{$this->name}";
            $defaults->$elementname = $this->getvalue($issueid);
        } else {
            $defaults->$elementname = $_SERVER['HTTP_REFERER'];
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
        $data->$elmname = required_param($elmname, PARAM_TEXT);
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

