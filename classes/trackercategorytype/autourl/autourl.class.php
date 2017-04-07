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
 * @package mod_tracker
 * @category mod
 * @author Valery Fremaux / 1.8
 *
 * A class implementing a hidden/labelled element that captures the referer url
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/tracker/classes/trackercategorytype/trackerelement.class.php');

class autourlelement extends trackerelement {

    public function __construct(&$tracker, $id = null, $used = false) {
        parent::__construct($tracker, $id, $used);
    }

    public function has_mandatory_option() {
        return false;
    }

    public function view($issueid = 0) {
        $this->get_value($issueid);
        return '<a href="'.$this->value.'">'.$this->value.'</a>';
    }

    public function add_form_element(&$mform) {

        $mform->addElement('hidden', "element{$this->name}");
        $mform->setDefault("element{$this->name}", $_SERVER['HTTP_REFERER']);

        $mform->addElement('text', "element{$this->name}shadow", get_string('autourl', 'tracker'));
        $mform->setType("element{$this->name}shadow", PARAM_URL);
        $mform->disabledIf("element{$this->name}shadow", "element{$this->name}");
        $mform->setDefault("element{$this->name}shadow", $_SERVER['HTTP_REFERER']);
        $mform->setType("element{$this->name}", PARAM_URL);
    }

    /**
     * updates or creates the element instance for this issue
     */
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
        $data->$elmname = required_param($elmname, PARAM_TEXT);
        $attribute->elementitemid = $data->$elmname; // In this case true value in element id.
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

    public function set_data(&$defaults, $issueid = 0) {
        $elementname = "element{$this->name}";
        if ($issueid) {
            $defaults->$elementname = $this->get_value($issueid);
        } else {
            $defaults->$elementname = $_SERVER['HTTP_REFERER'];
        }
    }
}
