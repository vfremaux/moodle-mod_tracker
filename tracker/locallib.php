<?php

/**
* @package mod-tracker
* @category mod
* @author Clifford Tham, Valery Fremaux > 1.8
* @date 02/12/2007
*
* Library of internal functions and constants for module tracker
*/

/**
* includes and requires
*/
require_once $CFG->dirroot.'/mod/tracker/filesystemlib.php';
require_once $CFG->dirroot.'/lib/uploadlib.php';
require_once($CFG->dirroot.'/mod/tracker/mailtemplatelib.php');

// statusses
define('POSTED', 0);
define('OPEN', 1);
define('RESOLVING', 2);
define('WAITING', 3);
define('RESOLVED', 4);
define('ABANDONNED', 5);
define('TRANSFERED', 6);
define('TESTING', 7);
define('PUBLISHED', 8);
define('VALIDATED', 9);

// states && eventmasks
define('EVENT_POSTED', 0);
define('EVENT_OPEN', 1);
define('EVENT_RESOLVING', 2);
define('EVENT_WAITING', 4);
define('EVENT_RESOLVED', 8);
define('EVENT_ABANDONNED', 16);
define('EVENT_TRANSFERED', 32);
define('ON_COMMENT', 64);
define('EVENT_TESTING', 128);
define('EVENT_PUBLISHED', 256);

define('ALL_EVENTS', 255);

global $STATUSCODES;
global $STATUSKEYS;
$STATUSCODES = array(POSTED => 'posted', 
                    OPEN => 'open', 
                    RESOLVING => 'resolving', 
                    WAITING => 'waiting', 
                    RESOLVED => 'resolved', 
                    ABANDONNED => 'abandonned',
                    TRANSFERED => 'transfered',
                    TESTING => 'testing',
                    PUBLISHED => 'published');

$STATUSKEYS = array(POSTED => get_string('posted', 'tracker'), 
                    OPEN => get_string('open', 'tracker'), 
                    RESOLVING => get_string('resolving', 'tracker'), 
                    WAITING => get_string('waiting', 'tracker'), 
                    RESOLVED => get_string('resolved', 'tracker'), 
                    ABANDONNED => get_string('abandonned', 'tracker'),
                    TRANSFERED => get_string('transfered', 'tracker'),
                    TESTING => get_string('testing', 'tracker'),
                    PUBLISHED => get_string('published', 'tracker'));

/**
* loads all elements in memory
* @uses $CFG
* @uses $COURSE
* @param reference $tracker the tracker object
* @param reference $elementsobj
*/
function tracker_loadelements(&$tracker, &$elementsobj){
    global $COURSE, $CFG, $DB;

    /// first get shared elements
    $elements = $DB->get_records('tracker_element', array('course' => 0));
    if (!$elements) $elements = array();

    /// get course scope elements
    $courseelements = $DB->get_records('tracker_element', array('course' => $COURSE->id));
    if ($courseelements){
        $elements = array_merge($elements, $courseelements);
    }

    /// make a set of element objet with records
    if (!empty($elements)){
        foreach ($elements as $element){
            if ($element->type == ''){
                $elementsobj[$element->id] = new trackerelement($tracker, $element->id);
                $elementsobj[$element->id]->setoptionsfromdb();
            }
            else{
                // this get the options by the constructor
                include_once($CFG->dirroot.'/mod/tracker/classes/trackercategorytype/'.$element->type.'/'.$element->type.'.class.php');
                $constructorfunction = "{$element->type}element";
                $elementsobj[$element->id] = new $constructorfunction($tracker, $element->id);
            }
            $elementsobj[$element->id]->name = $element->name;
            $elementsobj[$element->id]->description = $element->description;
            $elementsobj[$element->id]->type = $element->type;
            $elementsobj[$element->id]->course = $element->course;
        }
    }
}

/**
* this implements an element factory
* makes a single element from a record if given an id, or a new element of a desired type
* @uses $CFG
* @param int $elementid
* @param string $type the type for creating a new element
* @return object
*/
function tracker_getelement(&$tracker, $elementid=null, $type=null){
    global $CFG, $DB;
    if ($elementid){
        $element = $DB->get_record('tracker_element', array('id' => $elementid));
        $elementtype = ($element) ? $element->type : $type ;

        if (!empty($element)){
            if ($element->type == ''){
                $elementobj = new trackerelement($tracker, $element->id);
                $elementobj->setoptionsfromdb();
            } else {
                include_once('classes/trackercategorytype/' . $element->type . '/'.$element->type.'.class.php');
                $constructorfunction = "{$elementtype}element";
                $elementobj = new $constructorfunction($tracker, $element->id);
            }
            $elementobj->name = $element->name;
            $elementobj->description = $element->description;
            $elementobj->type = $element->type;
            $elementobj->course = $element->course;
        }
    }
    else{
        if ($type == ''){
            $elementobj = new trackerelement($tracker);
            $elementobj->setoptionsfromdb();
        }
        else{
            include_once('classes/trackercategorytype/' . $type . '/'.$type.'.class.php');
            $constructorfunction = "{$type}element";
            $elementobj = new $constructorfunction($tracker);
        }
    }   
    return $elementobj;
}

/**
* get all available types which are plugins in classes/trackercategorytype
* @uses $CFG
* @return an array of known element types
*/
function tracker_getelementtypes(){
    global $CFG;
    $typedir = "{$CFG->dirroot}/mod/tracker/classes/trackercategorytype";
    $DIR = opendir($typedir);
    while($entry = readdir($DIR)){
        if (strpos($entry, '.') === 0) continue;
        if ($entry == 'CVS') continue;
        if (!is_dir("$typedir/$entry")) continue;
        $types[] = $entry;
    }
    return $types;
}

/**
* tells if at least one used element is a file element
* @param int $trackerid the current tracker
*/
function tracker_requiresfile($trackerid){
    global $CFG, $DB;

    $sql = "
        SELECT 
            COUNT(*)
        FROM 
            {tracker_element} e,
            {tracker_elementused} eu
        WHERE 
            eu.elementid = e.id AND 
            eu.trackerid = {$trackerid} AND
            e.type = 'file'
    ";
    $count = $DB->count_records_sql($sql);
    return $count;
}

/**
* loads elements in a reference
* @param int $trackerid the current tracker
* @param reference a reference to an array of used elements
*/
function tracker_loadelementsused(&$tracker, &$used){
    global $CFG, $DB;

    $sql = "
        SELECT 
            e.*,
            eu.id AS usedid,
            eu.sortorder, 
            eu.trackerid, 
            eu.canbemodifiedby, 
            eu.active
        FROM 
            {tracker_element} e,
            {tracker_elementused} eu
        WHERE 
            eu.elementid = e.id AND 
            eu.trackerid = {$tracker->id} 
        ORDER BY 
            eu.sortorder ASC
    ";
    $elements = $DB->get_records_sql($sql);
    $used = array();
    if (!empty($elements)){
        foreach ($elements as $element){
            if ($element->type == ''){
                $used[$element->id] = new trackerelement($tracker, $element->id);
                $used[$element->id]->setoptionsfromdb();
            } else {
                include_once($CFG->dirroot.'/mod/tracker/classes/trackercategorytype/' . $element->type . '/'.$element->type.'.class.php');
                $constructorfunction = "{$element->type}element";
                $used[$element->id] = new $constructorfunction($tracker, $element->id);
            }
            $used[$element->id]->usedid = $element->usedid;
            $used[$element->id]->name = $element->name;
            $used[$element->id]->description = $element->description;
            $used[$element->id]->type = $element->type;
            $used[$element->id]->course = $element->course;
            $used[$element->id]->sortorder = $element->sortorder;                       
            $used[$element->id]->active = $element->active;                       
        }
    }
}   

/**
* quite the same as above, but not loading objects, and
* mapping hash keys by "name"
* @param int $trackerid
*
*/
function tracker_getelementsused_by_name(&$tracker){
    global $CFG, $DB;

    $sql = "
        SELECT 
            e.name,
            e.description,
            e.type,
            eu.id AS usedid,
            eu.sortorder, 
            eu.trackerid, 
            eu.canbemodifiedby, 
            eu.active
        FROM 
            {tracker_element} e,
            {tracker_elementused} eu
        WHERE 
            eu.elementid = e.id AND 
            eu.trackerid = {$tracker->id}
        ORDER BY 
            eu.sortorder ASC
    ";
    if (!$usedelements = $DB->get_records_sql($sql)){
        return array();
    }
    return $usedelements;
}

/**
* checks if an element is used somewhere in the tracker. It must be in used list
* @param int $trackerid the current tracker
* @param int $elementid the element
* @return boolean
*/
function tracker_iselementused($trackerid, $elementid){
	global $DB;
	
    $inusedelements = $DB->count_records_select('tracker_elementused', 'elementid = ' . $elementid . ' AND trackerid = ' . $trackerid);  
    return $inusedelements;
}

