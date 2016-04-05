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

include_once $CFG->libdir.'/formslib.php';

abstract class tracker_moodle_form extends moodleform {

    function start_form() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'type');
        $mform->setType('type', PARAM_TEXT);

        $mform->addElement('hidden', 'elementid');
        $mform->setType('elementid', PARAM_INT);

        $mform->addElement('text', 'name', get_string('name'), '');
        $mform->setType('name', PARAM_ALPHANUM);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('textarea', 'description', get_string('description'));
    }

    function end_form() {

        $mform = $this->_form;
        $mform->addElement('checkbox', 'shared', get_string('sharethiselement', 'tracker'));

        $this->add_action_buttons();
    }

    function validation($data, $files) {
        return;
    }
}