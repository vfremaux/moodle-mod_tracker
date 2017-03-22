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
require_once($CFG->dirroot.'/mod/tracker/lib.php');
require_once($CFG->dirroot.'/mod/tracker/locallib.php');
require_once($CFG->dirroot.'/mod/tracker/forms/addcomment_form.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or.
$t = optional_param('t', 0, PARAM_INT);  // Tracker ID.
$issueid = required_param('issueid', PARAM_INT);  // Tracker ID.
$commentid = required_param('commentid', PARAM_INT);  // Id of comment ofr editing.

if ($id) {
    if (! $cm = get_coursemodule_from_id('tracker', $id)) {
        print_error('errorcoursemodid', 'tracker');
    }

    if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('errorcoursemisconfigured', 'tracker');
    }

    if (! $tracker = $DB->get_record('tracker', array('id' => $cm->instance))) {
        print_error('errormoduleincorrect', 'tracker');
    }
} else {

    if (! $tracker = $DB->get_record('tracker', array('id' => $t))) {
        print_error('errormoduleincorrect', 'tracker');
    }

    if (! $course = $DB->get_record('course', array('id' => $tracker->course))) {
        print_error('errorcoursemisconfigured', 'tracker');
    }

    if (! $cm = get_coursemodule_from_instance("tracker", $tracker->id, $course->id)) {
        print_error('errorcoursemodid', 'tracker');
    }
}

// Security.

$context = context_module::instance($cm->id);
require_course_login($course->id, false, $cm);
require_capability('mod/tracker:comment', $context);

if (!$issue = $DB->get_record('tracker_issue', array('id' => $issueid))) {
    print_error('errorbadissueid', 'tracker');
}

// Setting page.

$url = new moodle_url('/mod/tracker/comment.php', array('id' => $id, 'issueid' => $issueid));
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(format_string($tracker->name));
$PAGE->set_heading(format_string($tracker->name));
$PAGE->set_button($OUTPUT->update_module_button($cm->id, 'tracker'));

$form = new add_comment_form(new moodle_url('/mod/tracker/comment.php'), array('issueid' => $issueid, 'cmid' => $id));

if (!$form->is_cancelled()) {
    if ($data = $form->get_data()) {

        if (empty($data->commentid)) {
            $comment = new StdClass();
            $comment->comment = $data->comment_editor['text'];
            $comment->commentformat = $data->comment_editor['format'];
            $comment->userid = $USER->id;
            $comment->trackerid = $tracker->id;
            $comment->issueid = $issueid;
            $comment->datecreated = time();
            $commentid = $DB->insert_record('tracker_issuecomment', $comment);
        } else {
            $comment = $data;
            $comment->id = $comment->commentid;
            unset($data->id);
            $DB->update_record('tracker_issuecomment', $comment);
        }

        if ($tracker->allownotifications) {
            tracker_notifyccs_comment($issueid, $comment->comment, $tracker);
        }
        tracker_register_cc($tracker, $issue, $USER->id);

        // Stores files.
        $data = file_postupdate_standard_editor($data, 'comment', $form->editoroptions, $context,
                                                'mod_tracker', 'issuecomment', $commentid);

        // Update back reencoded field text content.
        $DB->set_field('tracker_issuecomment', 'comment', $data->comment, array('id' => $comment->id));
        $params = array('id' => $id, 'view' => 'view', 'screen' => 'viewanissue', 'issueid' => $issueid);

        $event = \mod_tracker\event\tracker_issuecommented::create_from_issue($tracker, $issueid);
        $event->trigger();

        redirect(new moodle_url('/mod/tracker/view.php', $params));
    }

    $comment = $DB->get_record('tracker_issuecomment', array('id' => $commentid));

} else {
    $params = array('id' => $id, 'view' => 'view', 'screen' => 'viewanissue', 'issueid' => $issueid);
    redirect(new moodle_url('/mod/tracker/view.php', $params));
}

echo $OUTPUT->header();

echo $OUTPUT->heading($issue->summary);

$description = file_rewrite_pluginfile_urls($issue->description, 'pluginfile.php', $context->id, 'mod_tracker',
                                            'issuedescription', $issue->id);
echo $OUTPUT->box(format_text($description, $issue->descriptionformat), 'tracker-issue-description');

echo $OUTPUT->heading(get_string('addacomment', 'tracker'));

if (!empty($commentid)) {
    $form->set_date($comment);
}
$form->display();

echo $OUTPUT->footer();