/**
* print additional user defined elements in several contexts
* @param int $trackerid the current tracker
* @param array $fields the array of fields to be printed
*/
function tracker_printelements(&$tracker, $fields=null, $dest=false){
    tracker_loadelementsused($tracker, $used);
    if (!empty($used)){
        if (!empty($fields)){
            foreach ($used as $element){
                if (isset($fields[$element->id])){
                    foreach($fields[$element->id] as $value){
                        $element->value = $value;
                    }
                }
            }
        }
        foreach ($used as $element){

        	if (!$element->active) continue;

            echo '<tr>';
            echo '<td align="right" valign="top">';
            echo '<b>' . format_string($element->description) . ':</b>';
            echo '</td>';
            echo '<td align="left" colspan="3">';
            if ($dest == 'search'){
                $element->viewsearch();
            }
            elseif ($dest == 'query'){
                $element->viewquery();
            }
            else{
                $element->view(true);
            }
            echo '</td>';
            echo '</tr>';
        }
    }
}


/**
* print additional user defined elements in several contexts
* @param int $trackerid the current tracker
* @param array $fields the array of fields to be printed
*
function tracker_printelements(&$mform, &$tracker, $fields=null, $dest=false){
    tracker_loadelementsused($tracker, $used);
    if (!empty($used)){
        if (!empty($fields)){
            foreach ($used as $element){
                if (isset($fields[$element->id])){
                    foreach($fields[$element->id] as $value){
                        $element->value = $value;
                    }
                }
            }
        }
        foreach ($used as $element){

        	if (!$element->active) continue;
        	
            if ($dest == 'search'){
                $element->viewsearch($mform);
            }
            elseif ($dest == 'query'){
                $element->viewquery($mform);
            } else {
            	$element->view($mform);
            } 
        }
    }
}
*/

/// Search engine 

/**
* constructs an adequate search query, based on both standard and user defined 
* fields. 
* @param int $trackerid
* @param array $fields
* @return an object where both the query for counting and the query for getting results are
* embedded. 
*/
function tracker_constructsearchqueries($trackerid, $fields, $own = false){
    global $CFG, $USER, $DB;

    $keys = array_keys($fields);

    //Check to see if we are search using elements as a parameter.  
    //If so, we need to include the table tracker_issueattribute in the search query
    $elementssearch = false;
    foreach ($keys as $key){
        if (is_numeric($key)){
            $elementssearch = true;
        }
    }
    $elementsSearchClause = ($elementssearch) ? " {tracker_issueattribute} AS ia, " : '' ;

    $elementsSearchConstraint = '';
    foreach ($keys as $key){
        if ($key == 'id'){
            $elementsSearchConstraint .= ' AND  (';
            foreach ($fields[$key] as $idtoken){
                $elementsSearchConstraint .= (empty($idquery)) ? 'i.id =' . $idtoken : ' OR i.id = ' . $idtoken ;
            }
            $elementsSearchConstraint .= ')';
        }

        if ($key == 'datereported' && array_key_exists('checkdate', $fields) ){
            $datebegin = $fields[$key][0];
            $dateend = $datebegin + 86400;
            $elementsSearchConstraint .= " AND i.datereported > {$datebegin} AND i.datereported < {$dateend} ";
        }

        if ($key == 'description'){
            $tokens = explode(' ', $fields[$key][0], ' ');
            foreach ($tokens as $token){
                $elementsSearchConstraint .= " AND i.description LIKE '%{$descriptiontoken}%' ";
            }
        }

        if ($key == 'reportedby'){
            $elementsSearchConstraint .= ' AND i.reportedby = ' . $fields[$key][0];
        }

        if ($key == 'summary'){
            $summarytokens = explode(' ', $fields[$key][0]);
            foreach ($summarytokens as $summarytoken){
                $elementsSearchConstraint .= " AND i.summary LIKE '%{$summarytoken}%'";
            }
        }

        if (is_numeric($key)){
            foreach($fields[$key] as $value){
                $elementsSearchConstraint .= ' AND i.id IN (SELECT issue FROM {tracker_issueattribute} WHERE elementdefinition=' . $key . ' AND elementitemid=' . $value . ')';
            }
        }
    }
    if ($own == false){
        $sql->search = "
            SELECT DISTINCT 
                i.id, 
                i.trackerid, 
                i.summary, 
                i.datereported, 
                i.reportedby, 
                i.assignedto, 
                i.status,
                COUNT(cc.userid) AS watches,
                u.firstname, 
                u.lastname
            FROM 
                {user} AS u, 
                $elementsSearchClause
                {tracker_issue} i
            LEFT JOIN
                {tracker_issuecc} cc
            ON
                cc.issueid = i.id           
            WHERE 
                i.trackerid = {$trackerid} AND 
                i.reportedby = u.id $elementsSearchConstraint
            GROUP BY
                i.id, 
                i.trackerid, 
                i.summary, 
                i.datereported, 
                i.reportedby, 
                i.assignedto, 
                i.status, 
                u.firstname,
                u.lastname
        ";
        $sql->count = "
            SELECT COUNT(DISTINCT 
                (i.id)) as reccount
            FROM 
                {tracker_issue} i
                $elementsSearchClause
            WHERE 
                i.trackerid = {$trackerid} 
                $elementsSearchConstraint
        ";
    } else {
        $sql->search = "
            SELECT DISTINCT 
                i.id, i.trackerid, i.summary, i.datereported, i.reportedby, i.assignedto, i.status,
                COUNT(cc.userid) AS watches
            FROM 
                $elementsSearchClause
                {tracker_issue} i
            LEFT JOIN
                {tracker_issuecc} cc
            ON
                cc.issueid = i.id           
            WHERE 
                i.trackerid = {$trackerid} AND 
                i.reportedby = {$USER->id} 
                $elementsSearchConstraint
            GROUP BY
                i.id, i.trackerid, i.summary, i.datereported, i.reportedby, i.assignedto, i.status
        ";
        $sql->count = "
            SELECT COUNT(DISTINCT 
                (i.id)) as reccount
            FROM 
                {tracker_issue} i
                $elementsSearchClause
            WHERE 
                i.trackerid = {$trackerid} AND
                i.reportedby = $USER->id
                $elementsSearchConstraint
        ";
    }
    return $sql;    
}

/**
* analyses the POST parameters to extract values of additional elements
* @return an array of field descriptions
*/
function tracker_extractsearchparametersfrompost(){
    $count = 0;
    $fields = array();
    $issuenumber = optional_param('issueid', '', PARAM_INT);
    if (!empty ($issuenumber)){
        $issuenumberarray = explode(',', $issuenumber);
        foreach ($issuenumberarray as $issueid){
            if (is_numeric($issueid)){
                $fields['id'][] = $issueid;
            }
            else{
                print_error('errorbadlistformat', 'tracker', 'view.php?id=' . $this->tracker_getcoursemodule() . '&what=search');
            }
        }
     } else {
        $checkdate = optional_param('checkdate', 0, PARAM_INT);
        if ($checkdate){
            $month = optional_param('month', '', PARAM_INT);
            $day = optional_param('day', '', PARAM_INT);
            $year = optional_param('year', '', PARAM_INT);
            if (!empty($month) && !empty($day) && !empty($year)){
                $datereported = make_timestamp($year, $month, $day);
                $fields['datereported'][] = $datereported;
            }
        }
        $description = optional_param('description', '', PARAM_CLEANHTML);
        if (!empty($description)){  
            $fields['description'][] = stripslashes($description);
        }
        $reportedby = optional_param('reportedby', '', PARAM_INT);
        if (!empty($reportedby)){   
            $fields['reportedby'][] = $reportedby;
        }
        $summary = optional_param('summary', '', PARAM_TEXT);
        if (!empty($summary)){  
            $fields['summary'][] = $summary;
        }
        $keys = array_keys($_POST);                         // get the key value of all the fields submitted
        $elementkeys = preg_grep('/element./' , $keys);     // filter out only the element keys
        foreach ($elementkeys as $elementkey){
            preg_match('/element(.*)$/', $elementkey, $elementid);
            if (!empty($_POST[$elementkey])){
                if (is_array($_POST[$elementkey])){
                    foreach ($_POST[$elementkey] as $elementvalue){
                        $fields[$elementid[1]][] = $elementvalue;
                    }
                } else {
                    $fields[$elementid[1]][] = $_POST[$elementkey];
                }
            }
        }
    }
    return $fields;
}

/**
* given a query object, and a description of additional fields, stores 
* all the query description to database.  
* @uses $USER
* @param object $query
* @param array $fields
* @return the inserted or updated queryid
*/
function tracker_savesearchparameterstodb($query, $fields){
    global $USER, $DB;
    $query->userid = $USER->id;
    $query->published = 0;
    $query->fieldnames = '';
    $query->fieldvalues = '';
    if (!empty($fields)){
        $keys = array_keys($fields);
        if (!empty($keys)){
            foreach ($keys as $key){
                foreach($fields[$key] as $value){
                    if (empty($query->fieldnames)){
                        $query->fieldnames = $key;
                        $query->fieldvalues = $value;
                    } else {
                        $query->fieldnames = $query->fieldnames . ', ' . $key;
                        $query->fieldvalues = $query->fieldvalues . ', '  . $value;
                    }
                }
            }       
        }
    }
    if (!isset($query->id)) {           //if not given a $queryid, then insert record
        $queryid = $DB->insert_record('tracker_query', $query);
    }
    else {                      //otherwise, update record
        $queryid = $DB->update_record('tracker_query', $query, true);
    }   
    return $queryid;        
}

