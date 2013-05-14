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

    return $DB->insert_record('tracker', $tracker);
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

	if (is_array($tracker->subtrackers)){
		$tracker->subtrackers = implode(',', $tracker->subtrackers);
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
            t.name,
            t.ticketprefix,
            ti.id,
            ti.trackerid,
            ti.summary,
            ti.reportedby,
            ti.datereported
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
