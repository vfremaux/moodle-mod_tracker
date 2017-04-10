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
defined('AJAX_SCRIPT', 1);

require('../../../config.php');
require_once($CFG->dirroot.'/mod/tracker/locallib.php');

require_login();

$action = required_param('what', PARAM_TEXT);

if ($action == 'updatewatch') {
    require_sesskey();
    $cc = new StdClass();
    $cc->id = required_param('ccid', PARAM_INT);
    $open = optional_param('open', '', PARAM_INT);
    $resolving = optional_param('resolving', '', PARAM_INT);
    $waiting = optional_param('waiting', '', PARAM_INT);
    $testing = optional_param('testing', '', PARAM_INT);
    $published = optional_param('published', '', PARAM_INT);
    $resolved = optional_param('resolved', '', PARAM_INT);
    $abandonned = optional_param('abandonned', '', PARAM_INT);
    $oncomment = optional_param('oncomment', '', PARAM_INT);
    $cc->events = $DB->get_field('tracker_issuecc', 'events', array('id' => $cc->id));
    if (is_numeric($open)) {
        $cc->events = ($open === 1) ? $cc->events | EVENT_OPEN : $cc->events & ~EVENT_OPEN;
    }
    if (is_numeric($resolving)) {
        $cc->events = ($resolving === 1) ? $cc->events | EVENT_RESOLVING : $cc->events & ~EVENT_RESOLVING;
    }
    if (is_numeric($waiting)) {
        $cc->events = ($waiting === 1) ? $cc->events | EVENT_WAITING : $cc->events & ~EVENT_WAITING;
    }
    if (is_numeric($testing)) {
        $cc->events = ($testing === 1) ? $cc->events | EVENT_TESTING : $cc->events & ~EVENT_TESTING;
    }
    if (is_numeric($published)) {
        $cc->events = ($published === 1) ? $cc->events | EVENT_PUBLISHED : $cc->events & ~EVENT_PUBLISHED;
    }
    if (is_numeric($resolved)) {
        $cc->events = ($resolved === 1) ? $cc->events | EVENT_RESOLVED : $cc->events & ~EVENT_RESOLVED;
    }
    if (is_numeric($abandonned)) {
        $cc->events = ($abandonned === 1) ? $cc->events | EVENT_ABANDONNED : $cc->events & ~EVENT_ABANDONNED;
    }
    if (is_numeric($oncomment)) {
        $cc->events = ($oncomment === 1) ? $cc->events | ON_COMMENT : $cc->events & ~ON_COMMENT;
    }

    $DB->update_record('tracker_issuecc', $cc);
}