/**
* prints the human understandable search query form
* @param array $fields
*/
function tracker_printsearchfields($fields){
	global $DB;
	
    foreach($fields as $key => $value){
        switch(trim($key)){
            case 'datereported' :
                if (!function_exists('trk_userdate')){
                    function trk_userdate(&$a){
                        $a = userdate($a);
                        $a = preg_replace("/, \\d\\d:\\d\\d/", '', $a);
                    }
                }
                array_walk($value, 'trk_userdate');
                $strs[] = get_string($key, 'tracker') . ' '.get_string('IN', 'tracker')." ('".implode("','", $value) . "')";
                break;
            case 'summary' :
                $strs[] =  "('".implode("','", $value) ."') ".get_string('IN', 'tracker').' '.get_string('summary', 'tracker');
                break;
            case 'description' :
                $strs[] =  "('".implode("','", $value) ."') ".get_string('IN', 'tracker').' '.get_string('description');
                break;
            case 'reportedby' :
                $users = $DB->get_records_list('user', array('id' => implode(',',$value)), 'lastname', 'id,firstname,lastname');
                $reporters = array();
                if($users){
                    foreach($users as $user){
                        $reporters[] = fullname($user);
                    }
                }
                $reporterlist = implode ("', '", $reporters);
                $strs[] = get_string('reportedby', 'tracker').' '.get_string('IN', 'tracker')." ('".$reporterlist."')";
                break;
            default : 
                $strs[] = get_string($key, 'tracker') . ' '.get_string('IN', 'tracker')." ('".implode("','", $value) . "')";
        }
    }
    return implode (' '.get_string('AND', 'tracker').' ', $strs);
}

/**
*
*
*/
function tracker_extractsearchparametersfromdb($queryid=null){
	global $DB;
	
    if (!$queryid){
        $queryid = optional_param('queryid', '', PARAM_INT);
    }
    $query_record = $DB->get_record('tracker_query', array('id' => $queryid));
    $fields = null;
    if (!empty($query_record)){
        $fieldnames = explode(',', $query_record->fieldnames);
        $fieldvalues = explode(',', $query_record->fieldvalues);
        $count = 0;
        if (!empty($fieldnames)){
            foreach ($fieldnames as $fieldname){
                $fields[trim($fieldname)][] = trim($fieldvalues[$count]);
                $count++;
            }
        }
    }
    else{
        error ("Invalid query id: " . $queryid);
    }
    return $fields;
}

/**
* set a cookie with search information
* @return boolean
*/
function tracker_setsearchcookies($fields){
    $success = true;
    if (is_array($fields)){
        $keys = array_keys($fields);
        foreach ($keys as $key){
            $cookie = '';
            foreach ($fields[$key] as $value){
                if (empty($cookie)){
                    $cookie = $cookie . $value;
                }       
                else{
                    $cookie = $cookie . ', ' . $value;
                }
            }
            $result = setcookie("moodle_tracker_search_" . $key, $cookie);          
            $success = $success && $result;
        }
    }
    else{
        $success = false;
    }
    return $success;    
}

/**
* get last search parameters from use cookie
* @uses $_COOKIE
* @return an array of field desriptions
*/
function tracker_extractsearchcookies(){
    $keys = array_keys($_COOKIE);                                           // get the key value of all the cookies
    $cookiekeys = preg_grep('/moodle_tracker_search./' , $keys);            // filter all search cookies
    $fields = null;
    foreach ($cookiekeys as $cookiekey){
        preg_match('/moodle_tracker_search_(.*)$/', $cookiekey, $fieldname);
        $fields[$fieldname[1]] = explode(', ', $_COOKIE[$cookiekey]);
    }
    return $fields;
}


/**
* clear the current search
* @uses _COOKIE
* @return boolean true if succeeded
*/
function tracker_clearsearchcookies(){
    $success = true;
    $keys = array_keys($_COOKIE);                                           // get the key value of all the cookies
    $cookiekeys = preg_grep('/moodle_tracker_search./' , $keys);            // filter all search cookies
    foreach ($cookiekeys as $cookiekey){
        $result = setcookie($cookiekey, '');
        $success = $success && $result;
    }   

    return $success;        
}

/**
* settles data for memoising current search context
* @uses $CFG
* @param int $trackerid
* @param int $cmid
*/
function tracker_searchforissues(&$tracker, $cmid){
    global $CFG;
    
    tracker_clearsearchcookies($tracker->id);
    $fields = tracker_extractsearchparametersfrompost($tracker->id);
    $success = tracker_setsearchcookies($fields);
    if ($success){
        if ($tracker->supportmode == 'bugtracker'){
            redirect ("view.php?id={$cmid}&amp;view=view&amp;page=browse");
        } else { 
            redirect("view.php?id={$cmid}&amp;view=view&amp;page=mytickets");
        }
    } else {
        print_error('errorcookie', 'tracker', '', $cookie);
    }
}

/**
* get how many issues in this tracker
* @uses $CFG
* @param int $trackerid
* @param int $status if status is positive or null, filters by status
*/
function tracker_getnumissuesreported($trackerid, $status='*', $reporterid = '*', $resolverid='*', $developerids='', $adminid='*'){ 
    global $CFG, $DB;
    
    $statusClause = ($status !== '*') ? " AND i.status = $status " : '' ;
    $reporterClause = ($reporterid != '*') ? " AND i.reportedby = $reporterid " : '' ;
    $resolverClause = ($resolverid != '*') ? " AND io.userid = $resolverid " : '' ;
    $developerClause = ($developerids != '') ? " AND io.userid IN ($developerids) " : '' ;
    $adminClause = ($adminid != '*') ? " AND io.bywhomid IN ($adminid) " : '' ;

    $sql = "
        SELECT
            COUNT(DISTINCT(i.id))
        FROM
            {tracker_issue} i
        LEFT JOIN
            {tracker_issueownership} io
        ON 
            i.id = io.issueid
        WHERE
            i.trackerid = {$trackerid}
            $statusClause
            $reporterClause
            $developerClause
            $resolverClause
            $adminClause
    ";
    return $DB->count_records_sql($sql); 
}

//// User related 

/**
* get available managers/tracker administrators
* @param object $context
*/
function tracker_getadministrators($context){
    return get_users_by_capability($context, 'mod/tracker:manage', 'u.id,firstname,lastname,picture,email', 'lastname', '', '', '', '', false);
}

/**
* get available resolvers
* @param object $context
*/
function tracker_getresolvers($context){
    return get_users_by_capability($context, 'mod/tracker:resolve', 'u.id,firstname,lastname,picture,email', 'lastname', '', '', '', '', false);
}

/**
* get actual reporters from records
* @uses $CFG
* @param int $trackerid
*/
function tracker_getreporters($trackerid){
    global $CFG, $DB;
    
    $sql = "
        SELECT
            DISTINCT(reportedby) AS id,
            u.firstname,
            u.lastname
        FROM
            {tracker_issue} i,
            {user} u
        WHERE
            i.reportedby = u.id AND
            i.trackerid = ?
    ";
    return $DB->get_records_sql($sql, array($trackerid));
}

/**
*
*
*/
function tracker_getdevelopers($context){
    return get_users_by_capability($context, 'mod/tracker:develop', 'u.id,firstname,lastname,picture,email', 'lastname', '', '', '', '', false);
}

/**
* get the assignees of a manager
*
*/
function tracker_getassignees($userid){
    global $CFG, $DB;
    
    $sql = "
        SELECT DISTINCT 
            u.id, 
            u.firstname, 
            u.lastname, 
            u.picture, 
            u.email, 
            u.emailstop, 
            u.maildisplay,
            COUNT(i.id) as issues
        FROM
            {tracker_issue} i,
            {user} u
        WHERE
            i.assignedto = u.id AND
            i.bywhomid = ?
        GROUP BY
            u.id, 
            u.firstname, 
            u.lastname, 
            u.picture, 
            u.email, 
            u.emailstop, 
            u.maildisplay
    ";
    return $DB->get_records_sql($sql, array($userid));
}

/**
* submits an issue in the current tracker
* @uses $CFG
* @param int $trackerid the current tracker
*/
function tracker_submitanissue(&$tracker){
    global $CFG, $DB;
    
    $issue->datereported = required_param('datereported', PARAM_INT);
    $issue->summary = required_param('summary', PARAM_TEXT);
    $issue->description = addslashes(required_param('description', PARAM_CLEANHTML));
    $issue->format = addslashes(required_param('format', PARAM_CLEANHTML));
    $issue->assignedto = $tracker->defaultassignee;
    $issue->bywhomid = 0;
    $issue->trackerid = $tracker->id;
    $issue->status = POSTED;
    $issue->reportedby = required_param('reportedby', PARAM_INT);

    // fetch max actual priority
    $maxpriority = $DB->get_field_select('tracker_issue', 'MAX(resolutionpriority)', " trackerid = {$tracker->id} GROUP BY trackerid ");
    $issue->resolutionpriority = $maxpriority + 1;

    $issue->id = $DB->insert_record('tracker_issue', $issue);
    if ($issue->id){
        tracker_recordelements($issue);
        // if not CCed, the assignee should be
        tracker_register_cc($tracker, $issue, $issue->reportedby);
        return $issue;
    } else {
         print_error('errorrecordissue', 'tracker');
    }
}

