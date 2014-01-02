<?PHP  // $Id: lib.php,v 1.1.1.1 2012-08-01 10:16:31 vf Exp $

/**
* @package mod-tracker
* @author Clifford Tham, Valery Fremaux > 1.8
* @date 02/12/2007
* @version Moodle 2.0
*
* Library of functions and constants for module tracker
*/

include_once('classes/trackercategorytype/trackerelement.class.php');
require_once($CFG->dirroot.'/mod/tracker/locallib.php');

/**
 * List of features supported in tracker module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function tracker_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_OTHER;
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_GROUPMEMBERSONLY:        return false;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return false;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}

/** 
* Given an object containing all the necessary data, 
* (defined by the form in mod.html) this function 
* will create a new instance and return the id number 
* of the new instance.
* @param object $tracker
*/
function tracker_add_instance($tracker, $mform) {
	global $DB;

    $tracker->timemodified = time();
    if (empty($tracker->allownotifications)) $tracker->allownotifications = 0;
    if (empty($tracker->enablecomments)) $tracker->enablecomments = 0;
    if (empty($tracker->format)) $tracker->format = FORMAT_MOODLE;

	if (is_array(@$tracker->subtrackers)){
		$tracker->subtrackers = implode(',', $tracker->subtrackers);
	}

	$tracker->id = $DB->insert_record('tracker', $tracker);
				
	$context = context_module::instance($tracker->coursemodule);

	// make some presets depending on tracker type
	if ($tracker->supportmode != 'customized'){
		tracker_setup_role_overrides($tracker, $context);
		tracker_preset_states($tracker);
		tracker_preset_params($tracker);
		$DB->set_field('tracker', 'enabledstates', $tracker->enabledstates, array('id' => $tracker->id));
	} else {
		tracker_clear_role_overrides($context);
	}

	if (empty($tracker->ticketprefix)){
		$tracker->ticketprefix = 'TRK'.$tracker->id.'_';
		$DB->set_field('tracker', 'ticketprefix', $tracker->ticketprefix, array('id' => $tracker->id));
	}

    return $tracker->id;
}

/**
* Given an object containing all the necessary data, 
* (defined by the form in mod.html) this function 
* will update an existing instance with new data.
*/
function tracker_update_instance($tracker, $mform) {
	global $DB;

    $tracker->timemodified = time();
    $tracker->id = $tracker->instance;

	if (is_array(@$tracker->subtrackers)){
		$tracker->subtrackers = implode(',', $tracker->subtrackers);
	}

	$context = context_module::instance($tracker->coursemodule);

	if ($tracker->supportmode != 'customized'){
		tracker_setup_role_overrides($tracker, $context);
		tracker_preset_states($tracker);
		tracker_preset_params($tracker);
		$DB->set_field('tracker', 'enabledstates', $tracker->enabledstates, array('id' => $tracker->id));
	} else {
		tracker_clear_role_overrides($context);
	}

	if (empty($tracker->ticketprefix)){
		$tracker->ticketprefix = 'TRK'.$tracker->id.'_';
		$DB->set_field('tracker', 'ticketprefix', $tracker->ticketprefix, array('id' => $tracker->id));
	}

    return $DB->update_record('tracker', $tracker);
}

/**
* Given an ID of an instance of this module, 
* this function will permanently delete the instance 
* and any data that depends on it.  
*/
function tracker_delete_instance($id) {
	global $DB;

    if (! $tracker = $DB->get_record('tracker', array('id' => "$id"))) {
        return false;
    }

    if (!$cm = get_coursemodule_from_instance('tracker', $tracker->id)) {
        return false;
    }

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    $result = true;

    /// Delete any dependent records here 
    $DB->delete_records('tracker_issue', array('trackerid' => "$tracker->id"));
    $DB->delete_records('tracker_elementused', array('trackerid' => "$tracker->id"));
    $DB->delete_records('tracker_query', array('trackerid' => "$tracker->id"));
    $DB->delete_records('tracker_issuedependancy', array('trackerid' => "$tracker->id"));
    $DB->delete_records('tracker_issueownership', array('trackerid' => "$tracker->id"));
    $DB->delete_records('tracker_issueattribute', array('trackerid' => "$tracker->id"));
    $DB->delete_records('tracker_issuecc', array('trackerid' => "$tracker->id"));
    $DB->delete_records('tracker_issuecomment', array('trackerid' => "$tracker->id"));

	// delete all files attached to this context
    $fs = get_file_storage();
    $fs->delete_area_files($context->id);

    return $result;
}

