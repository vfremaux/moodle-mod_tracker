<?php

	require_once("../../config.php");
	require_once($CFG->dirroot."/mod/tracker/lib.php");
	require_once($CFG->dirroot."/mod/tracker/locallib.php");
	require_once $CFG->dirroot.'/mod/tracker/forms/addcomment_form.php';

	$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
	$t  = optional_param('t', 0, PARAM_INT);  // tracker ID
	$issueid  = required_param('issueid', PARAM_INT);  // tracker ID

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
	
	$context = context_module::instance($cm->id);

	require_course_login($course->id, false, $cm);
	require_capability('mod/tracker:comment', $context);

    if (!$issue = $DB->get_record('tracker_issue', array('id' => $issueid))){
    	print_error('errorbadissueid', 'tracker');
    }


	// setting page
	$url = $CFG->wwwroot.'/mod/tracker/addcomment.php?id='.$id.'&amp;issueid='.$issueid;
    $PAGE->set_url($url);
    $PAGE->set_context($context);
	$PAGE->set_title(format_string($tracker->name));
	$PAGE->set_heading(format_string($tracker->name));
    $PAGE->set_button($OUTPUT->update_module_button($cm->id, 'tracker'));
    $PAGE->set_headingmenu(navmenu($course, $cm));
	
	add_to_log($course->id, 'tracker', "reportissue", "view.php?id={$cm->id}", "$tracker->id", $cm->id);

	$form = new AddCommentForm($CFG->wwwroot.'/mod/tracker/addcomment.php', array('issueid' => $issueid, 'cmid' => $id));
	
	if (!$form->is_cancelled()){
		if ($data = $form->get_data()){

		    $comment = new StdClass();
		    $comment->comment = $data->comment_editor['text'];
		    $comment->commentformat = $data->comment_editor['format'];
		    $comment->userid = $USER->id;
		    $comment->trackerid = $tracker->id;
		    $comment->issueid = $issueid;
		    $comment->datecreated = time();
		    if (!$comment->id = $DB->insert_record('tracker_issuecomment', $comment)){
		        print_error('cannotwritecomment', 'tracker');
		    }
		
		    if ($tracker->allownotifications){
		        tracker_notifyccs_comment($issueid, $comment->comment, $tracker);
		    }
			tracker_register_cc($tracker, $issue, $USER->id);

			// stores files
            $data = file_postupdate_standard_editor($data, 'comment', $form->editoroptions, $context, 'mod_tracker', 'issuecomment', $comment->id);
			// update back reencoded field text content
			$DB->set_field('tracker_issuecomment', 'comment', $data->comment, array('id' => $comment->id));
			redirect($CFG->wwwroot.'/mod/tracker/view.php?id='.$id.'&amp;view=view&amp;screen=viewanissue&amp;issueid='.$issueid);
		}
	} else {
		redirect($CFG->wwwroot.'/mod/tracker/view.php?id='.$id.'&amp;view=view&amp;screen=viewanissue&amp;issueid='.$issueid);
	}
	
	echo $OUTPUT->header();
	
	echo $OUTPUT->heading($issue->summary);
	
	$description = file_rewrite_pluginfile_urls($issue->description, 'pluginfile.php', $context->id, 'mod_tracker', 'issuedescription', $issue->id);
	echo $OUTPUT->box(format_text($description, $issue->descriptionformat), 'tracker-issue-description');

	echo $OUTPUT->heading(get_string('addacomment', 'tracker'));

	$form->display();
	
	echo $OUTPUT->footer();