/**
* fetches all issues a user is assigned to as resolver
* @uses $USER
* @param int $trackerid the current tracker
* @param int $userid an eventual userid
*/
function tracker_getownedissuesforresolve($trackerid, $userid = null){
    global $USER, $DB;
    if (empty($userid)){
        $userid = $USER->id;
    }
    return $DB->get_records_select('tracker_issue', "trackerid = {$trackerid} AND assignedto = {$userid} ");
}

/**
* stores in database the element values
* @uses $CFG
* @param object $issue
*/
function tracker_recordelements(&$issue){
    global $CFG, $COURSE, $DB;
    
    $keys = array_keys($_POST);                 // get the key value of all the fields submitted
    $keys = preg_grep('/element./' , $keys);    // filter out only the element keys

    $filekeys = array_keys($_FILES);                 // get the key value of all the fields submitted
    $filekeys = preg_grep('/element./' , $filekeys);    // filter out only the element keys    

    $keys = array_merge($keys, $filekeys);
    foreach ($keys as $key){
        preg_match('/element(.*)$/', $key, $elementid);
        $elementname = $elementid[1];
        $sql = "
            SELECT 
              e.id as elementid,
              e.type as type
            FROM
                {tracker_elementused} eu,
                {tracker_element} e
            WHERE
                eu.elementid = e.id AND
                e.name = ? AND
                eu.trackerid = ? 
        ";
        $attribute = $DB->get_record_sql($sql, array($elementname, $issue->trackerid));
        $attribute->timemodified = $issue->datereported;
        if (is_array(@$_POST[$key])){
	        $values = optional_param_array($key, '', PARAM_TEXT);
	    } else {
	        $values = optional_param($key, '', PARAM_TEXT);
	    }
        $attribute->issueid = $issue->id;
        $attribute->trackerid = $issue->trackerid;
        /// For those elements where more than one option can be selected
        if (is_array($values)){
            foreach ($values as $value){
                $attribute->elementitemid = $value;
                $attributeid = $DB->insert_record('tracker_issueattribute', $attribute);
                if (!$attributeid){
                    print_error('erroraddissueattribute', 'tracker', '', 1);
                }
            }
        } else {  //For the rest of the elements that can only support one answer
            if ($attribute->type != 'file'){
                $attribute->elementitemid = str_replace("'", "''", $values);
                $attributeid = $DB->insert_record('tracker_issueattribute', $attribute);    
	            if (empty($attributeid)){
	                print_error('erroraddissueattribute', 'tracker', '', 2);
	            }
            } else {
				$fs = get_file_storage();
				if (!empty($_FILES[$key]['name'])){
				 
					// Prepare file record object
					$context = context_module::instance($issue->trackerid);
					$fileinfo = array(
					    'contextid' => $context->id, // ID of context
					    'component' => 'mod_tracker',     // usually = table name
					    'filearea' => 'attachment',     // usually = table name
					    'itemid' => $issue->id,               // usually = ID of row in table
					    'filepath' => '/',           // any path beginning and ending in /
					    'filename' => $_FILES[$key]['name']); // any filename
					    
					// Get previous file and delete it
					if ($file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], 
					        $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename'])){
					    $file->delete();
					}
					    				 
					$fs->create_file_from_pathname($fileinfo, $_FILES[$key]['tmp_name']);
					$attribute->elementitemid = $_FILES[$key]['name'];
	                $attributeid = $DB->insert_record('tracker_issueattribute', $attribute);
	            }
            }

        }   
    }           
}

/**
* clears element recordings for an issue
* @TODO check if it is really used
* @param int $issueid the issue
*/
function tracker_clearelements($issueid){
    global $CFG, $COURSE, $DB;

    if (!$issue = $DB->get_record('tracker_issue', array('id' => "$issueid"))){
        return;
    }

    // find all files elements to protect
   $sql = "
            SELECT
                e.id,
                e.type
            FROM
                {tracker_element} e,
                {tracker_elementused} eu
            WHERE
                e.id = eu.elementid AND
                e.type = 'file' AND
                eu.trackerid = {$issue->trackerid}
    ";

    $nofileclause = '';
    if($fileelements = $DB->get_records_sql($sql)){
        $fileelementlist = implode("','", array_keys($fileelements));
        $nofileclause = " AND elementid NOT IN ('$fileelementlist') ";
    }
    if (!$DB->delete_records_select('tracker_issueattribute', "issueid = $issueid $nofileclause")){
        print_error('errorcannotlearelementsforissue', 'tracker', $issueid);
    }

    $storebase = "{$COURSE->id}/moddata/tracker/{$issue->trackerid}/{$issue->id}";

    // remove all deleted file attachements
    $keys = array_keys($_POST);
    $deletefilekeys = preg_grep('/deleteelement./' , $keys);    // filter out only the deleteelement keys    

    if (!empty($deletefilekeys)){
        foreach($deletefilekeys as $deletedkey){
            if (preg_match("/deleteelement(.*)$/", $deletedkey, $matches)){
                $elementname = $matches[1];
                $element = $DB->get_record('tracker_element', array('name' => $elementname));
                if ($elementitem = $DB->get_record('tracker_issueattribute', array('elementid' => $element->id, 'issueid' => $issueid))){
                    if (!empty($elementitem->elementitemid)){                    	
						$fs = get_file_storage();
						$context = context_module::instance($issue->trackerid);
						// Prepare file record object
						$fileinfo = array(
						    'component' => 'mod_tracker',
						    'filearea' => 'attachment',     // usually = table name
						    'itemid' => $issue->id,               // usually = ID of row in table
						    'contextid' => $context->id, // ID of context
						    'filepath' => '/',           // any path beginning and ending in /
						    'filename' => $elementitem->value); // any filename
						 
						// Get file
						$file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], 
						        $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
						 
						// Delete it if it exists
						if ($file) {
						    $file->delete();
						}
					}
                    $DB->delete_records('tracker_issueattribute', array('id' => $elementitem->id));
                }
            }
        }
    }

    // remove all reloaded files
    $keys = array_keys($_FILES);
    $reloadedfilekeys = preg_grep('/element./' , $keys);    // filter out only the reloaded element keys    
    if (!empty($reloadedfilekeys)){
        foreach($reloadedfilekeys as $reloadedkey){
        	if (!empty($_FILES[$reloadedkey]['name'])){ // file is reloaded with another entry
	            if (preg_match("/element(.*)$/", $reloadedkey, $matches)){
	                $elementname = $matches[1];
	                $element = $DB->get_record('tracker_element', array('name' => $elementname));
	                if ($elementitem = $DB->get_record('tracker_issueattribute', array('elementid' => $element->id, 'issueid' => $issueid))){
	                    if (!empty($elementitem->elementitemid)){
	                        filesystem_delete_file($storebase.'/'.$elementitem->elementitemid);
	                    }
	                    $DB->delete_records('tracker_issueattribute', array('id' => $elementitem->id));
	                }
	            }
	        }
        }
    }
}

/**
* adds an error css marker in case of matching error
* @param array $errors the current error set
* @param string $errorkey 
*/
if (!function_exists('print_error_class')){
    function print_error_class($errors, $errorkeylist){
        if ($errors){
            foreach($errors as $anError){
                if ($anError->on == '') continue;
                if (preg_match("/\\b{$anError->on}\\b/" ,$errorkeylist)){
                    echo " class=\"formerror\" ";
                    return;
                }
            }        
        }
    }
}

/**
* registers a user as cced for an issue in a tracker
* @param reference $tracker the current tracker
* @param reference $issue the issue to watch
* @param int $userid the cced user's ID
*/
function tracker_register_cc(&$tracker, &$issue, $userid){
	global $DB;
	
    if ($userid && !$DB->get_record('tracker_issuecc', array('trackerid' => $tracker->id, 'issueid' => $issue->id, 'userid' => $userid))){
        // Add new the assignee as new CC !!
        // we do not discard the old one as he may be still concerned
        $eventmask = 127;
        if ($userprefs = $DB->get_record('tracker_preferences', array('trackerid' => $tracker->id, 'userid' => $userid, 'name' => 'eventmask'))){
            $eventmask = $userprefs->value;
        }
        $cc->trackerid = $tracker->id;
        $cc->issueid = $issue->id;
        $cc->userid = $userid;
        $cc->events = $eventmask;
        $DB->insert_record('tracker_issuecc', $cc);
    }    

}