/**
* Return a small object with summary information about what a 
* user has done with a given particular instance of this module
* Used for user activity reports.
* $return->time = the time they did it
* $return->info = a short text description
*/
function tracker_user_outline($course, $user, $mod, $tracker) {

    return NULL;
}

/**
* Print a detailed representation of what a  user has done with 
* a given particular instance of this module, for user activity reports.
*/
function tracker_user_complete($course, $user, $mod, $tracker) {

    return NULL;
}

/**
* Given a course and a time, this module should find recent activity 
* that has occurred in tracker activities and print it out. 
* Return true if there was output, or false is there was none.
*/
function tracker_print_recent_activity($course, $isteacher, $timestart) {
	global $DB, $CFG;
	
    $sql = "
        SELECT
            ti.id,
            ti.trackerid,
            ti.summary,
            ti.reportedby,
            ti.datereported,
            t.name,
            t.ticketprefix
         FROM
            {tracker} t,
            {tracker_issue} ti
         WHERE
            t.id = ti.trackerid AND
            t.course = $course->id AND
            ti.datereported > $timestart
    ";
    $newstuff = $DB->get_records_sql($sql);
    if ($newstuff){
        foreach($newstuff as $anissue){
            echo "<span style=\"font-size:0.8em\">"; 
            echo get_string('modulename', 'tracker').': '.format_string($anissue->name).':<br/>';
            echo "<a href=\"{$CFG->wwwroot}/mod/tracker/view.php?a={$anissue->trackerid}&amp;view=view&amp;page=viewanissue&amp;issueid={$anissue->id}\">".shorten_text(format_string($anissue->summary), 20).'</a><br/>';
            echo '&nbsp&nbsp&nbsp<span class="trackersmalldate">'.userdate($anissue->datereported).'</span><br/>';
            echo "</span><br/>";
        }
        return true;
    }
    
    return false;  //  True if anything was printed, otherwise false 
}

/**
 * Print an overview of all trackers
 * for the courses.
 *
 * @param mixed $courses The list of courses to print the overview for
 * @param array $htmlarray The array of html to return
 */
function tracker_print_overview($courses, &$htmlarray) {
    global $USER, $CFG, $DB;
    
    if (empty($courses) || !is_array($courses) || count($courses) == 0) {
        return array();
    }

    if (!$trackers = get_all_instances_in_courses('tracker', $courses)) {
        return;
    }

    $strtracker = get_string('modulename', 'tracker');

	foreach($trackers as $tracker){

        $str = '<div class="tracker overview">';
        $str .= '<div class="name">'.$strtracker. ': '.
               '<a '.($tracker->visible ? '':' class="dimmed"').
               'title="'.$strtracker.'" href="'.$CFG->wwwroot.
               '/mod/tracker/view.php?id='.$tracker->coursemodule.'">'.
               format_string($tracker->name).'</a></div>';

        $context = context_module::instance($tracker->coursemodule);
        if (has_capability('mod/tracker:develop', $context)) {

            // count how many assigned
            $sql = "
            	SELECT DISTINCT
            		i.id, i.id
            	FROM
            		{tracker_issue} i
            	LEFT JOIN
            		{tracker_issueownership} io
            	ON
            		io.issueid = i.id
            	WHERE
            		i.trackerid = ? AND
            		assignedto = ? AND
            		(status = ".POSTED." OR
            		status = ".OPEN." OR
            		status = ".RESOLVING.") AND
            		io.id IS NULL
            ";
            $yours = $DB->get_records_sql($sql, array($tracker->id, $USER->id));
            
            if ($yours) {
                $link = new moodle_url('/mod/tracker/view.php', array('id' => $tracker->coursemodule, 'view' => 'view', 'screen' => 'mywork'));
                $str .= '<div class="details"><a href="'.$link.'">'.get_string('issuestowatch', 'tracker', count($yours)).'</a></div>';
            }
		}

        if (has_capability('mod/tracker:manage', $context)) {

            // count how many unassigned
            $unassigned = $DB->get_records('tracker_issue', array('trackerid' => $tracker->id, 'assignedto' => 0, 'status' => POSTED));
            
            if ($unassigned) {
                $link = new moodle_url('/mod/tracker/view.php', array('id' => $tracker->coursemodule, 'view' => 'view', 'screen' => 'mywork'));
                $str .= '<div class="details"><a href="'.$link.'">'.get_string('issuestoassign', 'tracker', count($unassigned)).'</a></div>';
            }
		}
    	$str .= '</div>';

	    if (empty($htmlarray[$tracker->course]['tracker'])) {
	        $htmlarray[$tracker->course]['tracker'] = $str;
	    } else {
	        $htmlarray[$tracker->course]['tracker'] .= $str;
	    }
	}
}

