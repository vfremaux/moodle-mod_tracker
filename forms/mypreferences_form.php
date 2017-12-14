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
 * @package  mod_tracker
 * @category mod
 * @author   Clifford Tham, Valery Fremaux > 1.8
 *
 * Prints a form for user preferences
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

class my_preferences_form extends moodleform {

    public function definition() {

        $mform = $this->_form;

        $mform->addElement('hidden', 'what', 'saveprefs');
        $mform->setType('what', PARAM_TEXT);

        $mform->addElement('hidden', 'view', 'profile');
        $mform->setType('view', PARAM_TEXT);

        $mform->addElement('hidden', 'screen', 'mypreferences');
        $mform->setType('screen', PARAM_TEXT);

        $mform->addElement('header', 'hdr0', get_string('mypreferences', 'tracker'));

        $mform->addElement('html', get_string('prefsnote', 'tracker'));

        if ($this->_customdata['tracker']->enabledstates & ENABLED_OPEN) {
            $mform->addElement('selectyesno', 'open', get_string('unsetwhenopens', 'tracker'));
        }
        if ($this->_customdata['tracker']->enabledstates & ENABLED_RESOLVING) {
            $mform->addElement('selectyesno', 'resolving', get_string('unsetwhenworks', 'tracker'));
        }
        if ($this->_customdata['tracker']->enabledstates & ENABLED_WAITING) {
            $mform->addElement('selectyesno', 'waiting', get_string('unsetwhenwaits', 'tracker'));
        }
        if ($this->_customdata['tracker']->enabledstates & ENABLED_TESTING) {
            $mform->addElement('selectyesno', 'testing', get_string('unsetwhentesting', 'tracker'));
        }
        if ($this->_customdata['tracker']->enabledstates & ENABLED_PUBLISHED) {
            $mform->addElement('selectyesno', 'published', get_string('unsetwhenpublished', 'tracker'));
        }
        if ($this->_customdata['tracker']->enabledstates & ENABLED_RESOLVED) {
            $mform->addElement('selectyesno', 'resolved', get_string('unsetwhenresolves', 'tracker'));
        }
        if ($this->_customdata['tracker']->enabledstates & ENABLED_ABANDONNED) {
            $mform->addElement('selectyesno', 'abandonned', get_string('unsetwhenthrown', 'tracker'));
        }
        $mform->addElement('selectyesno', 'oncomment', get_string('unsetoncomment', 'tracker'));

        $this->add_action_buttons();
    }

    public function set_data($defaults) {
        global $USER;

        if (!empty($USER->trackerprefs)) {
            $defaults['open'] = @$USER->trackerprefs->eventmask & EVENT_OPEN;
            $defaults['resolving'] = @$USER->trackerprefs->eventmask & EVENT_RESOLVING;
            $defaults['waiting'] = @$USER->trackerprefs->eventmask & EVENT_WAITING;
            $defaults['testing'] = @$USER->trackerprefs->eventmask & EVENT_TESTING;
            $defaults['published'] = @$USER->trackerprefs->eventmask & EVENT_PUBLISHED;
            $defaults['resolved'] = @$USER->trackerprefs->eventmask & EVENT_RESOLVED;
            $defaults['abandonned'] = @$USER->trackerprefs->eventmask & EVENT_ABANDONNED;
            $defaults['oncomment'] = @$USER->trackerprefs->eventmask & ON_COMMENT;
        } else {
            $config = get_config('mod_tracker');
            $defaults['open'] = $config->useropendefault;
            $defaults['resolving'] = $config->userresolvingdefault;
            $defaults['waiting'] = $config->userwaitingdefault;
            $defaults['testing'] = $config->usertestingdefault;
            $defaults['published'] = $config->userpublisheddefault;
            $defaults['resolved'] = $config->userresolveddefault;
            $defaults['abandonned'] = $config->userabandonneddefault;
            $defaults['oncomment'] = $config->useroncommentdefault;
        }

        parent::set_data($defaults);
    }

}