/**
* a local version of the print user command that fits  better to the tracker situation
* @uses $COURSE
* @uses $CFG
* @param object $user the user record
*/
function tracker_print_user($user){
    global $COURSE, $CFG, $OUTPUT;

    if ($user){
        echo $OUTPUT->user_picture ($user, array('courseid' => $COURSE->id, 'size' => 25));
        if ($CFG->messaging){
            echo "&nbsp;<a href=\"$CFG->wwwroot/user/view.php?id={$user->id}&amp;course={$COURSE->id}\">".fullname($user)."</a> <a href=\"\" onclick=\"this.target='message'; return openpopup('/message/discussion.php?id={$user->id}', 'message', 'menubar=0,location=0,scrollbars,status,resizable,width=400,height=500', 0);\" ><img src=\"".$OUTPUT->pix_url('t/message', 'core')."\"></a>";
        } elseif (!$user->emailstop && $user->maildisplay){
            echo "&nbsp;<a href=\"$CFG->wwwroot/user/view.php?id={$user->id}&amp;course={$COURSE->id}\">".fullname($user)."</a> <a href=\"mailto:{$user->email}\"><img src=\"".$OUTPUT->pix_url('t/mail', 'core')."\"></a>";
        } else {
            echo '&nbsp;'.fullname($user);
        }
    }
}

/**
* prints comments for the given issue
* @uses $CFG
* @param int $issueid
*/
function tracker_printcomments($issueid){
    global $CFG, $DB;
    
    $comments = $DB->get_records('tracker_issuecomment', array('issueid' => $issueid), 'datecreated');
    if ($comments){
        foreach ($comments as $comment){
            $user = $DB->get_record('user', array('id' => $comment->userid));
            echo '<tr>';
            echo '<td valign="top" class="commenter" width="30%">';
            tracker_print_user($user);
            echo '<br/>';
            echo '<span class="timelabel">'.userdate($comment->datecreated).'</span>';
            echo '</td>';
            echo '<td colspan="3" valign="top" align="left" class="comment">';
            echo $comment->comment;
            echo '</td>';
            echo '</tr>';
        }
    }
}

/**
* get list of possible parents. Note that none can be in the subdependancies.
* @uses $CFG
* @param int $trackerid
* @param int $issueid
*/
function tracker_getpotentialdependancies($trackerid, $issueid){
    global $CFG, $DB;

    $subtreelist = tracker_get_subtree_list($trackerid, $issueid);
    $subtreeClause = (!empty($subtreelist)) ? "AND i.id NOT IN ({$subtreelist}) " : '' ;

    $sql = "
       SELECT
          i.id,
          id.parentid,
          id.childid as isparent,
          summary
       FROM
          {tracker_issue} i
       LEFT JOIN
          {tracker_issuedependancy} id
       ON
          i.id = id.parentid
       WHERE
          i.trackerid = {$trackerid} AND
          ((id.childid IS NULL) OR (id.childid = $issueid)) AND
          ((id.parentid != $issueid) OR (id.parentid IS NULL)) AND
          i.id != $issueid 
          $subtreeClause
       GROUP BY 
          i.id, 
          id.parentid, 
          id.childid, 
          summary
    ";
    // echo $sql;
    return $DB->get_records_sql($sql);
}

/**
* get the full list of dependencies in a tree // revamped from techproject/treelib.php
* @param table the table-tree
* @param id the node from where to start of
* @return a comma separated list of nodes
*/
function tracker_get_subtree_list($trackerid, $id){
	global $DB;
	
    $res = $DB->get_records_menu('tracker_issuedependancy', array('parentid' => $id), '', 'id,childid');
    $ids = array();
    if (is_array($res)){
        foreach(array_values($res) as $aSub){
            $ids[] = $aSub;
            $subs = tracker_get_subtree_list($trackerid, $aSub);
            if (!empty($subs)) $ids[] = $subs;
        }
    }
    return(implode(',', $ids));
}

/**
* prints all childs of an issue treeshaped
* @uses $CFG
* @uses $STATUSCODES
* @uses $STATUS KEYS
* @param object $tracker 
* @param int $issueid 
* @param boolean $return if true, returns the HTML, prints it to output elsewhere
* @param int $indent the indent value
* @return the HTML
*/
function tracker_printchilds(&$tracker, $issueid, $return=false, $indent=''){
    global $CFG, $STATUSCODES, $STATUSKEYS, $DB;

    $str = '';
    $sql = "
       SELECT
          childid,
          summary,
          status
       FROM
          {tracker_issuedependancy} id,
          {tracker_issue} i
       WHERE
          i.id = id.childid AND
          id.parentid = {$issueid} AND
          i.trackerid = {$tracker->id}
    ";
    $res = $DB->get_records_sql($sql);
    if ($res){
        foreach($res as $aSub){
            $str .= "<span style=\"position : relative; left : {$indent}px\"><a href=\"view.php?a={$tracker->id}&amp;what=viewanissue&amp;issueid={$aSub->childid}\">".$tracker->ticketprefix.$aSub->childid.' - '.format_string($aSub->summary)."</a>";
            $str .= "&nbsp;<span class=\"status_".$STATUSCODES[$aSub->status]."\">".$STATUSKEYS[$aSub->status]."</span></span><br/>\n";
            $indent = $indent + 20;
            $str .= tracker_printchilds($tracker, $aSub->childid, true, $indent);
            $indent = $indent - 20;
        }
    }
    if ($return) return $str;
    echo $str;
}


/**
* prints all parents of an issue tree shaped
* @uses $CFG
* @uses $STATUSCODES
* @uses STATUSKEYS
* @param object $tracker 
* @param int $issueid 
* @return the HTML
*/
function tracker_printparents(&$tracker, $issueid, $return=false, $indent=''){
    global $CFG, $STATUSCODES, $STATUSKEYS, $DB;

    $str = '';
    $sql = "
       SELECT
          parentid,
          summary,
          status
       FROM
          {tracker_issuedependancy} id,
          {tracker_issue} i
       WHERE
          i.id = id.parentid AND
          id.childid = ? AND
          i.trackerid = ?
    ";
    $res = $DB->get_records_sql($sql, array($issueid, $tracker->id));
    if ($res){
        foreach($res as $aSub){
            $indent = $indent - 20;
            $str .= tracker_printparents($tracker, $aSub->parentid, true, $indent);
            $indent = $indent + 20;
            $str .= "<span style=\"position : relative; left : {$indent}px\"><a href=\"view.php?a={$tracker->id}&amp;what=viewanissue&amp;issueid={$aSub->parentid}\">".$tracker->ticketprefix.$aSub->parentid.' - '.format_string($aSub->summary)."</a>";
            $str .= "&nbsp;<span class=\"status_".$STATUSCODES[$aSub->status]."\">".$STATUSKEYS[$aSub->status]."</span></span><br/>\n";
        }
    }
    if ($return) return $str;
    echo $str;
}

/**
* return watch list for a user
* @uses $CFG
* @param int trackerid the current tracker
* @param int userid the user
*/
function tracker_getwatches($trackerid, $userid){
    global $CFG, $DB;

    $sql = "
        SELECT
            w.*,
            i.summary
        FROM
            {tracker_issuecc} w,
            {tracker_issue} i
        WHERE
            w.issueid = i.id AND
            i.trackerid = ? AND
            w.userid = ?
    ";
    $watches = $DB->get_records_sql($sql, array($trackerid, $userid));
    if ($watches){
        foreach($watches as $awatch){
            $people = $DB->count_records('tracker_issuecc', array('issueid' => $awatch->issueid));
            $watches[$awatch->id]->people = $people;
        }
    }
    return $watches;
}

/**
* sends required notifications when requiring raising priority
* @uses $COURSE
* @param object $issue
* @param object $cm
* @param object $tracker
*/
function tracker_notify_raiserequest($issue, &$cm, $reason, $urgent, $tracker = null){
    global $COURSE, $SITE, $CFG, $USER, $DB;

    if (empty($tracker)){ // database access optimization in case we have a tracker from somewhere else
        $tracker = $DB->get_record('tracker', array('id' => $issue->trackerid));
    }
    $context = context_module::instance($cm->id);
    $managers = get_users_by_capability($context, 'mod/tracker:manage', 'u.id,firstname,lastname,lang,email,emailstop,mailformat,mnethostid', 'lastname');

    $by = $DB->get_record('user', array('id' => $issue->reportedby));
    $urgentrequest = '';
    if ($urgent){
        $urgentrequest = get_string('urgentsignal', 'tracker');
    }

    $vars = array('COURSE_SHORT' => $COURSE->shortname, 
                  'COURSENAME' => format_string($COURSE->fullname), 
                  'TRACKERNAME' => format_string($tracker->name), 
                  'ISSUE' => $tracker->ticketprefix.$issue->id, 
                  'SUMMARY' => format_string($issue->summary), 
                  'REASON' => stripslashes($reason), 
                  'URGENT' => $urgentrequest, 
                  'BY' => fullname($by),
                  'REQUESTEDBY' => fullname($USER),
                  'ISSUEURL' => $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=view&amp;page=viewanissue&amp;issueid={$issue->id}",
                  );

    include_once($CFG->dirroot."/mod/tracker/mailtemplatelib.php");

    if (!empty($managers)){
        foreach($managers as $manager){
            $notification = tracker_compile_mail_template('raiserequest', $vars, 'tracker', $manager->lang);
            $notification_html = tracker_compile_mail_template('raiserequest_html', $vars, 'tracker', $manager->lang);
            if ($CFG->debugsmtp) echo "Sending Raise Request Mail Notification to " . fullname($manager) . '<br/>'.$notification_html;
            email_to_user($manager, $USER, get_string('raiserequestcaption', 'tracker', $SITE->shortname.':'.format_string($tracker->name)), $notification, $notification_html);
        }
    }

    $systemcontext = context_system::instance();
    $admins = get_users_by_capability($systemcontext, 'moodle/site:doanything', 'u.id,firstname,lastname,lang,email,emailstop,mailformat,mnethostid', 'lastname');

    if (!empty($admins)){
        foreach($admins as $admin){
            $notification = tracker_compile_mail_template('raiserequest', $vars, 'tracker', $admin->lang);
            $notification_html = tracker_compile_mail_template('raiserequest_html', $vars, 'tracker', $admin->lang);
            if ($CFG->debugsmtp) echo "Sending Raise Request Mail Notification to " . fullname($admin) . '<br/>'.$notification_html;
            email_to_user($admin, $USER, get_string('urgentraiserequestcaption', 'tracker', $SITE->shortname.':'.format_string($tracker->name)), $notification, $notification_html);
        }
    }

}

