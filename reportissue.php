<?php

	require_once("../../config.php");
	require_once($CFG->dirroot."/mod/tracker/lib.php");
	require_once($CFG->dirroot."/mod/tracker/locallib.php");
	require_once $CFG->dirroot.'/mod/tracker/forms/reportissue_form.php';

	$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
	$a  = optional_param('a', 0, PARAM_INT);  // tracker ID

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
		
	    if (! $tracker = $DB->get_record('tracker', array('id' => $a))) {
	        print_error('errormoduleincorrect', 'tracker');
	    }
		
	    if (! $course = $DB->get_record('course', array('id' => $tracker->course))) {
	        print_error('errorcoursemisconfigured', 'tracker');
	    }
	    if (! $cm = get_coursemodule_from_instance("tracker", $tracker->id, $course->id)) {
	        print_error('errorcoursemodid', 'tracker');
	    }
	}

	$screen = tracker_resolve_screen($tracker, $cm);
	$view = tracker_resolve_view($tracker, $cm);
	
	$context = context_module::instance($cm->id);

	require_course_login($course->id, false, $cm);
	require_capability('mod/tracker:report', $context);

	// setting page
	$url = $CFG->wwwroot.'/mod/tracker/reportissue.php?id='.$id;
    $PAGE->set_url($url);
    $PAGE->set_context($context);
	$PAGE->set_title(format_string($tracker->name));
	$PAGE->set_heading(format_string($tracker->name));
    $PAGE->set_button($OUTPUT->update_module_button($cm->id, 'tracker'));
    $PAGE->set_headingmenu(navmenu($course, $cm));
	
	add_to_log($course->id, 'tracker', "reportissue", "view.php?id={$cm->id}", "$tracker->id", $cm->id);

	$form = new TrackerIssueForm($CFG->wwwroot.'/mod/tracker/reportissue.php', array('trackerid' => $tracker->id, 'cmid' => $id));
	
	if (!$form->is_cancelled()){
		if ($data = $form->get_data()){

			if (!$issue = tracker_submitanissue($tracker, $data)){
				print_error('errorcannotsubmitticket', 'tracker');
			}

			// stores files
            $data = file_postupdate_standard_editor($data, 'description', $form->editoroptions, $context, 'mod_tracker', 'issuedescription', $data->issueid);
			// update back reencoded field text content
			$DB->set_field('tracker_issue', 'description', $data->description, array('id' => $issue->id));

			// log state change
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
			echo (empty($tracker->thanksmessage)) ? get_string('thanksdefault', 'tracker') : format_string($tracker->thanksmessage) ;
			echo $OUTPUT->box_end();
			echo $OUTPUT->continue_button($CFG->wwwroot."/mod/tracker/view.php?id={$cm->id}view=view&amp;screen=browse");
			echo $OUTPUT->footer();
			die;
			// notify all admins
			if ($tracker->allownotifications){
				tracker_notify_submission($issue, $cm, $tracker);
				if ($issue->assignedto){
					tracker_notifyccs_changeownership($issue->id, $tracker);
				}
			}
		}
	}
	
	echo $OUTPUT->header();

	$view = 'reportanissue';
	include_once 'menus.php';
	
	$form->display();
	
	echo $OUTPUT->footer();