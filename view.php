<?PHP  // $Id: view.php,v 1.1.1.1 2012-08-01 10:16:32 vf Exp $

/**
* @package mod-tracker
* @category mod
* @author Clifford Tham, Valery Fremaux > 1.8
* @date 02/12/2007
*
* This page prints a particular instance of a tracker and handles
* top level interactions
*/

	require_once("../../config.php");
	require_once($CFG->dirroot."/mod/tracker/lib.php");
	require_once($CFG->dirroot."/mod/tracker/locallib.php");
	
	// $usehtmleditor = false;
	// $editorfields = '';

	/// Check for required parameters - Course Module Id, trackerID, 

	$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
	$a  = optional_param('a', 0, PARAM_INT);  // tracker ID
	$issueid = optional_param('issueid', '', PARAM_INT);  // issue number

	// PART OF MVC Implementation
	$action = optional_param('what', '', PARAM_ALPHA);
	// !PART OF MVC Implementation

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
	
	$url = $CFG->wwwroot.'/mod/tracker/view.php?id='.$cm->id;
	
	// redirect (before outputting) traps
	if ($view == "view" && (empty($screen) || $screen == 'viewanissue' || $screen == 'editanissue') && empty($issueid)){
        redirect("view.php?id={$cm->id}&amp;view=view&amp;screen=browse");
	}
	if ($view == 'reportanissue'){
		redirect($CFG->wwwroot.'/mod/tracker/reportissue.php?id='.$id);
	}

	// implicit routing
	if ($issueid){
		$view = 'view';
		if (empty($screen)) $screen = 'viewanissue';
	}

	require_course_login($course->id, true, $cm);
	
	add_to_log($course->id, 'tracker', "$view:$screen/$action", "view.php?id=$cm->id", "$tracker->id", $cm->id);
	
	$usehtmleditor = can_use_html_editor();
	$defaultformat = FORMAT_MOODLE;
	tracker_loadpreferences($tracker->id, $USER->id);
	
	/// Search controller - special implementation
	// TODO : consider incorporing this controller back into standard MVC
	if ($action == 'searchforissues'){
	    $search = optional_param('search', null, PARAM_CLEANHTML);
	    $saveasreport = optional_param('saveasreport', null, PARAM_CLEANHTML);
	    
	    if (!empty($search)){       //search for issues
	        tracker_searchforissues($tracker, $cm->id);
	    } elseif (!empty ($saveasreport)){        //save search as a report
	        tracker_saveasreport($tracker->id);
	    }
	} elseif ($action == 'viewreport'){
	    tracker_viewreport($tracker->id);
	} elseif ($action == 'clearsearch'){
	    if (tracker_clearsearchcookies($tracker->id)){
	        $returnview = ($tracker->supportmode == 'bugtracker') ? 'browse' : 'mytickets' ;
	        redirect("view.php?id={$cm->id}&amp;view=view&amp;screen={$returnview}");
	    }
	}
	
	$strtrackers = get_string('modulenameplural', 'tracker');
	$strtracker  = get_string('modulename', 'tracker');

	if (file_exists($CFG->libdir.'/jqplotlib.php')){
		include_once $CFG->libdir.'/jqplotlib.php';
		require_jqplot_libs();
	}

	$context = context_module::instance($cm->id);
    $PAGE->set_context($context);

	$PAGE->set_title(format_string($tracker->name));
	$PAGE->set_heading(format_string($tracker->name));
	$PAGE->set_url($url);
    $PAGE->set_button($OUTPUT->update_module_button($cm->id, 'tracker'));
    $PAGE->set_headingmenu(navmenu($course, $cm));
    
    if ($screen == 'print'){
    	$PAGE->set_pagelayout('embedded');
    }

	echo $OUTPUT->header();

	echo $OUTPUT->box_start('', 'tracker-view');

	include 'menus.php';

	//=====================================================================
	// Print the main part of the page
	//
	//=====================================================================
	/// routing to appropriate view against situation
	// echo "routing : $view:$screen:$action ";

	if ($view == 'view'){
	    $result = 0 ;
	    if ($action != ''){
	        $result = include "views/view.controller.php";
	    }
	    if ($result != -1){
	        switch($screen){
	            case 'mytickets': 
	                $resolved = 0;
	                include "views/viewmyticketslist.php";
	                break;
	            case 'mywork': 
	                $resolved = 0;
	                include "views/viewmyassignedticketslist.php";
	                break;
	            case 'browse': 
	                if (!has_capability('mod/tracker:viewallissues', $context)){
	                    print_error ('errornoaccessallissues', 'tracker');
	                } else {
	                    $resolved = 0;
	                    include "views/viewissuelist.php";
	                } 
	                break;
	            case 'search': 
	                include "views/searchform.html";
	                break;
	            case 'viewanissue' :
	                ///If user it trying to view an issue, check to see if user has privileges to view this issue
                    if (!has_any_capability(array('mod/tracker:seeissues','mod/tracker:resolve','mod/tracker:develop','mod/tracker:manage'), $context)){
                        print_error('errornoaccessissue', 'tracker');
                    } else {
                        include "views/viewanissue.html";
                    }
	                break;
	            case 'editanissue' :
                    if (!has_capability('mod/tracker:manage', $context)){
                        print_error('errornoaccessissue', 'tracker');
                    } else {
                        include "views/editanissue.html";   
                    }
	                break;
	        }
	    }
	} elseif ($view == 'resolved'){
	    $result = 0 ;
	    if ($action != ''){
	        $result = include "views/view.controller.php";
	    }
	    if ($result != -1){
	        switch($screen){
	            case 'mytickets': 
	                $resolved = 1;
	                include "views/viewmyticketslist.php";
	                break;
	            case 'mywork': 
	                $resolved = 1;
	                include "views/viewmyassignedticketslist.php";
	                break;
	            case 'browse': 
	                if (!has_capability('mod/tracker:viewallissues', $context)){
	                    print_error('errornoaccessallissues', 'tracker');
	                } else {
	                    $resolved = 1;
	                    include "views/viewissuelist.php";
	                } 
	                break;
	        }
	    }
	} elseif ($view == 'reports') {
	    $result = 0;
	    if ($result != -1){
	        switch($screen){
	            case 'status': 
	                include "report/status.html"; 
	                break;
	            case 'evolution': 
	                include "report/evolution.html";
	                break;
	            case 'print': 
	                include "report/print.html";
	                break;
	        }
	    }
	} elseif ($view == 'admin') {
	    $result = 0;
	    if ($action != ''){
	        $result = include "views/admin.controller.php";
	    }
	    if ($result != -1){
	        switch($screen){
	            case 'summary': 
	                include "views/admin_summary.html"; 
	                break;
	            case 'manageelements': 
	                include "views/admin_manageelements.html";
	                break;
	            case 'managenetwork': 
	                include "views/admin_mnetwork.html";
	                break;
	        }
	    }
	}
	elseif ($view == 'profile'){
	    $result = 0;
	    if ($action != ''){
	        $result = include "views/profile.controller.php";
	    }
	    if ($result != -1){
	        switch($screen){
	            case 'myprofile' :
	                include "views/profile.html";
	                break;
	            case 'mypreferences' :
	                include "views/mypreferences.html";
	                break;
	            case 'mywatches' :
	                include "views/mywatches.html";
	                break;
	            case 'myqueries':
	                include "views/myqueries.html";
	                break;
	        }
	    }
	} else {
	    print_error('errorfindingaction', 'tracker', $action);
	}
	echo $OUTPUT->box_end();
	echo $OUTPUT->footer();
