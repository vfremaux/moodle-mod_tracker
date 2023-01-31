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
 * @package     mod_tracker
 * @category    mod
 * @author      Clifford Tham, Valery Fremaux > 1.8
 *
 * Summary for administrators
 */
define('AJAX_SCRIPT', 1);

require('../../../config.php');
require_once($CFG->dirroot.'/mod/tracker/locallib.php');
require_once($CFG->dirroot.'/mod/tracker/lib.php');

require_login();

$action = required_param('what', PARAM_TEXT);

if ($action == 'updatewatch') {
    require_sesskey();
    $cc = new StdClass();
    $cc->id = required_param('ccid', PARAM_INT);
    $event = required_param('event', PARAM_TEXT);
    $state = required_param('state', PARAM_INT);
    $cc->events = $DB->get_field('tracker_issuecc', 'events', array('id' => $cc->id));

    switch($event) {
        case 'open': {
            $eventcode = ENABLED_OPEN;
            break;
        }
        case 'resolving': {
            $eventcode = ENABLED_RESOLVING;
            break;
        }
        case 'waiting': {
            $eventcode = ENABLED_WAITING;
            break;
        }
        case 'resolved': {
            $eventcode = ENABLED_RESOLVED;
            break;
        }
        case 'abandonned': {
            $eventcode = ENABLED_ABANDONNED;
            break;
        }
        case 'transfered': {
            $eventcode = ENABLED_TRANSFERED;
            break;
        }
        case 'testing': {
            $eventcode = ENABLED_TESTING;
            break;
        }
        case 'published': {
            $eventcode = ENABLED_PUBLISHED;
            break;
        }
        case 'validated': {
            $eventcode = ENABLED_VALIDATED;
            break;
        }
    }
    if ($state) {
        $cc->events = $cc->events | $eventcode;
    } else {
        $cc->events = $cc->events & ~$eventcode;
    }

    $DB->update_record('tracker_issuecc', $cc);
    exit(0);
}
if ($action == 'getmodalform') {
    $args = new StdClass;
    $args->mode = required_param('mode', PARAM_TEXT);
    $args->id = required_param('id', PARAM_INT);
    $args->ctx = required_param('ctx', PARAM_INT);

    $context = $DB->get_record('context', array('id' => $args->ctx));
    $PAGE->set_context($context);

    $renderer = $PAGE->get_renderer('mod_tracker');
    $return = $renderer->list_edit_form($args);

    echo $return;
    exit(0);
}
if ($action == 'updatestatus') {
    $id = required_param('id', PARAM_INT);
    $status = required_param('status', PARAM_INT);

    $result = new StdClass;
    $issue = $DB->get_record('tracker_issue', array('id' => $id));
    if (!$issue) {
        $result->result = 'error';
    }

    $statuscodes = tracker_get_statuscodes();

    $result->result = 'success';
    $result->oldvalue = $issue->status;
    $result->oldlabel = get_string($statuscodes[$issue->status], 'tracker');
    $DB->set_field('tracker_issue', 'status', $status, array('id' => $id));
    $result->newvalue = $status;
    $result->newlabel = get_string($statuscodes[$status], 'tracker');

    echo json_encode($result);
    exit(0);
}
if ($action == 'updateassignedto') {
    $id = required_param('id', PARAM_INT);
    $assignedto = required_param('assignedto', PARAM_INT);

    $result = new StdClass;
    $issue = $DB->get_record('tracker_issue', array('id' => $id));
    if (!$issue) {
        $result->result = 'error';
    }

    $result->result = 'success';
    $result->oldvalue = $issue->assignedto;
    $DB->set_field('tracker_issue', 'assignedto', $assignedto, array('id' => $id));
    $result->newvalue = $assignedto;
    if ($assignedto) {
        $assigneduser = $DB->get_record('user', array('id' => $assignedto));
        $label = fullname($assigneduser);
    } else {
        $label = get_string('unassigned', 'tracker');
    }
    $result->newlabel = $label;

    echo json_encode($result);
    exit(0);
}

echo "Unknown action $action ";