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
 * This page lists all the instances of tracker in a particular course
 * Replace tracker with the name of your module
 */
require('../../config.php');
require_once($CFG->dirroot.'/mod/tracker/lib.php');

$id = required_param('id', PARAM_INT); // Course.

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

// Security.

require_login($course->id);

// Trigger instances list viewed event.
$event = \mod_tracker\event\course_module_instance_list_viewed::create(array('context' => $context));
$event->add_record_snapshot('course', $course);
$event->trigger();

// Get all required strings.
$strtrackers = get_string('modulenameplural', 'tracker');
$strtracker  = get_string('modulename', 'tracker');

// Print the header.
$navigation = build_navigation($strtrackers);
$PAGE->set_title($strtrackers);
$PAGE->set_heading($strtrackers);
$PAGE->navbar->add($strtrackers);
$PAGE->set_cacheable(true);
$PAGE->set_button('');
$PAGE->set_headingmenu(navmenu($course));
echo $OUTPUT->header();

// Get all the appropriate data.
if (! $trackers = get_all_instances_in_course('tracker', $course)) {
    echo $OUTPUT->notification(get_string('notrackers', 'tracker'), new moodle_url('course/view.php', array('id' => $course->id)));
    die;
}

// Print the list of instances (your module will probably extend this).
$timenow = time();
$strname = get_string('name');
$strweek = get_string('week');
$strtopic = get_string('topic');

if ($course->format == 'weeks') {
    $table->head  = array ($strweek, $strname);
    $table->align = array ('center', 'left');
} else if ($course->format == 'topics') {
    $table->head  = array ($strtopic, $strname);
    $table->align = array ('center', 'left', 'left', 'left');
} else {
    $table->head  = array ($strname);
    $table->align = array ('left', 'left', 'left');
}

foreach ($trackers as $tracker) {
    $trackername = format_string($tracker->name);
    $linkurl = new moodle_url('/mod/tracker/view.php', array('id' => $tracker->coursemodule));
    if (!$tracker->visible) {
        // Show dimmed if the mod is hidden.
        $link = '<a class="dimmed" href="'.$linkurl.'">'.$trackername.'</a>';
    } else {
        // Show normal if the mod is visible.
        $link = '<a href="'.$linkurl.'">'.$trackername.'</a>';
    }

    if ($course->format == 'weeks' or $course->format == 'topics') {
        $table->data[] = array ($tracker->section, $link);
    } else {
        $table->data[] = array ($link);
    }
}

echo '<br />';

echo html_writer::table($table);

// Finish the page.

echo $OUTPUT->footer($course);
