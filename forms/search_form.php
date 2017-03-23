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
 * @package mod-tracker
 * @category mod
 * @author Clifford Tham, Valery Fremaux > 1.8
 * @version moodle 2.x
 *
 * Prints prints user's profile and stats
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

class search_form extends moodleform {

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

        $mform->addElement('header', 'hdr0', get_string('searchbyid', 'tracker'));

        $mform->addElement('text', 'issuenumber', get_string('issuenumber', 'tracker'));
        $mform->setType('issuenumber', PARAM_TEXT);

        $mform->addElement('submit', 'search', get_string('search', 'tracker'));

        $mform->addElement('header', 'hdr1', get_string('searchcriteria', 'tracker'));

        $reporters = tracker_getreporters($tracker->id);

        $reportersmenu = array('' => get_string('any', 'tracker'));
        if ($reporters) {
            foreach ($reporters as $reporter) {
                $reportersmenu[$reporter->id] = fullname($reporter);
            }
            $mform->addElement('select', 'reportedby', get_string('reportedby', 'tracker'), $reportersmenu);
        } else {
            $mform->addElement('static', 'reportedby', get_string('noreporters', 'tracker'));
        }

        $mform->addElement('datetime', 'datereported', get_string('datereported', 'tracker'));

        $assignees = tracker_getassignees($tracker->id);

        $assigneesmenu = array('' => get_string('any', 'tracker'));
        if ($assignees) {
            foreach ($assignees as $assignee) {
                $assigneesmenu[$assignee->id] = fullname($assignee);
            }
            $mform->addElement('select', 'reportedby', get_string('reportedby', 'tracker'), $assigneessmenu);
        } else {
            $mform->addElement('static', 'reportedby', get_string('noassignees', 'tracker'));
        }

        $mform->addElement('text', 'summary', get_string('summary', 'tracker'));
        $mform->setType('summary', PARAM_TEXT);

        $mform->addElement('text', 'description', get_string('description', 'tracker'));
        $mform->setType('description', PARAM_TEXT);

        tracker_printelements($tracker, null, 'search');

        $mform->addElement('submit', 'searchcrit', get_string('search', 'tracker'));

        $mform->addElement('submit', 'savequery', get_string('savequery', 'tracker'));
    }
}