/**
* sends required notifications by the watchers when first submit
* @uses $COURSE
* @param object $issue
* @param object $cm
* @param object $tracker
*/
function tracker_notify_submission($issue, &$cm, $tracker = null){
    global $COURSE, $SITE, $CFG, $USER, $DB;

    if (empty($tracker)){ // database access optimization in case we have a tracker from somewhere else
        $tracker = $DB->get_record('tracker', array('id' => $issue->trackerid));
    }
    $context = context_module::instance($cm->id);
    $managers = get_users_by_capability($context, 'mod/tracker:manage', 'u.id,firstname,lastname,lang,email,emailstop,mailformat,mnethostid', 'lastname');

    $by = $DB->get_record('user', array('id' => $issue->reportedby));
    if (!empty($managers)){
        $vars = array('COURSE_SHORT' => $COURSE->shortname, 
                      'COURSENAME' => format_string($COURSE->fullname), 
                      'TRACKERNAME' => format_string($tracker->name), 
                      'ISSUE' => $tracker->ticketprefix.$issue->id, 
                      'SUMMARY' => format_string($issue->summary), 
                      'DESCRIPTION' => format_string(stripslashes($issue->description)), 
                      'BY' => fullname($by),
                      'ISSUEURL' => $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=view&amp;page=viewanissue&amp;issueid={$issue->id}",
                      'CCURL' => $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=profile&amp;page=mywatches&amp;issueid={$issue->id}&amp;what=register"
                      );
        include_once($CFG->dirroot."/mod/tracker/mailtemplatelib.php");
        foreach($managers as $manager){
            $notification = tracker_compile_mail_template('submission', $vars, 'tracker', $manager->lang);
            $notification_html = tracker_compile_mail_template('submission_html', $vars, 'tracker', $manager->lang);
            if ($CFG->debugsmtp) echo "Sending Submission Mail Notification to " . fullname($manager) . '<br/>'.$notification_html;
            email_to_user($manager, $USER, get_string('submission', 'tracker', $SITE->shortname.':'.format_string($tracker->name)), $notification, $notification_html);
        }
    }
}

/**
* sends required notifications by the watchers when first submit
* @uses $COURSE
* @param int $issueid
* @param object $tracker
*/
function tracker_notifyccs_changeownership($issueid, $tracker = null){
    global $COURSE, $SITE, $CFG, $USER, $DB;

    $issue = $DB->get_record('tracker_issue', array('id' => $issueid));
    if (empty($tracker)){ // database access optimization in case we have a tracker from somewhere else
        $tracker = $DB->get_record('tracker', array('id' => $issue->trackerid));
    }

    $issueccs = $DB->get_records('tracker_issuecc', array('issueid' => $issue->id));
    $assignee = $DB->get_record('user', array('id' => $issue->assignedto));
    if (!empty($issueccs)){
        $vars = array('COURSE_SHORT' => $COURSE->shortname, 
                      'COURSENAME' => format_string($COURSE->fullname), 
                      'TRACKERNAME' => format_string($tracker->name), 
                      'ISSUE' => $tracker->ticketprefix.$issue->id, 
                      'SUMMARY' => format_string($issue->summary), 
                      'ASSIGNEDTO' => fullname($assignee), 
                      'BY' => fullname($USER),
                      'ISSUEURL' => $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=view&amp;page=viewanissue&amp;issueid={$issue->id}",
                      );
        include_once($CFG->dirroot.'/mod/tracker/mailtemplatelib.php');
        foreach($issueccs as $cc){
            $ccuser = $DB->get_record('user', array('id' => $cc->userid));
            $vars['UNCCURL'] = $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=profile&amp;page=mywatches&amp;ccid={$cc->userid}&amp;what=unregister";
            $vars['ALLUNCCURL'] = $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=profile&amp;page=mywatches&amp;userid={$cc->userid}&amp;what=unregisterall";
            $notification = tracker_compile_mail_template('ownershipchanged', $vars, 'tracker', $ccuser->lang);
            $notification_html = tracker_compile_mail_template('ownershipchanged_html', $vars, 'tracker', $ccuser->lang);
            if ($CFG->debugsmtp) echo "Sending Ownership Change Mail Notification to " . fullname($ccuser) . '<br/>'.$notification_html;
            email_to_user($ccuser, $USER, get_string('submission', 'tracker', $SITE->shortname.':'.format_string($tracker->name)), $notification, $notification_html);
        }
    }
}

/**
* notify when moving an issue from a traker to a new tracker
* @uses $COURSE
* @param int $issueid
* @param object $tracker
* @param object $newtracker
*/
function tracker_notifyccs_moveissue($issueid, $tracker, $newtracker = null){
    global $COURSE, $SITE, $CFG, $USER, $DB;

    $issue = $DB->get_record('tracker_issue', array('id' => $issueid));
    if (empty($tracker)){ // database access optimization in case we have a tracker from somewhere else
        $tracker = $DB->get_record('tracker', array('id' => $issue->trackerid));
    }
    $newcourse = $DB->get_record('course', array('id' => $newtracker->course));

    $issueccs = $DB->get_records('tracker_issuecc', array('issueid' => $issue->id));
    $assignee = $DB->get_record('user', array('id' => $issue->assignedto));
    if (!empty($issueccs)){
        $vars = array('COURSE_SHORT' => $COURSE->shortname, 
                      'COURSENAME' => format_string($COURSE->fullname), 
                      'TRACKERNAME' => format_string($tracker->name), 
                      'NEWCOURSE_SHORT' => format_string($tracker->name), 
                      'NEWCOURSENAME' => format_string($tracker->name), 
                      'NEWTRACKERNAME' => format_string($tracker->name), 
                      'OLDISSUE' => $tracker->ticketprefix.$issue->id, 
                      'ISSUE' => $newtracker->ticketprefix.$issue->id, 
                      'SUMMARY' => format_string($issue->summary), 
                      'ASSIGNEDTO' => fullname($assignee), 
                      'ISSUEURL' => $CFG->wwwroot."/mod/tracker/view.php?a={$newtracker->id}&amp;view=view&amp;page=viewanissue&amp;issueid={$issue->id}",
                      );
        foreach($issueccs as $cc){
            $ccuser = $DB->get_record('user', array('id' => $cc->userid));
            $vars['UNCCURL'] = $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=profile&amp;page=mywatches&amp;ccid={$cc->userid}&amp;what=unregister";
            $vars['ALLUNCCURL'] = $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=profile&amp;page=mywatches&amp;userid={$cc->userid}&amp;what=unregisterall";
            $notification = tracker_compile_mail_template('issuemoved', $vars, 'tracker', $ccuser->lang);
            $notification_html = tracker_compile_mail_template('issuemoved_html', $vars, 'tracker', $ccuser->lang);
            if ($CFG->debugsmtp) echo "Sending Issue Moving Mail Notification to " . fullname($ccuser) . '<br/>'.$notification_html;
            email_to_user($ccuser, $USER, get_string('submission', 'tracker', $SITE->shortname.':'.format_string($tracker->name)), $notification, $notification_html);
        }
    }
}

