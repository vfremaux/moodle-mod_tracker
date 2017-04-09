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
 * @package mod_tracker
 * @category mod
 * @author Clifford Tham, Valery Fremaux > 1.8
 * @date 02/12/2007
 *
 * Prints a form for user preferences
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/tracker/forms/mypreferences_form.php');

tracker_loadpreferences($tracker->id, $USER->id);

$form = new my_preferences_form(new moodle_url('/mod/tracker/view.php', array('id' => $cm->id)), array('tracker' => $tracker));

if ($data = $form->get_data()) {

    // Saves the user's preferences ****************************************************************.

    $open = $data->open;
    $resolving = $data->resolving;
    $waiting = $data->waiting;
    $testing = $data->testing;
    $published = $data->published;
    $resolved = $data->resolved;
    $abandonned = $data->abandonned;
    $oncomment = $data->oncomment;
    $pref = new StdClass();
    $pref->trackerid = $tracker->id;
    $pref->userid = $USER->id;
    $pref->name = 'eventmask';
    $pref->value = $open * EVENT_OPEN + $resolving * EVENT_RESOLVING + $waiting * EVENT_WAITING + $resolved * EVENT_RESOLVED;
    $pref->value += $abandonned * EVENT_ABANDONNED + $oncomment * ON_COMMENT + $testing * EVENT_TESTING;
    $pref->value += $published * EVENT_PUBLISHED;
    $params = array('trackerid' => $tracker->id, 'userid' => $USER->id, 'name' => 'eventmask');
    if (!$oldpref = $DB->get_record('tracker_preferences', $params)) {
        $DB->insert_record('tracker_preferences', $pref);
    } else {
        $pref->id = $oldpref->id;
        $DB->update_record('tracker_preferences', $pref);
    }
}

// Start printing screen.

echo $output;
echo $OUTPUT->heading(get_string('mypreferences', 'tracker'));
echo $OUTPUT->box_start('generalbox', 'tracker-preferences-form');

$form->display();

echo $OUTPUT->box_end();