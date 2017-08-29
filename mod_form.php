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
 * This view allows checking deck states
 *
 * @package mod_tracker
 * @category mod
 * @author Valery Fremaux
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * overrides moodleform for test setup
 */
class mod_tracker_mod_form extends moodleform_mod {

    public function definition() {
        global $CFG, $DB;

        $mform    =& $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        $modeoptions['bugtracker'] = get_string('mode_bugtracker', 'tracker');
        $modeoptions['ticketting'] = get_string('mode_ticketting', 'tracker');
        $modeoptions['taskspread'] = get_string('mode_taskspread', 'tracker');
        $modeoptions['customized'] = get_string('mode_customized', 'tracker');
        $mform->addElement('select', 'supportmode', get_string('supportmode', 'tracker'), $modeoptions);
        $mform->addHelpButton('supportmode', 'supportmode', 'tracker');

        $mform->addElement('text', 'ticketprefix', get_string('ticketprefix', 'tracker'), array('size' => 5));
        $mform->setType('ticketprefix', PARAM_TEXT);
        $mform->setAdvanced('ticketprefix');

        $stateprofileopts = array(
            ENABLED_OPEN => get_string('open', 'tracker'),
            ENABLED_RESOLVING => get_string('resolving', 'tracker'),
            ENABLED_WAITING => get_string('waiting', 'tracker'),
            ENABLED_RESOLVED => get_string('resolved', 'tracker'),
            ENABLED_ABANDONNED => get_string('abandonned', 'tracker'),
            ENABLED_TESTING => get_string('testing', 'tracker'),
            ENABLED_PUBLISHED => get_string('published', 'tracker'),
            ENABLED_VALIDATED => get_string('validated', 'tracker'),
        );
        $select = &$mform->addElement('select', 'stateprofile', get_string('stateprofile', 'tracker'), $stateprofileopts);
        $mform->setType('stateprofile', PARAM_INT);
        $mform->disabledIf('stateprofile', 'supportmode', 'neq', 'customized');
        $select->setMultiple(true);
        $mform->setAdvanced('stateprofile');

        $attrs = array('cols' => 60, 'rows' => 10);
        $mform->addElement('textarea', 'thanksmessage', get_string('thanksmessage', 'tracker'), $attrs);
        $mform->disabledIf('thanksmessage', 'supportmode', 'neq', 'customized');
        $mform->setType('thanksmessage', PARAM_TEXT);
        $mform->setAdvanced('thanksmessage');

        $mform->addElement('checkbox', 'enablecomments', get_string('enablecomments', 'tracker'));
        $mform->addHelpButton('enablecomments', 'enablecomments', 'tracker');

        $mform->addElement('checkbox', 'allownotifications', get_string('notifications', 'tracker'));
        $mform->addHelpButton('allownotifications', 'notifications', 'tracker');

        $mform->addElement('checkbox', 'strictworkflow', get_string('strictworkflow', 'tracker'));
        $mform->addHelpButton('strictworkflow', 'strictworkflow', 'tracker');

        if (isset($this->_cm->id)) {
            $context = context_module::instance($this->_cm->id);
            $fields = 'u.id,'.get_all_user_name_fields(true, 'u');
            $order = 'lastname, firstname';
            if ($assignableusers = get_users_by_capability($context, 'mod/tracker:resolve', $fields, $order)) {
                $useropts[0] = get_string('none');
                foreach ($assignableusers as $assignable) {
                    $useropts[$assignable->id] = fullname($assignable);
                }
                $mform->addElement('select', 'defaultassignee', get_string('defaultassignee', 'tracker'), $useropts);
                $mform->addHelpButton('defaultassignee', 'defaultassignee', 'tracker');
                $mform->disabledIf('defaultassignee', 'supportmode', 'eq', 'taskspread');
                $mform->setAdvanced('defaultassignee');
            } else {
                $mform->addElement('hidden', 'defaultassignee', 0);
            }
        } else {
            $mform->addElement('hidden', 'defaultassignee', 0);
        }
        $mform->setType('defaultassignee', PARAM_INT);

        if ($subtrackers = $DB->get_records_select('tracker', " id != 0 " )) {
            $trackermoduleid = $DB->get_field('modules', 'id', array('name' => 'tracker'));
            $subtrackersopts = array();
            foreach ($subtrackers as $st) {
                if ($st->id == @$this->current->id) {
                    continue;
                }
                if ($targetcm = $DB->get_record('course_modules', array('instance' => $st->id, 'module' => $trackermoduleid))) {
                    $targetcontext = context_module::instance($targetcm->id);
                    $caps = array('mod/tracker:manage',
                                  'mod/tracker:develop',
                                  'mod/tracker:resolve');
                    if (has_any_capability($caps, $targetcontext)) {
                        $trackercourseshort = $DB->get_field('course', 'shortname', array('id' => $st->course));
                        $subtrackersopts[$st->id] = $trackercourseshort.' - '.$st->name;
                    }
                }
            }
            if (!empty($subtrackersopts)) {
                $select = &$mform->addElement('select', 'subtrackers', get_string('subtrackers', 'tracker'), $subtrackersopts);
                $mform->setType('subtrackers', PARAM_INT);
                $mform->setAdvanced('subtrackers');
                $select->setMultiple(true);
            }
        }

        if ($CFG->mnet_dispatcher_mode == 'strict') {
            $mform->addElement('checkbox', 'networkable', get_string('networkable', 'tracker'), get_string('yes'), 0);
            $mform->addHelpButton('networkable', 'networkable', 'tracker');
            $mform->setAdvanced('networkable');
        }

        $mform->addElement('text', 'failovertrackerurl', get_string('failovertrackerurl', 'tracker'), array('size' => 80));
        $mform->setType('failovertrackerurl', PARAM_URL);
        $mform->setAdvanced('failovertrackerurl');

        $options['idnumber'] = true;
        $options['groups'] = false;
        $options['groupings'] = false;
        $options['gradecat'] = false;
        $this->standard_coursemodule_elements($options);
        $this->add_action_buttons();
    }

