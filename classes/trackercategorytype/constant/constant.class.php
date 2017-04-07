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
 * @author Valery Fremaux / 1.8
 *
 * A class implementing a constant element from an internal configuration value or
 * an instance setting value
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/tracker/classes/trackercategorytype/trackerelement.class.php');

class constantelement extends trackerelement {

    public function __construct(&$tracker, $id = null, $used = false) {
        parent::__construct($tracker, $id, $used);
    }

    public function has_mandatory_option() {
        return false;
    }

    public function view($issueid = 0) {
        $this->get_value($issueid);
        return $this->value;
    }

    /**
     * Constant source reference :
     * 1 => site shortname
     * 2 => site fullname
     * 3 => current idnumber
     * 4 => current courseidnumber
     * 5 => current courseshortname
     * 6 => current coursefullname
     */
    public function add_form_element(&$mform) {
        global $SITE, $COURSE;

        $mform->addElement('hidden', "element{$this->name}");
        $mform->setDefault("element{$this->name}", 'name');
        $mform->setType("element{$this->name}", PARAM_URL);

        switch ($this->paramint1) {
            case 1: {
                $constant = $SITE->shortname;
                break;
            }

            case 2: {
                $constant = $SITE->fullname;
                break;
            }

            case 3: {
                $cm = get_coursemodule_from_instance('tracker', $this->tracker->id);
                $constant = $cm->idnumber;
                break;
            }

            case 4: {
                $constant = $COURSE->idnumber;
                break;
            }

            case 5: {
                $constant = $COURSE->shortname;
                break;
            }

            case 6: {
                $constant = $COURSE->fullname;
                break;
            }

            default:
                $contant = '';
        }

        if ($this->active) {
            $mform->addElement('text', "element{$this->name}shadow", get_string('constant', 'tracker'));
            $mform->setType("element{$this->name}shadow", PARAM_URL);
            $mform->disabledIf("element{$this->name}shadow", "element{$this->name}");
            $mform->setDefault("element{$this->name}shadow", $constant);
        }
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
        if ($this->mandatory && !$this->private) {
            $data->$elmname = required_param($elmname, PARAM_TEXT);
        } else {
            $data->$elmname = optional_param($elmname, '', PARAM_TEXT);
        }
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
}