/**
* Function to be run periodically according to the moodle cron
* This function searches for things that need to be done, such 
* as sending out mail, toggling flags etc ... 
*/
function tracker_cron () {

    global $CFG;

    return true;
}

/** 
* Must return an array of grades for a given instance of this module, 
* indexed by user.  It also returns a maximum allowed grade.
*
*    $return->grades = array of grades;
*    $return->maxgrade = maximum allowed grade;
*
*    return $return;
*/
function tracker_grades($trackerid) {

   return NULL;
}

/**
 *
 **/
function tracker_scale_used_anywhere($scaleid){
    return false;
}

/**
* Must return an array of user records (all data) who are participants
* for a given instance of tracker. Must include every user involved
* in the instance, independent of his role (student, teacher, admin...)
* See other modules as example.
*/
function tracker_get_participants($trackerid) {
	global $DB;

    $resolvers = $DB->get_records('tracker_issueownership', array('trackerid' => $trackerid), '', 'id,id');
    if(!$resolvers) $resolvers = array();
    $developers = $DB->get_records('tracker_issuecc', array('trackerid' => $trackerid), '', 'id,id');
    if(!$developers) $developers = array();
    $reporters = $DB->get_records('tracker_issue', array('trackerid' => $trackerid), '', 'reportedby,reportedby');
    if(!$reporters) $reporters = array();
    $admins = $DB->get_records('tracker_issueownership', array('trackerid' => $trackerid), '', 'bywhomid,bywhomid');
    if(!$admins) $admins = array();
    $commenters = $DB->get_records('tracker_issuecomment', array('trackerid' => $trackerid), '', 'userid,userid');
    if(!$commenters) $commenters = array();
    $participants = array_merge(array_keys($resolvers), array_keys($developers), array_keys($reporters), array_keys($admins));
    $participantlist = implode(',', array_unique($participants));
    
    if (!empty($participantlist)){
        return $DB->get_records_list('user', array('id' => $participantlist));   
    }
    return array();
}

/*
* This function returns if a scale is being used by one tracker
* it it has support for grading and scales. Commented code should be
* modified if necessary. See forum, glossary or journal modules
* as reference.
*/
function tracker_scale_used ($trackerid, $scaleid) {
    $return = false;

    //$rec = get_record("tracker","id","$trackerid","scale","-$scaleid");
    //
    //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}
   
    return $return;
}

/**
*
*
*/
function tracker_install(){
	global $DB;

    $result = true;

    if (!$DB->get_record('mnet_service', array('name' => 'tracker_cascade'))){
        $service->name = 'tracker_cascade';
        $service->description = get_string('transferservice', 'tracker');
        $service->apiversion = 1;
        $service->offer = 1;
        if (!$serviceid = $DB->insert_record('mnet_service', $service)){
            echo $OUTPUT->notification('Error installing tracker_cascade service.');
            $result = false;
        }
        $rpc->function_name = 'tracker_rpc_get_instances';
        $rpc->xmlrpc_path = 'mod/tracker/rpclib.php/tracker_rpc_get_instances';
        $rpc->parent_type = 'mod';  
        $rpc->parent = 'tracker';
        $rpc->enabled = 0; 
        $rpc->help = 'Get instances of available trackers for cascading.';
        $rpc->profile = '';
        if (!$rpcid = $DB->insert_record('mnet_rpc', $rpc)){
            echo $OUTPUT->notification('Error installing tracker_cascade RPC calls.');
            $result = false;
        }
        $rpcmap->serviceid = $serviceid;
        $rpcmap->rpcid = $rpcid;
        $DB->insert_record('mnet_service2rpc', $rpcmap);
        $rpc->function_name = 'tracker_rpc_get_infos';
        $rpc->xmlrpc_path = 'mod/tracker/rpclib.php/tracker_rpc_get_infos';
        $rpc->parent_type = 'mod';  
        $rpc->parent = 'tracker';
        $rpc->enabled = 0; 
        $rpc->help = 'Get information about one tracker.';
        $rpc->profile = '';
        if (!$rpcid = $DB->insert_record('mnet_rpc', $rpc)){
            echo $OUTPUT->notification('Error installing tracker_cascade RPC calls.');
            $result = false;
        }
        $rpcmap->rpcid = $rpcid;
        $DB->insert_record('mnet_service2rpc', $rpcmap);

        $rpc->function_name = 'tracker_rpc_post_issue';
        $rpc->xmlrpc_path = 'mod/tracker/rpclib.php/tracker_rpc_post_issue';
        $rpc->parent_type = 'mod';  
        $rpc->parent = 'tracker';
        $rpc->enabled = 0; 
        $rpc->help = 'Cascades an issue.';
        $rpc->profile = '';
        if (!$rpcid = $DB->insert_record('mnet_rpc', $rpc)){
            echo $OUTPUT->notification('Error installing tracker_cascade RPC calls.');
            $result = false;
        }
        $rpcmap->rpcid = $rpcid;
        $DB->insert_record('mnet_service2rpc', $rpcmap);
    }
    
    return $result;
}