    public function set_data($defaults) {

        if (!property_exists($defaults, 'enabledstates')) {
            $defaults->stateprofile = array();

            $defaults->stateprofile[] = ENABLED_OPEN; // State when opened by the assigned.
            $defaults->stateprofile[] = ENABLED_RESOLVING; // State when asigned tells he starts processing.
            $defaults->stateprofile[] = ENABLED_RESOLVED; // State when issue has an identified solution provided by assignee.
            $defaults->stateprofile[] = ENABLED_ABANDONNED; // State when issue is no more relevant by external cause.
        } else {
            $defaults->stateprofile = array();
            if ($defaults->enabledstates & ENABLED_OPEN) {
                $defaults->stateprofile[] = ENABLED_OPEN;
            }
            if ($defaults->enabledstates & ENABLED_RESOLVING) {
                $defaults->stateprofile[] = ENABLED_RESOLVING;
            }
            if ($defaults->enabledstates & ENABLED_WAITING) {
                $defaults->stateprofile[] = ENABLED_WAITING;
            }
            if ($defaults->enabledstates & ENABLED_RESOLVED) {
                $defaults->stateprofile[] = ENABLED_RESOLVED;
            }
            if ($defaults->enabledstates & ENABLED_ABANDONNED) {
                $defaults->stateprofile[] = ENABLED_ABANDONNED;
            }
            if ($defaults->enabledstates & ENABLED_TESTING) {
                $defaults->stateprofile[] = ENABLED_TESTING;
            }
            if ($defaults->enabledstates & ENABLED_PUBLISHED) {
                $defaults->stateprofile[] = ENABLED_PUBLISHED;
            }
            if ($defaults->enabledstates & ENABLED_VALIDATED) {
                $defaults->stateprofile[] = ENABLED_VALIDATED;
            }
        }

        parent::set_data($defaults);

    }

    public function definition_after_data() {
        $mform =& $this->_form;
    }

    public function validation($data, $files = null) {
        $errors = array();
        return $errors;
    }

}
