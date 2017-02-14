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

class captchaelement extends trackerelement {

    function __construct(&$tracker, $id = null, $used = false) {
        parent::__construct($tracker, $id, $used);
    }

    function has_mandatory_option() {
        return false;
    }

    function view($issueid = 0) {
        return '';
    }

    function edit($issueid = 0) {
        $this->getvalue($issueid);
        $str = '';
        $str .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'element'.$this->name, 'value' => format_string($this->value)));
        return $str;
    }

    function add_form_element(&$mform) {
        global $SESSION;

        $mform->addElement('header', "header{$this->name}", '');
        $mform->setExpanded("header{$this->name}");

        $group[] = &$mform->createElement('text', "element{$this->name}");
        $mform->setType("element{$this->name}", PARAM_TEXT);

        $SESSION->tracker[$this->tracker->id] = new StdClass;
        $captcharec = new StdClass;
        $captcharec->length = 6;
        $SESSION->tracker[$this->tracker->id]->captcha = $captcharec; // @todo parametrize this from global settings
        $cm = get_coursemodule_from_instance('tracker', $this->tracker->id);
        $generatorurl = new moodle_url('/mod/tracker/classes/trackercategorytype/captcha/print_captcha.php', array('id' => $cm->id));
        $group[] = &$mform->createElement('html', '<img src="'.$generatorurl.'">');

        $mform->addGroup($group, 'captchagroup', get_string('captcha', 'tracker'), false, array(''), false);
        $mform->setType("captchagroup[element{$this->name}]", PARAM_TEXT);
    }

    function set_data(&$defaults, $issueid = 0) {
        // Do not load anything
    }

    function validate($data, $files) {
        global $SESSION;

        $elmname = "element{$this->name}";
        if ($data->$elmname != $SESSION->tracker[$this->tracker->id]->captcha->checkchar) {
            return array($elmname, get_string('errorcaptcha', 'tracker'));
        }

        return null;
    }

    /**
     * updates or creates the element instance for this issue
     */
    function formprocess(&$data) {
        global $DB;

        // Do not store anything for captcha.
    }
}

