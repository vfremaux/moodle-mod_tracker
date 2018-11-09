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
        case 'open' : $eventcode = ENABLED_OPEN; break;
        case 'resolving' : $eventcode = ENABLED_RESOLVING; break;
        case 'waiting' : $eventcode = ENABLED_WAITING; break;
        case 'resolved' : $eventcode = ENABLED_RESOLVED; break;
        case 'abandonned' : $eventcode = ENABLED_ABANDONNED; break;
        case 'transfered' : $eventcode = ENABLED_TRANSFERED; break;
        case 'testing' : $eventcode = ENABLED_TESTING; break;
        case 'published' : $eventcode = ENABLED_PUBLISHED; break;
        case 'validated' : $eventcode = ENABLED_VALIDATED; break;
    }
    if ($state) {
        $cc->events = $cc->events | $eventcode;
    } else {
        $cc->events = $cc->events & ~$eventcode;
    }

    $DB->update_record('tracker_issuecc', $cc);
}
