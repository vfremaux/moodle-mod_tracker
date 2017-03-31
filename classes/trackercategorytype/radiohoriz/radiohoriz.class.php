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
 *
 * A class implementing a radio button (exclusive choice) element horizontally displayed
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/tracker/classes/trackercategorytype/radio/radio.class.php');

class radiohorizelement extends radioelement {

    public function add_form_element(&$mform) {

        if (isset($this->options)) {
            $group = array();
            $mform->addElement('header', "head{$this->name}", format_string($this->description));
            $mform->setExpanded("head{$this->name}");
            foreach ($this->options as $option) {
                $label = format_string($option->description);
                $group[] = &$mform->createElement('radio', 'element'.$this->name, '', $label, $option->name);
                $mform->setType('element'.$this->name, PARAM_TEXT);
            }

            $mform->addGroup($group, 'element' . $this->name.'_set', '', false);

            if (!empty($this->mandatory)) {
                $mform->addRule('element'.$this->name, null, 'required', null, 'client');
            }
        }
    }

    public function options_sep() {
        return ', ';
    }

    public function form_process(&$data) {
        global $DB;

        $params = array('elementid' => $this->id, 'trackerid' => $data->trackerid, 'issueid' => $data->issueid);
        if (!$attribute = $DB->get_record('tracker_issueattribute', $params)) {
            $attribute = new StdClass();
            $attribute->trackerid = $data->trackerid;
            $attribute->issueid = $data->issueid;
            $attribute->elementid = $this->id;
        }

        $elmname = 'element'.$this->name;
        $data->$elmname = optional_param($elmname, '', PARAM_TEXT);
        $attribute->elementitemid = $data->$elmname; // In this case we have elementitem id or idlist.
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

    public function type_has_options() {
        return true;
    }
}
