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

class add_query_form extends moodleform {

    public function definition() {

        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'what');
        $mform->setType('what', PARAM_TEXT);

        $mform->addElement('hidden', 'view');
        $mform->setType('view', PARAM_TEXT);

        $mform->addElement('hidden', 'screen');
        $mform->setType('screen', PARAM_TEXT);

        $mform->addElement('hidden', 'fields');
        $mform->setType('fields', PARAM_TEXT);

        $mform->addElement('header', 'hdr0', get_string($this->_customdata['action'].'aquerytomemo', 'tracker'));

        $mform->addElement('text', 'name', get_string('name'));
        $mform->setType('name', PARAM_CLEANHTML);

        $mform->addElement('textarea', 'description', get_string('description'));
        $mform->setType('description', PARAM_CLEANHTML);

        if ($this->_customdata['action']) {
            $reporters = tracker_getreporters($tracker->id);
            $reportersmenu = array();
            foreach ($reporters as $reporter) {
                $reportersmenu[$reporter->id] = fullname($reporter);
            }

            $mform->addElement('select', 'reportedby', get_string('reportedby', 'tracker'), $this->_customdata['reporters']);

            $mform->addElement('datetime', 'datereported', get_string('datereported', 'tracker'));

            $mform->addElement('text', 'summary', get_string('summary', 'tracker'));

            $mform->addElement('text', 'description', get_string('description'));

            tracker_printelements($this->_customdata['tracker'], $this->customdata['fields'], 'query');
        }

        $this->add_action_buttons();
    }

    public function validation($data, $files = null) {
        $errors = parent::validation($data, $files);

        return $errors;
    }
}