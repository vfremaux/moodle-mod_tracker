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
 * Controller for all "profile" related views
 *
 * @usecase savequery (form)
 * @usecase dosavequery
 * @usecase viewquery
 * @usecase editquery (form)
 * @usecase updatequery
 * @usecase deletequery
 * @usecase register
 * @usecase unregister
 * @usecase editwatch (form)
 * @usecase updatewatch
 * @usecase saveprefs
 */

defined('MOODLE_INTERNAL') || die();

if ($action == 'savequery') {

    // Ask for a new search query **************************************************************.
    // Collects name and description on the way.

    $fields = tracker_extractsearchparametersfrompost();
    $form->fields = serialize($fields); // Serialize for passthru
    $form->action = 'dosaveasquery';
    $form->description = tracker_printsearchfields($fields);
    include $CFG->dirroot.'/mod/tracker/views/addaquery.html';
    return -1;

} else if ($action == 'dosaveasquery') {

    // Saves a new search query ****************************************************************.

    $query->format = required_param('format', PARAM_INT);
    $query->name = required_param('name', PARAM_TEXT);
    $query->description = str_replace("'", "''", required_param('description', PARAM_CLEANHTML));

    if (empty($query->name)) {
        $error->message = get_string('namecannotbeblank', 'tracker');
        $error->on = 'name';
        $errors[] = $error;
        $form->description = stripslashes($query->description);
        include($CFG->dirroot.'/mod/tracker/views/addaquery.html');
        return -1;
    }

    $fields = unserialize(stripslashes(required_param('fields', PARAM_RAW)));
    $query->trackerid = $tracker->id;
    if (! tracker_savesearchparameterstodb($query, $fields)) {
        print_error('errorunabletosavequery', 'tracker',  $url.'&amp;view=profile');
    }

} else if ($action == 'viewquery') {

    // Ask for viewing a personal search query *****************************************************.

    $fields = tracker_extractsearchparametersfromdb();
    include($CFG->dirroot.'/mod/tracker/views/editquery.html');

} else if ($action == 'editquery') {

    // Ask for editing a personal search query *****************************************************.

    $form->queryid = required_param('queryid', PARAM_INT);
    $query = $DB->get_record('tracker_query', array('id' => $form->queryid));
    $fields = tracker_extractsearchparametersfromdb($form->queryid);
    $form->name = $query->name;
    $form->checkdate = (empty($fields['datereported'])) ? false : true ;
    if (empty($fields['reportedby'])) {
        $fields['reportedby'] = array();
    }
    if (empty($fields['summary'])) {
        $fields['summary'] = array();
    }
    if (empty($fields['description'])) {
        $fields['description'] = array();
    }
    if (empty($fields['id'])) {
        $fields['id'] = array();
    }
    include($CFG->dirroot.'/mod/tracker/views/editquery.html');
    return -1;

} else if ($action == 'updatequery') {

    // Updates a personal search query ****************************************************************.

    $query->id = required_param('queryid', PARAM_INT);
    $fields = tracker_extractsearchparametersfrompost();
    $query->trackerid = $tracker->id;
    if (! tracker_savesearchparameterstodb($query, $fields)) {
        print_error('errorunabletosavequeryid', 'tracker',  $url.'&amp;view=profile', $query->id);
    }
} else if ($action == 'deletequery') {

    // Deletes a personal search query **************************************************************.

    $queryid = optional_param('queryid', '', PARAM_INT);
    if (! $DB->delete_records ('tracker_query', 'id', $queryid, 'trackerid', $tracker->id, 'userid', $USER->id)) {
        print_error('errorcannotdeletequeryid', 'tracker',  $url.'&amp;view=profile', $queryid);
    }
} else if ($action == 'register') {

    // Register to an issue ************************************************************************.

    $issueid = optional_param('issueid', '', PARAM_INT);
    $params = array('trackerid' => $tracker->id, 'issueid' => $issueid, 'userid' => $USER->id);
    if (!$DB->get_record('tracker_issuecc', $params)) {
        $cc->trackerid = $tracker->id;
        $cc->issueid = $issueid;
        $cc->userid = $USER->id;
        $cc->events = (isset($USER->trackerprefs->eventmask)) ? $USER->trackerprefs->eventmask : ALL_EVENTS ;
        $DB->insert_record('tracker_issuecc', $cc);
    }

} else if ($action == 'unregister') {

    // Unregister a watch on an issue *************************************************************.

    $issueid = required_param('issueid', PARAM_INT);
    $ccid = required_param('ccid', PARAM_INT);
    if (!$DB->delete_records('tracker_issuecc', array('trackerid' => $tracker->id, 'issueid' => $issueid, 'userid' => $ccid))) {
        $e->issue = $tracker->ticketprefix.$issueid;
        $e->userid = $ccid;
        print_error('errorcannotdeletecarboncopyforuser', 'tracker', $e);
    }
} else if ($action == 'unregisterall') {

    // Unregister all my watches ******************************************************************.

    $userid = required_param('userid', PARAM_INT);
    if (! $DB->delete_records ('tracker_issuecc', array('trackerid' => $tracker->id, 'userid' => $userid))) {
        print_error('errorcannotdeletecarboncopies', 'tracker', $userid);
    }

} else if ($action == 'editwatch') {

    // Ask for editing the watchers configuration ************************************************.

    $ccid = optional_param('ccid', '', PARAM_INT);
    if (!$form = $DB->get_record('tracker_issuecc', array('id' => $ccid))) {
        print_error('errorcannoteditwatch', 'tracker');
    }
    $issue = $DB->get_record('tracker_issue', array('id' => $form->issueid));
    $form->summary = $issue->summary;

    include("views/editwatch.html");
    return -1;

} else if ($action == 'updatewatch') {

    // Update a watchers config for an issue *****************************************************.

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

    if (!$DB->update_record('tracker_issuecc', $cc)) {
        print_error('errorcannotupdatewatcher', 'tracker', $url.'&amp;view=profile');
    }
} else if ($action == 'saveprefs') {

    // Saves the user's preferences *************************************************************.

    $open = optional_param('open', 1, PARAM_INT);
    $resolving = optional_param('resolving', 1, PARAM_INT);
    $waiting = optional_param('waiting', 1, PARAM_INT);
    $testing = optional_param('testing', 1, PARAM_INT);
    $published = optional_param('published', 1, PARAM_INT);
    $resolved = optional_param('resolved', 1, PARAM_INT);
    $abandonned = optional_param('abandonned', 1, PARAM_INT);
    $oncomment = optional_param('oncomment', 1, PARAM_INT);
    $pref = new StdClass();
    $pref->trackerid = $tracker->id;
    $pref->userid = $USER->id;
    $pref->name = 'eventmask';
    $pref->value = $open * EVENT_OPEN + $resolving * EVENT_RESOLVING + $waiting * EVENT_WAITING + $resolved * EVENT_RESOLVED;
    $pref->value += $abandonned * EVENT_ABANDONNED + $oncomment * ON_COMMENT + $testing * EVENT_TESTING;
    $pref->value += $published * EVENT_PUBLISHED;
    $params = array('trackerid' => $tracker->id, 'userid' => $USER->id, 'name' => 'eventmask');
    if (!$oldpref = $DB->get_record('tracker_preferences', $params)) {
        if (!$DB->insert_record('tracker_preferences', $pref)) {
            print_error('errorcannotsaveprefs', 'tracker', $url.'&amp;view=profile');
        }
    } else {
        $pref->id = $oldpref->id;
        if (!$DB->update_record('tracker_preferences', $pref)) {
            print_error('errorcannotupdateprefs', 'tracker', $url.'&amp;view=profile');
        }
    }
}
