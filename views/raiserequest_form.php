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
* @package      mod_tracker
* @category     mod
* @author       Clifford Tham, Valery Fremaux > 1.8
*
* From for adding a comment
*/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

class raise_request_form extends moodleform {

    public function definition() {

        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'what');
        $mform->setType('what', PARAM_TEXT);

        $mform->addElement('hidden', 'issueid');
        $mform->setType('issueid', PARAM_INT);

        $mform->addElement('header', 'hdr0', get_string('raiserequesttitle', 'tracker'));

        $mform->addElement('textarea', 'reason', get_string('reason', 'tracker'));
        $mform->setType('reason', PARAM_TEXT);

        $mform->addElement('checkbox', 'emergency', get_string('urgentquery', 'tracker'));

        $this->add_action_buttons();
    }
}