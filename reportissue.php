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
 */
require('../../config.php');
require_once($CFG->dirroot."/mod/tracker/lib.php");
require_once($CFG->dirroot."/mod/tracker/locallib.php");
require_once($CFG->dirroot.'/mod/tracker/forms/reportissue_form.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or.
$t  = optional_param('t', 0, PARAM_INT);  // tracker ID.

list($cm, $tracker, $course) = tracker_get_context($id, $t);

$screen = tracker_resolve_screen($tracker, $cm);
$view = tracker_resolve_view($tracker, $cm);

// Security.
$context = context_module::instance($cm->id);
require_course_login($course->id, false, $cm);
require_capability('mod/tracker:report', $context);

// Setting page.
$url = new moodle_url('/mod/tracker/reportissue.php', array('id' => $id));
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(format_string($tracker->name));
$PAGE->set_heading(format_string($tracker->name));
$PAGE->set_button($OUTPUT->update_module_button($cm->id, 'tracker'));

$renderer = $PAGE->get_renderer('mod_tracker');

$params = array('tracker' => $tracker, 'cmid' => $id, 'mode' => 'add');
$form = new TrackerIssueForm(new moodle_url('/mod/tracker/reportissue.php'), $params);

if (!$form->is_cancelled()) {
    if ($data = $form->get_data()) {

        if (!$issue = tracker_submitanissue($tracker, $data)) {
            print_error('errorcannotsubmitticket', 'tracker');
        }

        $event = \mod_tracker\event\tracker_issuereported::create_from_issue($tracker, $issue->id);
        $event->trigger();

        // Stores files.
        $data = file_postupdate_standard_editor($data, 'description', $form->editoroptions, $context, 'mod_tracker',
                                                'issuedescription', $data->issueid);
        // Update back reencoded field text content.
        $DB->set_field('tracker_issue', 'description', $data->description, array('id' => $issue->id));

        // Log state change.
        $stc = new StdClass;
        $stc->userid = $USER->id;
        $stc->issueid = $issue->id;
        $stc->trackerid = $tracker->id;
        $stc->timechange = time();
        $stc->statusfrom = POSTED;
        $stc->statusto = POSTED;
        $DB->insert_record('tracker_state_change', $stc);
        echo $OUTPUT->header();
        echo $OUTPUT->box_start('generalbox', 'tracker-acknowledge');
        echo (empty($tracker->thanksmessage)) ? get_string('thanksdefault', 'tracker') : format_string($tracker->thanksmessage);
        echo $OUTPUT->box_end();
        echo $OUTPUT->continue_button(new moodle_url('/mod/tracker/view.php', array('id' => $cm->id, 'view' => 'view', 'screen' => 'browse')));
        echo $OUTPUT->footer();

        tracker_recordelements($issue, $data);

        // Notify all admins.
        if ($tracker->allownotifications) {
            tracker_notify_submission($issue, $cm, $tracker);
            if ($issue->assignedto) {
                tracker_notifyccs_changeownership($issue->id, $tracker);
            }
        }
    }
}

echo $OUTPUT->header();

$view = 'reportanissue';
echo $renderer->tabs($view, $screen, $tracker, $cm);

$formdata = new StdClass;
$formdata->id = $id;
$form->set_data($formdata);
$form->display();

echo $OUTPUT->footer();