/**
* sends required notifications by the watchers when state changes
* @uses $COURSE
* @param int $issueid
* @param object $tracker
*/
function tracker_notifyccs_changestate($issueid, $tracker = null){
    global $COURSE, $SITE, $CFG, $USER, $DB;

    $issue = $DB->get_record('tracker_issue', array('id' => $issueid));
    if (empty($tracker)){ // database access optimization in case we have a tracker from somewhere else
        $tracker = $DB->get_record('tracker', array('id' => $issue->trackerid));
    }
    $issueccs = $DB->get_records('tracker_issuecc', array('issueid' => $issueid));

    if (!empty($issueccs)){    
        $vars = array('COURSE_SHORT' => $COURSE->shortname, 
                      'COURSENAME' => format_string($COURSE->fullname), 
                      'TRACKERNAME' => format_string($tracker->name), 
                      'ISSUE' => $tracker->ticketprefix.$issueid, 
                      'SUMMARY' => format_string($issue->summary), 
                      'BY' => fullname($USER),
                      'ISSUEURL' => $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=view&amp;page=viewanissue&amp;issueid={$issueid}");
        include_once($CFG->dirroot.'/mod/tracker/mailtemplatelib.php');
        foreach($issueccs as $cc){
            unset($notification);
            unset($notification_html);
            $ccuser = $DB->get_record('user', array('id' => $cc->userid));
            $vars['UNCCURL'] = $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=profile&amp;page=mywatches&amp;ccid={$cc->userid}&amp;what=unregister";
            $vars['ALLUNCCURL'] = $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=profile&amp;page=mywatches&amp;userid={$cc->userid}&amp;what=unregisterall";
            switch($issue->status){
                case OPEN : 
                    if($cc->events & EVENT_OPEN){
                        $vars['EVENT'] = get_string('open', 'tracker');
                        $notification = tracker_compile_mail_template('statechanged', $vars, 'tracker', $ccuser->lang);
                        $notification_html = tracker_compile_mail_template('statechanged_html', $vars, 'tracker', $ccuser->lang);
                    }
                break;
                case RESOLVING : 
                    if($cc->events & EVENT_RESOLVING){
                        $vars['EVENT'] = get_string('resolving', 'tracker');
                        $notification = tracker_compile_mail_template('statechanged', $vars, 'tracker', $ccuser->lang);
                        $notification_html = tracker_compile_mail_template('statechanged_html', $vars, 'tracker', $ccuser->lang);
                    }
                break;
                case WAITING : 
                    if($cc->events & EVENT_WAITING){
                        $vars['EVENT'] = get_string('waiting', 'tracker');
                        $notification = tracker_compile_mail_template('statechanged', $vars, 'tracker', $ccuser->lang);
                        $notification_html = tracker_compile_mail_template('statechanged_html', $vars, 'tracker', $ccuser->lang);
                    }
                break;
                case RESOLVED : 
                    if($cc->events & EVENT_RESOLVED){
                        $vars['EVENT'] = get_string('resolved', 'tracker');
                        $notification = tracker_compile_mail_template('statechanged', $vars, 'tracker', $ccuser->lang);
                        $notification_html = tracker_compile_mail_template('statechanged_html', $vars, 'tracker', $ccuser->lang);
                    }
                break;
                case ABANDONNED : 
                    if($cc->events & EVENT_ABANDONNED){
                        $vars['EVENT'] = get_string('abandonned', 'tracker');
                        $notification = tracker_compile_mail_template('statechanged', $vars, 'tracker', $ccuser->lang);
                        $notification_html = tracker_compile_mail_template('statechanged_html', $vars, 'tracker', $ccuser->lang);
                    }
                break;
                case TRANSFERED : 
                    if($cc->events & EVENT_TRANSFERED){
                        $vars['EVENT'] = get_string('transfered', 'tracker');
                        $notification = tracker_compile_mail_template('statechanged', $vars, 'tracker', $ccuser->lang);
                        $notification_html = tracker_compile_mail_template('statechanged_html', $vars, 'tracker', $ccuser->lang);
                    }
                break;
                case TESTING : 
                    if($cc->events & EVENT_TESTING){
                        $vars['EVENT'] = get_string('testing', 'tracker');
                        $notification = tracker_compile_mail_template('statechanged', $vars, 'tracker', $ccuser->lang);
                        $notification_html = tracker_compile_mail_template('statechanged_html', $vars, 'tracker', $ccuser->lang);
                    }
                break;
                case PUBLISHED : 
                    if($cc->events & EVENT_PUBLISHED){
                        $vars['EVENT'] = get_string('published', 'tracker');
                        $notification = tracker_compile_mail_template('statechanged', $vars, 'tracker', $ccuser->lang);
                        $notification_html = tracker_compile_mail_template('statechanged_html', $vars, 'tracker', $ccuser->lang);
                    }
                break;
                default:
            }
            if (!empty($notification)){
                if($CFG->debugsmtp) echo "Sending State Change Mail Notification to " . fullname($ccuser) . '<br/>'.$notification_html;
                email_to_user($ccuser, $USER, get_string('trackereventchanged', 'tracker', $SITE->shortname.':'.format_string($tracker->name)), $notification, $notification_html);
            }
        }
    }
}

/**
* sends required notifications by the watchers when first submit
* @uses $COURSE
* @param int $issueid
* @param object $tracker
*/
function tracker_notifyccs_comment($issueid, $comment, $tracker = null){
    global $COURSE, $SITE, $CFG, $USER, $DB;

    $issue = $DB->get_record('tracker_issue', array('id' => $issueid));
    if (empty($tracker)){ // database access optimization in case we have a tracker from somewhere else
        $tracker = $DB->get_record('tracker', array('id' => $issue->trackerid));
    }

    $issueccs = $DB->get_records('tracker_issuecc', array('issueid' => $issue->id));
    if (!empty($issueccs)){
        $vars = array('COURSE_SHORT' => $COURSE->shortname, 
                      'COURSENAME' => format_string($COURSE->fullname), 
                      'TRACKERNAME' => format_string($tracker->name), 
                      'ISSUE' => $tracker->ticketprefix.$issue->id, 
                      'SUMMARY' => $issue->summary, 
                      'COMMENT' => format_string(stripslashes($comment)), 
                      'ISSUEURL' => $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=view&amp;page=viewanissue&amp;issueid={$issue->id}",
                      );
        include_once($CFG->dirroot.'/mod/tracker/mailtemplatelib.php');
        foreach($issueccs as $cc){
            $ccuser = $DB->get_record('user', array('id' => $cc->userid));
            if ($cc->events & ON_COMMENT){
                $vars['CONTRIBUTOR'] = fullname($USER);
                $vars['UNCCURL'] = $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=profile&amp;page=mywatches&amp;ccid={$cc->userid}&amp;what=unregister";
                $vars['ALLUNCCURL'] = $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=profile&amp;page=mywatches&amp;userid={$cc->userid}&amp;what=unregisterall";
                $notification = tracker_compile_mail_template('addcomment', $vars, 'tracker', $ccuser->lang);
                $notification_html = tracker_compile_mail_template('addcomment_html', $vars, 'tracker', $ccuser->lang);
                if ($CFG->debugsmtp) echo "Sending Comment Notification to " . fullname($ccuser) . '<br/>'.$notification_html;
                email_to_user($ccuser, $USER, get_string('submission', 'tracker', $SITE->shortname.':'.format_string($tracker->name)), $notification, $notification_html);
            }
        }
    }
}

/**
* loads the tracker users preferences in the $USER global.
* @uses $USER
* @param int $trackerid the current tracker
* @param int $userid the user the preferences belong to
*/
function tracker_loadpreferences($trackerid, $userid = 0){
    global $USER, $DB;
    if ($userid == 0) $userid = $USER->id;
    $preferences = $DB->get_records_select('tracker_preferences', "trackerid = $trackerid AND userid = $userid");
    if ($preferences){
        foreach($preferences as $preference){
            $USER->trackerprefs->{$preference->name} = $preference->value;
        }
    }
}

/**
* prints a transfer link follow up to an available parent record
* @uses $CFG
*
*/
function tracker_print_transfer_link(&$tracker, &$issue){
    global $CFG, $DB;
    if (empty($tracker->parent)) return '';
    if (is_numeric($tracker->parent)){
        if (!empty($issue->followid)){
            $href = "<a href=\"/mod/tracker/view.php?id={$tracker->parent}&view=view&screen=viewanissue&issueid={$issue->followid}\">".get_string('follow', 'tracker').'</a>';
        } else {
            $href = '';
        }
    } else {
        list($parentid, $hostroot) = explode('@', $tracker->parent);
        $mnet_host = $DB->get_record('mnet_host', array('wwwroot' => $hostroot));
        $remoteurl = urlencode("/mod/tracker/view.php?view=view&amp;screen=viewanissue&amp;a={$parentid}&amp;issueid={$issue->id}");
        $href = "<a href=\"{$CFG->wwwroot}/auth/mnet/jump.php?hostid={$mnet_host->id}&amp;wantsurl={$remoteurl}\">".get_string('follow', 'tracker')."</a>";
    }
    return $href;
}

/**
* displays a match status of element definition between two trackers
* @param int $trackerid the id of the local tracker
* @param object $remote a remote tracker
* @return false if no exact matching in name and type
*/
function tracker_display_elementmatch($local, $remote){

    $match = true;

    echo "<ul>";
    foreach($remote->elements as $name => $element){
        $description = format_string($element->description);
        if (!empty($local->elements) && in_array($name, array_keys($local->elements))){
            if ($local->elements[$name]->type == $remote->elements[$name]->type){
                echo "<li>{$element->name} : {$description} ({$element->type})</li>";
            } else {
                echo "<li>{$element->name} : {$description} <span class=\"red\">({$element->type})</span></li>";
                $match = false;
            }
        } else {
            echo "<li><span class=\"red\">+{$element->name} : {$description} ({$element->type})</span></li>";
            $match = false;
        }
    }

    // Note that array_diff is buggy in PHP5
    if (!empty($local->elements)){
        foreach (array_keys($local->elements) as $localelement){
            if (!empty($remote->elements) && !in_array($localelement, array_keys($remote->elements))){
                $description = format_string($local->elements[$localelement]->description);
                echo "<li><span style=\"color: blue\" class=\"blue\">-{$local->elements[$localelement]->name} : {$description} ({$local->elements[$localelement]->type})</span></li>";
                $match = false;
            }
        }
    }
    echo "</ul>";
    return $match;
}

/**
* prints a backlink to the issue when cascading
* @uses $SITE
* @uses $CFG
* @param object $cm the tracker course module
* @param object $issue the original ticket
*/
function tracker_add_cascade_backlink(&$cm, &$issue){
    global $SITE, $CFG;

    $vieworiginalstr = get_string('vieworiginal', 'tracker');
    $str = get_string('cascadedticket', 'tracker', $SITE->shortname);
    $str .= '<br/>';
    $str .= "<a href=\"{$CFG->wwwroot}/mod/tracker/view.php?id={$cm->id}&amp;view=view&amp;page=viewanissue&amp;issueid={$issue->id}\">{$vieworiginalstr}</a><br/>";

    return $str;    
}

/**
* reorder correctly the priority sequence and discard from the stack
* all resolved and abandonned entries
* @uses $CFG
* @param $reference $tracker
*/
function tracker_update_priority_stack(&$tracker){
    global $CFG, $DB;
    
    /// discards resolved, transferred or abandoned
    $sql = "
       UPDATE 
           {tracker_issue}
       SET
           resolutionpriority = 0
       WHERE
           trackerid = $tracker->id AND
           status IN (".RESOLVED.','.ABANDONNED.','.TRANSFERED.')';
    $DB->execute($sql);

    /// fetch prioritarized by order
    $issues = $DB->get_records_select('tracker_issue', "trackerid = {$tracker->id} AND resolutionpriority != 0 ", 'resolutionpriority', 'id, resolutionpriority');
    $i = 1;
    if (!empty($issues)){
        foreach ($issues as $issue){
            $issue->resolutionpriority = $i;
            $DB->update_record('tracker_issue', $issue);
            $i++;
        }
    }
}

function tracker_get_stats(&$tracker, $from = null, $to = null){
	global $CFG, $DB;
	$sql = "
		SELECT
			status,
			count(*) as value
		FROM
			{tracker_issue}
		WHERE
			trackerid = {$tracker->id}
		GROUP BY
			status
	";
	if ($results = $DB->get_records_sql($sql)){
		foreach($results as $r){
			$stats[$r->status] = $r->value;
		}
	} else {
		$stats[POSTED] = 0;
		$stats[OPEN] = 0;
		$stats[RESOLVING] = 0;
		$stats[WAITING] = 0;
		$stats[TESTING] = 0;
		$stats[PUBLISHED] = 0;
		$stats[RESOLVED] = 0;
		$stats[ABANDONNED] = 0;
		$stats[TRANSFERED] = 0;
	}
	return $stats;
}

/**
* compile stats relative to emission date
*
*/
function tracker_get_stats_by_month(&$tracker, $from = null, $to = null){
	global $CFG, $DB;
	$sql = "
		SELECT
			CONCAT(YEAR(FROM_UNIXTIME(datereported)), '-', DATE_FORMAT(FROM_UNIXTIME(datereported), '%m'), '-', status) as resultid,
			CONCAT(YEAR(FROM_UNIXTIME(datereported)), '-', DATE_FORMAT(FROM_UNIXTIME(datereported), '%m')) as period,
			status,
			count(*) as value
		FROM
			{tracker_issue}
		WHERE
			trackerid = {$tracker->id}
		GROUP BY
			status, CONCAT(YEAR(FROM_UNIXTIME(datereported)), '-', DATE_FORMAT(FROM_UNIXTIME(datereported), '%m'))
		ORDER BY period
	";
	if ($results = $DB->get_records_sql($sql)){
		foreach($results as $r){
			$stats[$r->period][$r->status] = $r->value;
			$stats[$r->period]['sum'] = @$stats[$r->period]['sum'] + $r->value;
			$stats['sum'] = @$stats['sum'] + $r->value;
		}
	} else {
		$stats = array();
	}

	return $stats;
}

/**
* backtracks all issues and summarizes monthly on status
*
*/
function tracker_backtrack_stats_by_month(&$tracker){
	global $CFG, $DB;

	$sql = "
		SELECT
			id,
			CONCAT(YEAR(FROM_UNIXTIME(datereported)), '-', DATE_FORMAT(FROM_UNIXTIME(datereported), '%m')) as period,
			status
		FROM
			{tracker_issue}
		WHERE
			trackerid = {$tracker->id}
		ORDER BY period
	";
	if ($issues = $DB->get_records_sql($sql)){

		// dispatch issue generating events and follow change tracks
		foreach($issues as $is){
			$tracks[$is->period][$is->id] = $is->status;
			$sql = "
				SELECT
					id,
					issueid,
					timechange,
					CONCAT(YEAR(FROM_UNIXTIME(timechange)), '-', DATE_FORMAT(FROM_UNIXTIME(timechange), '%m')) as period,
					statusto
				FROM
					{tracker_state_change}
				WHERE
					issueid = {$is->id}
				ORDER BY 
					timechange
			";
			if ($changes = $DB->get_records_sql($sql)){
				foreach($changes as $c){
					$tracks[$c->period][$c->issueid] = $c->statusto;
				}
			}
			$issuelist[$is->id] = -1;
		}
		ksort($tracks);
		$availdates = array_keys($tracks);
		$lowest = $availdates[0];
		$highest = $availdates[count($availdates) - 1];
		list($low->year, $low->month) = split('-', $lowest);
		$dateiter = new date_iterator($low->year, $low->month);
		// scan table and snapshot issue states
		$current = $dateiter->current();
		while (strcmp($current, $highest) <= 0) {
			if (array_key_exists($current, $tracks)){
				foreach($tracks[$current] as $trackedid => $trackedstate){
					$issuelist[$trackedid] = $trackedstate;
				}
			}
			$monthtracks[$current] = $issuelist;
			$dateiter->next();
			$current = $dateiter->current();
		}
		// revert and summarize states	
		foreach($monthtracks as $current => $monthtrack){
			foreach($monthtrack as $issueid => $state){
				if ($state == -1) continue;
				$stats[$current][$state] = @$stats[$current][$state] + 1;
				$stats[$current]['sum'] = @$stats[$current]['sum'] + 1;
				if ($state != RESOLVED && $state != ABANDONNED && $state != TRANSFERED)
					$stats[$current]['sumunres'] = @$stats[$current]['sumunres'] + 1;
			}
		}
		return $stats;
	}
	return array();
}

/**
* Compiles global stats on users
*
*/
function tracker_get_stats_by_user(&$tracker, $userclass, $from = null, $to = null){
	global $CFG, $DB;
	$sql = "
		SELECT
			CONCAT(u.id, '-', i.status) as resultdid,
			u.id,
			u.firstname,
			u.lastname,
			count(*) as value,
			i.status
		FROM
			{tracker_issue} i
		LEFT JOIN
			{user} u			
		ON
			i.{$userclass} = u.id
		WHERE
			trackerid = {$tracker->id}
		GROUP BY
			i.{$userclass},status
		ORDER BY
			u.lastname, u.firstname
	";
	if ($results = $DB->get_records_sql($sql)){
		foreach($results as $r){
			$stats[$r->id]->name = fullname($r);
			$stats[$r->id]->status[$r->status] = $r->value;
			$stats[$r->id]->sum = @$stats[$r->id]->sum + $r->value;
		}
	} else {
		$stats = array();
	}
	return $stats;
}

/**
* provides a practical date iterator for progress display
*
*/
class date_iterator{
	var $inityear;
	var $initmonth;
	var $year;
	var $month;
	function date_iterator($year, $month){
		$this->year = $year;
		$this->month = $month;
		$this->inityear = $year;
		$this->initmonth = $month;
	}	

	function reset(){
		$this->year = $this->inityear;
		$this->month = $this->initmonth;
	}
	function next(){
		$this->month++;
		if ($this->month > 12){
			$this->month = 1;
			$this->year++;
		}
	}

	function current(){
		return $this->year.'-'.sprintf('%02d', $this->month);
	}

	function getyear(){
		return $this->year;
	}

	function getmonth(){
		return $this->month;
	}
	function getiterations($highest){
		$year = $this->year;
		$month = $this->month;
		$current = $year.'-'.sprintf('%02d', $month);
		$i = 0;
		while (strcmp($current, $highest) <= 0){
			$i++;
			$month++;
			if ($month > 12){
				$month = 1;
				$year++;
			}			
			$current = $year.'-'.sprintf('%02d', $month);
		}
		return $i;
	}
}

?>
