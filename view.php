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
	$screen = optional_param('screen', '', PARAM_ALPHA);
	$view = optional_param('view', '', PARAM_ALPHA);
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

	// redirect (before outputting) traps
	if ($view == "view" && (empty($screen) || $screen == 'viewanissue' || $screen == 'editanissue') && empty($issueid)){
        redirect("view.php?id={$cm->id}&amp;view=view&amp;screen=browse");
	}

	// implicit routing
	if ($issueid){
		$view = 'view';
		if (empty($screen)) $screen = 'viewanissue';
	}

	$context = context_module::instance($cm->id);
	require_login($course->id);
	
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
	        redirect("view.php?id={$cm->id}&amp;screen={$returnview}");
	    }
	}
	
	$strtrackers = get_string('modulenameplural', 'tracker');
	$strtracker  = get_string('modulename', 'tracker');

	if (file_exists($CFG->libdir.'/jqplotlib.php')){
		include_once $CFG->libdir.'/jqplotlib.php';
		require_jqplot_libs();
	}

	$PAGE->set_title(format_string($tracker->name));
	$PAGE->set_heading("");
	/* SCANMSG: may be additional work required for $navigation variable */
	$PAGE->set_focuscontrol("");
	$PAGE->set_url($CFG->wwwroot.'/mod/tracker/view.php?id='.$cm->id);
	$PAGE->set_cacheable(true);
	$PAGE->set_button(update_module_button($cm->id, $course->id, $strtracker));
	$PAGE->set_headingmenu(navmenu($course, $cm));
	echo $OUTPUT->header();

	// PART OF MVC Implementation
	/// memorizes current view - typical session switch
	if (!empty($view)){
	    $_SESSION['currentview'] = $view;
	} elseif (empty($_SESSION['currentview'])) {
	    $_SESSION['currentview'] = 'reportanissue';
	}
	$view = $_SESSION['currentview'];

	/// memorizes current screen - typical session switch
	if (!empty($screen)){
	    $_SESSION['currentscreen'] = $screen;
	} elseif (empty($_SESSION['currentscreen'])) {
	    $_SESSION['currentscreen'] = '';
	}
	$screen = $_SESSION['currentscreen'];
	// !PART OF MVC Implementation

	echo $OUTPUT->box_start('', 'tracker-view');

	$totalissues = $DB->count_records_select('tracker_issue', "trackerid = {$tracker->id} AND status <> ".RESOLVED." AND status <> ".ABANDONNED);
	$totalresolvedissues = $DB->count_records_select('tracker_issue', "trackerid = $tracker->id AND (status = ".RESOLVED." OR status = ".ABANDONNED.")");
	/// Print tabs with options for user
	$rows[0][] = new tabobject('reportanissue', "view.php?id={$cm->id}&amp;view=reportanissue", get_string('newissue', 'tracker'));
	$rows[0][] = new tabobject('view', "view.php?id={$cm->id}&amp;view=view", get_string('view', 'tracker').' ('.$totalissues.' '.get_string('issues','tracker').')');
	$rows[0][] = new tabobject('resolved', "view.php?id={$cm->id}&amp;view=resolved", get_string('resolvedplural', 'tracker').' ('.$totalresolvedissues.' '.get_string('issues','tracker').')');
	$rows[0][] = new tabobject('profile', "view.php?id={$cm->id}&amp;view=profile", get_string('profile', 'tracker'));
	if (has_capability('mod/tracker:viewreports', $context)){
		$rows[0][] = new tabobject('reports', "view.php?id={$cm->id}&amp;view=reports", get_string('reports', 'tracker'));
	}
	if (has_capability('mod/tracker:configure', $context)){
	    $rows[0][] = new tabobject('admin', "view.php?id={$cm->id}&amp;view=admin", get_string('administration', 'tracker'));
	}
	
	/// submenus
	$selected = null;
	$activated = null;
	switch ($view){
	    case 'reportanissue':
	        $screen = '';
	        $selected = $view;
	    	break;
	    case 'view' :
	        if (!preg_match("/mytickets|mywork|browse|search|viewanissue|editanissue/", $screen)) $screen = 'mytickets';
	        $rows[1][] = new tabobject('mytickets', "view.php?id={$cm->id}&amp;view=view&amp;screen=mytickets", get_string('mytickets', 'tracker'));
	        $rows[1][] = new tabobject('mywork', "view.php?id={$cm->id}&amp;view=view&amp;screen=mywork", get_string('mywork', 'tracker'));
	        if (has_capability('mod/tracker:viewallissues', $context) || $tracker->supportmode == 'bugtracker'){
	            $rows[1][] = new tabobject('browse', "view.php?id={$cm->id}&amp;view=view&amp;screen=browse", get_string('browse', 'tracker'));
	        }
	        $rows[1][] = new tabobject('search', "view.php?id={$cm->id}&amp;view=view&amp;screen=search", get_string('search', 'tracker'));
	        break;
	    case 'resolved' :
	        if (!preg_match("/mytickets|browse/", $screen)) $screen = 'mytickets';
	        $rows[1][] = new tabobject('mytickets', "view.php?id={$cm->id}&amp;view=resolved&amp;screen=mytickets", get_string('mytickets', 'tracker'));
	        $rows[1][] = new tabobject('mywork', "view.php?id={$cm->id}&amp;view=view&amp;screen=mywork", get_string('mywork', 'tracker'));
	        if (has_capability('mod/tracker:viewallissues', $context) || $tracker->supportmode == 'bugtracker'){
	            $rows[1][] = new tabobject('browse', "view.php?id={$cm->id}&amp;view=resolved&amp;screen=browse", get_string('browse', 'tracker'));
	        }
	    break;
	    case 'profile':
	        if (!preg_match("/myprofile|mypreferences|mywatches|myqueries/", $screen)) $screen = 'myprofile';
	        $rows[1][] = new tabobject('myprofile', "view.php?id={$cm->id}&amp;view=profile&amp;screen=myprofile", get_string('myprofile', 'tracker'));
	        $rows[1][] = new tabobject('mypreferences', "view.php?id={$cm->id}&amp;view=profile&amp;screen=mypreferences", get_string('mypreferences', 'tracker'));
	        $rows[1][] = new tabobject('mywatches', "view.php?id={$cm->id}&amp;view=profile&amp;screen=mywatches", get_string('mywatches', 'tracker'));
	        $rows[1][] = new tabobject('myqueries', "view.php?id={$cm->id}&amp;view=profile&amp;screen=myqueries", get_string('myqueries', 'tracker'));
	    break;
	    case 'reports':
	        if (!preg_match("/status|evolution|print/", $screen)) $screen = 'status';
	        $rows[1][] = new tabobject('status', "view.php?id={$cm->id}&amp;view=reports&amp;screen=status", get_string('status', 'tracker'));
	        $rows[1][] = new tabobject('evolution', "view.php?id={$cm->id}&amp;view=reports&amp;screen=evolution", get_string('evolution', 'tracker'));
	        $rows[1][] = new tabobject('print', "view.php?id={$cm->id}&amp;view=reports&amp;screen=print", get_string('print', 'tracker'));
	    break;
	    case 'admin':
	        if (!preg_match("/summary|manageelements|managenetwork/", $screen)) $screen = 'summary';
	        $rows[1][] = new tabobject('summary', "view.php?id={$cm->id}&amp;view=admin&amp;screen=summary", get_string('summary', 'tracker'));
	        $rows[1][] = new tabobject('manageelements', "view.php?id={$cm->id}&amp;view=admin&amp;screen=manageelements", get_string('manageelements', 'tracker'));
			if (has_capability('mod/tracker:configurenetwork', $context)){
	        	$rows[1][] = new tabobject('managenetwork', "view.php?id={$cm->id}&amp;view=admin&amp;screen=managenetwork", get_string('managenetwork', 'tracker'));
	        }
	        break;
	    default:
	}
	if (!empty($screen)){
	    $selected = $screen;
	    $activated = array($view);
	} else {
	    $selected = $view;
	}
	echo $OUTPUT->container_start('mod-header');
	print_tabs($rows, $selected, '', $activated);
	echo '<br/>';
	echo $OUTPUT->container_end();

	//=====================================================================
	// Print the main part of the page
	//
	//=====================================================================
	/// routing to appropriate view against situation
	// echo "routing : $view:$screen:$action ";

	if ($view == 'reportanissue'){
	    if (has_capability('mod/tracker:report', $context)){
	        include "views/issuereportform.html";
	    } else {
	        echo $OUTPUT->notification(get_string('youneedanaccount','tracker'), $CFG->wwwroot."/course/view.php?id={$course->id}");
	    }
	} elseif ($view == 'view'){
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
                    if (!has_capability('mod/tracker:seeissues', $context)){
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
	echo $OUTPUT->footer($course);
?>