/**
* a standard module API call for making some custom uninstall tasks 
*
*/
function tracker_uninstall(){
	global $DB;

    $return = true;
    
    // delete all tracker related mnet services and MNET bindings
    $service = $DB->get_record('mnet_service', array('name' => 'tracker_cascade'));
    if ($service){
        $DB->delete_records('mnet_host2service', array('serviceid' => $service->id));
        $DB->delete_records('mnet_service2rpc', array('serviceid' => $service->id));
        $DB->delete_records('mnet_rpc', array('parent' => 'tracker'));
        $DB->delete_records('mnet_service', array('name' => 'tracker_cascade'));
    }
    
    return $return;
}

function tracker_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $CFG, $DB;
	
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }
	
    require_course_login($course, true, $cm);
    
    $fileareas = array('issuedescription', 'issueresolution', 'issueattribute', 'issuecomment');
    if (!in_array($filearea, $fileareas)) {
        return false;
    }

    $itemid = (int)array_shift($args);

    if (!$tracker = $DB->get_record('tracker', array('id' => $cm->instance))) {
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_tracker/$filearea/$itemid/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    // finally send the file
    send_stored_file($file, 0, 0, false); // download MUST be forced - security!
}

/**
* Adds some overrides that invert role to profile mapping. This is done by role archetype 
* to help custom roles to adopt suitable behaviour.
*/
function tracker_setup_role_overrides(&$tracker, $context){
	global $DB, $USER;

	tracker_clear_role_overrides($context);
	
	assert(!$DB->get_records('role_capabilities', array('contextid' => $context->id)));

	$time = time();

	if ($tracker->supportmode == 'taskspread'){
		$overrides = array(
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'teacher',
				'capability' => 'mod/tracker:report',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'editingteacher',
				'capability' => 'mod/tracker:report',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'teacher',
				'capability' => 'mod/tracker:viewallissues',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'editingteacher',
				'capability' => 'mod/tracker:viewallissues',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'teacher',
				'capability' => 'mod/tracker:seeissues',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'editingteacher',
				'capability' => 'mod/tracker:seeissues',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'teacher',
				'capability' => 'mod/tracker:comment',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'editingteacher',
				'capability' => 'mod/tracker:comment',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'teacher',
				'capability' => 'mod/tracker:managepriority',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'editingteacher',
				'capability' => 'mod/tracker:managepriority',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'teacher',
				'capability' => 'mod/tracker:develop',
				'permission' => CAP_PREVENT,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'editingteacher',
				'capability' => 'mod/tracker:develop',
				'permission' => CAP_PREVENT,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'teacher',
				'capability' => 'mod/tracker:resolve',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'editingteacher',
				'capability' => 'mod/tracker:resolve',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'student',
				'capability' => 'mod/tracker:develop',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'student',
				'capability' => 'mod/tracker:report',
				'permission' => CAP_PREVENT,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'student',
				'capability' => 'mod/tracker:comment',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'student',
				'capability' => 'mod/tracker:managepriority',
				'permission' => CAP_PREVENT,
			),
		);
	} elseif ($tracker->supportmode == 'bugtracker'){
		$overrides = array(
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'teacher',
				'capability' => 'mod/tracker:report',
				'permission' => CAP_PREVENT,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'editingteacher',
				'capability' => 'mod/tracker:report',
				'permission' => CAP_PREVENT,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'teacher',
				'capability' => 'mod/tracker:viewallissues',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'editingteacher',
				'capability' => 'mod/tracker:viewallissues',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'teacher',
				'capability' => 'mod/tracker:seeissues',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'editingteacher',
				'capability' => 'mod/tracker:seeissues',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'teacher',
				'capability' => 'mod/tracker:comment',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'editingteacher',
				'capability' => 'mod/tracker:comment',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'teacher',
				'capability' => 'mod/tracker:develop',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'editingteacher',
				'capability' => 'mod/tracker:develop',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'teacher',
				'capability' => 'mod/tracker:resolve',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'editingteacher',
				'capability' => 'mod/tracker:resolve',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'student',
				'capability' => 'mod/tracker:develop',
				'permission' => CAP_PREVENT,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'student',
				'capability' => 'mod/tracker:report',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'student',
				'capability' => 'mod/tracker:comment',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'student',
				'capability' => 'mod/tracker:seeissues',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'student',
				'capability' => 'mod/tracker:viewallissues',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'student',
				'capability' => 'mod/tracker:resolve',
				'permission' => CAP_ALLOW,
			),
		);
	} elseif($tracker->supportmode == 'ticketting') { // User individual support
		$overrides = array(
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'teacher',
				'capability' => 'mod/tracker:report',
				'permission' => CAP_PREVENT,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'editingteacher',
				'capability' => 'mod/tracker:report',
				'permission' => CAP_PREVENT,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'teacher',
				'capability' => 'mod/tracker:viewallissues',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'editingteacher',
				'capability' => 'mod/tracker:viewallissues',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'teacher',
				'capability' => 'mod/tracker:seeissues',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'editingteacher',
				'capability' => 'mod/tracker:seeissues',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'teacher',
				'capability' => 'mod/tracker:comment',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'editingteacher',
				'capability' => 'mod/tracker:comment',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'teacher',
				'capability' => 'mod/tracker:develop',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'editingteacher',
				'capability' => 'mod/tracker:develop',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'teacher',
				'capability' => 'mod/tracker:resolve',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'editingteacher',
				'capability' => 'mod/tracker:resolve',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'student',
				'capability' => 'mod/tracker:develop',
				'permission' => CAP_PREVENT,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'student',
				'capability' => 'mod/tracker:report',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'student',
				'capability' => 'mod/tracker:comment',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'student',
				'capability' => 'mod/tracker:viewallissues',
				'permission' => CAP_PREVENT,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'student',
				'capability' => 'mod/tracker:seeissues',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'student',
				'capability' => 'mod/tracker:managepriority',
				'permission' => CAP_ALLOW,
			),
			array(
				'contextid' => $context->id,
				'rolearchetype' => 'student',
				'capability' => 'mod/tracker:resolve',
				'permission' => CAP_ALLOW,
			),
		);
	}
	
	foreach ($overrides as $ov){

		$overrideobj = (object) $ov;
		
		$roles = $DB->get_records('role', array('archetype' => $overrideobj->rolearchetype));

		foreach($roles as $r){
			$overrideobj->roleid = $r->id;			
			$overrideobj->timemodified = $time;
			$overrideobj->modifierid = $USER->id;		
			$DB->insert_record('role_capabilities', $overrideobj);
		}
	}
}

