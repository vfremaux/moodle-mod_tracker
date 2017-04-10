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
 * A form for updating a watch record
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

class edit_watch_form extends moodleform {

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

        $mform->addElement('hidden', 'ccid');
        $mform->setType('ccid', PARAM_INT);

        $mform->addElement('header', 'hdr0', get_string('editwatch', 'tracker'));

        $html = "$tracker->ticketprefix.$form->issueid - $form->summary";
        $mform->addElement('static', 'issuename', get_string('issuename', 'tracker'), $html);

        $group = array();
        $group[] = $mform->createElement('radio', 'open', get_string('yes'), 1);
        $group[] = $mform->createElement('radio', 'open', get_string('no'), 0);
        $mform->addGroup($group, 'opengroup', get_string('setwhenopens', 'tracker'), array(), false);

        $group = array();
        $group[] = $mform->createElement('radio', 'resolving', get_string('yes'), 1);
        $group[] = $mform->createElement('radio', 'resolving', get_string('no'), 0);
        $mform->addGroup($group, 'resolvinggroup', get_string('setwhenworks', 'tracker'), array(), false);

        $group = array();
        $group[] = $mform->createElement('radio', 'waiting', get_string('yes'), 1);
        $group[] = $mform->createElement('radio', 'waiting', get_string('no'), 0);
        $mform->addGroup($group, 'waitinggroup', get_string('setwhenwaits', 'tracker'), array(), false);

        $group = array();
        $group[] = $mform->createElement('radio', 'resolved', get_string('yes'), 1);
        $group[] = $mform->createElement('radio', 'resolved', get_string('no'), 0);
        $mform->addGroup($group, 'resolvedgroup', get_string('setwhenresolves', 'tracker'), array(), false);

        $group = array();
        $group[] = $mform->createElement('radio', 'abandonned', get_string('yes'), 1);
        $group[] = $mform->createElement('radio', 'abandonned', get_string('no'), 0);
        $mform->addGroup($group, 'abandonnedgroup', get_string('setwhenthrown', 'tracker'), array(), false);

        $group = array();
        $group[] = $mform->createElement('radio', 'abandonned', get_string('yes'), 1);
        $group[] = $mform->createElement('radio', 'abandonned', get_string('no'), 0);
        $mform->addGroup($group, 'abandonnedgroup', get_string('setwhenthrown', 'tracker'), array(), false);

        $this->add_action_buttons();
    }
}