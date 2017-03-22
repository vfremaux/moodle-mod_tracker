<?php

/**
* @package mod-tracker
* @category mod
* @author Clifford Tham, Valery Fremaux > 1.8
* @date 02/12/2007
*
* Prints a form for user preferences
*/
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/trackeR/forms/mypreferences_form.php');

tracker_loadpreferences($tracker->id, $USER->id);

$form = new my_preferences_form(new moodle_url('/mod/tracker/view.php', array('id' => $cm->id)), array('tracker' => $tracker));

if ($data = $form->get_data()) {

    // Saves the user's preferences *************************************************************.

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

echo $OUTPUT->heading(get_string('mypreferences', 'tracker'));
echo $OUTPUT->box_start('generalbox', 'tracker-preferences-form');

$form->display();

echo $OUTPUT->box_end();