/**
* Remove all overrides on this context
*
*/
function tracker_clear_role_overrides($context){
	global $DB;
	
	$DB->delete_records('role_capabilities', array('contextid' => $context->id));
}

function tracker_preset_states(&$tracker){

	if ($tracker->supportmode == 'taskspread'){
		$tracker->enabledstates = ENABLED_OPEN | ENABLED_RESOLVED | ENABLED_WAITING | ENABLED_ABANDONNED;
	} elseif ($tracker->supportmode == 'bugtracker'){
		$tracker->enabledstates = ENABLED_ALL;
	} elseif ($tracker->supportmode == 'ticketting'){
		$tracker->enabledstates = ENABLED_OPEN | ENABLED_RESOLVING | ENABLED_RESOLVED | ENABLED_WAITING | ENABLED_ABANDONNED | ENABLED_VALIDATED;
	} else {
		if (is_array(@$tracker->stateprofile)){
			$tracker->enabledstates = array_reduce($tracker->stateprofile, 'tracker_ror', 0);
		}	
	}
}

function tracker_preset_params(&$tracker){
	global $DB;
	
	if ($tracker->supportmode == 'taskspread'){
		$tracker->thanksmessage = get_string('message_taskspread', 'tracker');
		$tracker->defaultassignee = 0;
	} elseif ($tracker->supportmode == 'bugtracker'){
		$tracker->thanksmessage = get_string('message_bugtracker', 'tracker');
	} elseif ($tracker->supportmode == 'ticketting'){
		if ($tracker->defaultassignee){
			$defaultassignee = $DB->get_record('user', array('id' => $tracker->defaultassignee), 'id, firstname, lastname');
			$tracker->thanksmessage = get_string('message_ticketting_preassigned', 'tracker', fullname($defaultassignee));
		} else {
			$tracker->thanksmessage = get_string('message_ticketting', 'tracker');
		}
	}
}