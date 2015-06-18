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
 * @package mod-tracker
 * @category mod
 * @author Clifford Tham, Valery Fremaux > 1.8
 * @date 02/12/2007
 *
 * Library of internal functions and constants for module tracker
 */

require_once($CFG->dirroot.'/mod/tracker/filesystemlib.php');
require_once($CFG->dirroot.'/lib/uploadlib.php');
require_once($CFG->dirroot.'/mod/tracker/mailtemplatelib.php');

// Status defines.
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

// status defines.
define('ENABLED_POSTED', 1);
define('ENABLED_OPEN', 2);
define('ENABLED_RESOLVING', 4);
define('ENABLED_WAITING', 8);
define('ENABLED_RESOLVED', 16);
define('ENABLED_ABANDONNED', 32);
define('ENABLED_TRANSFERED', 64);
define('ENABLED_TESTING', 128);
define('ENABLED_PUBLISHED', 256);
define('ENABLED_VALIDATED', 512);
define('ENABLED_ALL', 1023);

// Major roles against status keys.
function shop_get_role_definition(&$tracker, $role) {
    if ($role == 'report') {
        if ($tracker->supportmode == 'bugtracker') {
            return ENABLED_POSTED | ENABLED_VALIDATED;
        } else {
            return ENABLED_POSTED | ENABLED_RESOLVED| ENABLED_VALIDATED;
        }
    } elseif ($role == 'develop') {
        if ($tracker->supportmode == 'bugtracker') {
            return ENABLED_OPEN | ENABLED_RESOLVING | ENABLED_WAITING | ENABLED_ABANDONNED | ENABLED_TESTING | ENABLED_PUBLISHED | ENABLED_VALIDATED;
        } else {
            return ENABLED_OPEN | ENABLED_RESOLVING | ENABLED_WAITING | ENABLED_ABANDONNED | ENABLED_TESTING | ENABLED_PUBLISHED;
        }
    } elseif ($role == 'resolve') {
        return ENABLED_RESOLVED | ENABLED_VALIDATED | ENABLED_ABANDONNED | ENABLED_TRANSFERED;
    } elseif ($role == 'manage') {
        return ENABLED_POSTED | ENABLED_RESOLVING | ENABLED_WAITING | ENABLED_ABANDONNED | ENABLED_TESTING | ENABLED_PUBLISHED | ENABLED_VALIDATED;
    }
    return 0;
}

// States && eventmasks.
define('EVENT_POSTED', 1);
define('EVENT_OPEN', 2);
define('EVENT_RESOLVING', 4);
define('EVENT_WAITING', 8);
define('EVENT_RESOLVED', 16);
define('EVENT_ABANDONNED', 32);
define('EVENT_TRANSFERED', 64);
define('EVENT_TESTING', 128);
define('EVENT_PUBLISHED', 256);
define('EVENT_VALIDATED', 512);
define('ON_COMMENT', 1024);

define('ALL_EVENTS', 2047);

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
                    PUBLISHED => 'published',
                    VALIDATED => 'validated',
                    );

/**
 * loads all elements in memory
 * @uses $CFG
 * @uses $COURSE
 * @param reference $tracker the tracker object
 * @param reference $elementsobj
 */
function tracker_loadelements(&$tracker, &$elementsobj) {
    global $COURSE, $CFG, $DB;

    // First get shared elements.
    $elements = $DB->get_records('tracker_element', array('course' => 0));
    if (!$elements) {
        $elements = array();
    }

    // Get course scope elements.
    $courseelements = $DB->get_records('tracker_element', array('course' => $COURSE->id));
    if ($courseelements) {
        $elements = array_merge($elements, $courseelements);
    }

    // Make a set of element objet with records.
    if (!empty($elements)) {
        foreach ($elements as $element) {
            // this get the options by the constructor
            include_once($CFG->dirroot.'/mod/tracker/classes/trackercategorytype/'.$element->type.'/'.$element->type.'.class.php');
            $constructorfunction = "{$element->type}element";
            $elementsobj[$element->id] = new $constructorfunction($tracker, $element->id);
            $elementsobj[$element->id]->name = $element->name;
            $elementsobj[$element->id]->description = $element->description;
            $elementsobj[$element->id]->type = $element->type;
            $elementsobj[$element->id]->course = $element->course;
        }
    }
}

/**
 * get all available types which are plugins in classes/trackercategorytype
 * @uses $CFG
 * @return an array of known element types
 */
function tracker_getelementtypes() {
    global $CFG;
    $typedir = "{$CFG->dirroot}/mod/tracker/classes/trackercategorytype";
    $DIR = opendir($typedir);
    while ($entry = readdir($DIR)) {
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
function tracker_requiresfile($trackerid) {
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
 * loads elements as objects array in a reference
 * @param int $trackerid the current tracker
 * @param reference $used a reference to an array of used elements
 */
function tracker_loadelementsused(&$tracker, &$used) {
    global $CFG, $DB;

    $cm = get_coursemodule_from_instance('tracker', $tracker->id);
    $context = context_module::instance($cm->id);

    $usedelements = $DB->get_records('tracker_elementused', array('trackerid' => $tracker->id), 'sortorder', 'id,elementid,sortorder');
    $used = array();
    $sortorder = 1;
    if (!empty($usedelements)) {
        foreach ($usedelements as $ueid => $ue) {
            // normalize sortorder indexes
            if ($ue->sortorder != $sortorder) {
                $ue->sortorder = $sortorder;
                $DB->update_record('tracker_elementused', $ue);
            }
            $used[$ue->elementid] = trackerelement::find_instance_by_usedid($tracker, $ueid);
            $used[$ue->elementid]->setcontext($context);
            $sortorder++;
        }
    }
}

/**
 * quite the same as above, but not loading objects, and
 * mapping hash keys by "name"
 * @param int $trackerid
 *
 */
function tracker_getelementsused_by_name(&$tracker) {
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
    if (!$usedelements = $DB->get_records_sql($sql)) {
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
function tracker_iselementused($trackerid, $elementid) {
    global $DB;

    $inusedelements = $DB->count_records_select('tracker_elementused', 'elementid = ' . $elementid . ' AND trackerid = ' . $trackerid);
    return $inusedelements;
}

/**
* print additional user defined elements in several contexts
* @param int $trackerid the current tracker
* @param array $fields the array of fields to be printed
*/
function tracker_printelements(&$tracker, $fields = null, $dest = false) {
    tracker_loadelementsused($tracker, $used);
    if (!empty($used)) {
        if (!empty($fields)) {
            foreach ($used as $element) {
                if (isset($fields[$element->id])) {
                    foreach ($fields[$element->id] as $value) {
                        $element->value = $value;
                    }
                }
            }
        }
        foreach ($used as $element) {

            if (!$element->active) {
                continue;
            }

            echo '<tr>';
            echo '<td align="right" valign="top">';
            echo '<b>' . format_string($element->description) . ':</b>';
            echo '</td>';
            echo '<td align="left" colspan="3">';
            if ($dest == 'search') {
                if ($element->type == 'file') {
                    continue;
                }
                $element->viewsearch();
            } elseif ($dest == 'query') {
                if ($element->type == 'file') {
                    continue;
                }
                $element->viewquery();
            } else {
                $element->view(true);
            }
            echo '</td>';
            echo '</tr>';
        }
    }
}

// Search engine

/**
 * constructs an adequate search query, based on both standard and user defined
 * fields.
 * @param int $trackerid
 * @param array $fields
 * @return an object where both the query for counting and the query for getting results are
 * embedded.
 */
function tracker_constructsearchqueries($trackerid, $fields, $own = false) {
    global $CFG, $USER, $DB;

    $keys = array_keys($fields);

    // Check to see if we are search using elements as a parameter.
    // If so, we need to include the table tracker_issueattribute in the search query.
    $elementssearch = false;
    foreach ($keys as $key) {
        if (is_numeric($key)) {
            $elementssearch = true;
        }
    }
    $elementsSearchClause = ($elementssearch) ? " {tracker_issueattribute} AS ia, " : '' ;

    $elementsSearchConstraint = '';
    foreach ($keys as $key) {
        if ($key == 'id') {
            $elementsSearchConstraint .= ' AND  (';
            foreach ($fields[$key] as $idtoken) {
                $elementsSearchConstraint .= (empty($idquery)) ? 'i.id =' . $idtoken : ' OR i.id = ' . $idtoken ;
            }
            $elementsSearchConstraint .= ')';
        }

        if ($key == 'datereported' && array_key_exists('checkdate', $fields) ) {
            $datebegin = $fields[$key][0];
            $dateend = $datebegin + 86400;
            $elementsSearchConstraint .= " AND i.datereported > {$datebegin} AND i.datereported < {$dateend} ";
        }

        if ($key == 'description') {
            $tokens = explode(' ', $fields[$key][0], ' ');
            foreach ($tokens as $token) {
                $elementsSearchConstraint .= " AND i.description LIKE '%{$descriptiontoken}%' ";
            }
        }

        if ($key == 'reportedby') {
            $elementsSearchConstraint .= ' AND i.reportedby = ' . $fields[$key][0];
        }

        if ($key == 'assignedto') {
            $elementsSearchConstraint .= ' AND i.assignedto = ' . $fields[$key][0];
        }

        if ($key == 'summary') {
            $summarytokens = explode(' ', $fields[$key][0]);
            foreach ($summarytokens as $summarytoken) {
                $elementsSearchConstraint .= " AND i.summary LIKE '%{$summarytoken}%'";
            }
        }

        if (is_numeric($key)) {
            foreach ($fields[$key] as $value) {
                $elementsSearchConstraint .= ' AND i.id IN (SELECT issue FROM {tracker_issueattribute} WHERE elementdefinition=' . $key . ' AND elementitemid=' . $value . ')';
            }
        }
    }
    if ($own == false) {
        $sql = new StdClass();
        $sql->search = "
            SELECT DISTINCT
                i.id,
                i.trackerid,
                i.summary,
                i.datereported,
                i.reportedby,
                i.assignedto,
                i.resolutionpriority,
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
                i.id,
                i.trackerid,
                i.summary,
                i.datereported,
                i.reportedby,
                i.resolutionpriority,
                i.assignedto,
                i.status,
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
function tracker_extractsearchparametersfrompost() {
    $count = 0;
    $fields = array();
    $issuenumber = optional_param('issueid', '', PARAM_INT);
    if (!empty ($issuenumber)) {
        $issuenumberarray = explode(',', $issuenumber);
        foreach ($issuenumberarray as $issueid) {
            if (is_numeric($issueid)) {
                $fields['id'][] = $issueid;
            } else {
                print_error('errorbadlistformat', 'tracker', 'view.php?id=' . $this->tracker_getcoursemodule() . '&what=search');
            }
        }
     } else {
        $checkdate = optional_param('checkdate', 0, PARAM_INT);
        if ($checkdate) {
            $month = optional_param('month', '', PARAM_INT);
            $day = optional_param('day', '', PARAM_INT);
            $year = optional_param('year', '', PARAM_INT);

            if (!empty($month) && !empty($day) && !empty($year)) {
                $datereported = make_timestamp($year, $month, $day);
                $fields['datereported'][] = $datereported;
            }
        }

        $description = optional_param('description', '', PARAM_CLEANHTML);
        if (!empty($description)) {
            $fields['description'][] = stripslashes($description);
        }

        $reportedby = optional_param('reportedby', '', PARAM_INT);
        if (!empty($reportedby)) {
            $fields['reportedby'][] = $reportedby;
        }

        $assignedto = optional_param('assignedto', '', PARAM_INT);
        if (!empty($assignedto)) {
            $fields['assignedto'][] = $assignedto;
        }

        $summary = optional_param('summary', '', PARAM_TEXT);
        if (!empty($summary)) {
            $fields['summary'][] = $summary;
        }

        $keys = array_keys($_POST);                         // get the key value of all the fields submitted
        $elementkeys = preg_grep('/element./' , $keys);     // filter out only the element keys

        foreach ($elementkeys as $elementkey) {
            preg_match('/element(.*)$/', $elementkey, $elementid);
            if (!empty($_POST[$elementkey])) {
                if (is_array($_POST[$elementkey])) {
                    foreach ($_POST[$elementkey] as $elementvalue) {
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
function tracker_savesearchparameterstodb($query, $fields) {
    global $USER, $DB;

    $query->userid = $USER->id;
    $query->published = 0;
    $query->fieldnames = '';
    $query->fieldvalues = '';

    if (!empty($fields)) {
        $keys = array_keys($fields);
        if (!empty($keys)) {
            foreach ($keys as $key) {
                foreach ($fields[$key] as $value) {
                    if (empty($query->fieldnames)) {
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

    if (!isset($query->id)) {
        // If not given a $queryid, then insert record.
        $queryid = $DB->insert_record('tracker_query', $query);
    } else {
        // Otherwise, update record.
        $queryid = $DB->update_record('tracker_query', $query, true);
    }
    return $queryid;
}

/**
 * prints the human understandable search query form
 * @param array $fields
 */
function tracker_printsearchfields($fields) {
    global $DB;

    foreach ($fields as $key => $value) {
        switch (trim($key)) {
            case 'datereported' :
                if (!function_exists('trk_userdate')) {
                    function trk_userdate(&$a) {
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
                if ($users) {
                    foreach ($users as $user) {
                        $reporters[] = fullname($user);
                    }
                }
                $reporterlist = implode ("', '", $reporters);
                $strs[] = get_string('reportedby', 'tracker').' '.get_string('IN', 'tracker')." ('".$reporterlist."')";
                break;
            case 'assignedto' :
                $users = $DB->get_records_list('user', array('id' => implode(',',$value)), 'lastname', 'id,firstname,lastname');
                $assignees = array();
                if ($users) {
                    foreach ($users as $user) {
                        $assignees[] = fullname($user);
                    }
                }
                $assigneelist = implode ("', '", $assignees);
                $strs[] = get_string('assignedto', 'tracker').' '.get_string('IN', 'tracker')." ('".$assigneelist."')";
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
function tracker_extractsearchparametersfromdb($queryid = null) {
    global $DB;

    if (!$queryid) {
        $queryid = optional_param('queryid', '', PARAM_INT);
    }
    $query_record = $DB->get_record('tracker_query', array('id' => $queryid));
    $fields = null;

    if (!empty($query_record)) {
        $fieldnames = explode(',', $query_record->fieldnames);
        $fieldvalues = explode(',', $query_record->fieldvalues);
        $count = 0;
        if (!empty($fieldnames)) {
            foreach ($fieldnames as $fieldname) {
                $fields[trim($fieldname)][] = trim($fieldvalues[$count]);
                $count++;
            }
        }
    } else {
        error ("Invalid query id: " . $queryid);
    }

    return $fields;
}

/**
* set a cookie with search information
* @return boolean
*/
function tracker_setsearchcookies($fields) {
    $success = true;
    if (is_array($fields)) {
        $keys = array_keys($fields);

        foreach ($keys as $key) {
            $cookie = '';
            foreach ($fields[$key] as $value) {
                if (empty($cookie)) {
                    $cookie = $cookie . $value;
                } else {
                    $cookie = $cookie . ', ' . $value;
                }
            }

            $result = setcookie("moodle_tracker_search_" . $key, $cookie);
            $success = $success && $result;
        }
    } else {
        $success = false;
    }
    return $success;
}

/**
 * get last search parameters from use cookie
 * @uses $_COOKIE
 * @return an array of field desriptions
 */
function tracker_extractsearchcookies() {

    $keys = array_keys($_COOKIE);                                           // get the key value of all the cookies
    $cookiekeys = preg_grep('/moodle_tracker_search./' , $keys);            // filter all search cookies
    $fields = null;
    foreach ($cookiekeys as $cookiekey) {
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
function tracker_clearsearchcookies() {

    $success = true;
    $keys = array_keys($_COOKIE); // get the key value of all the cookies
    $cookiekeys = preg_grep('/moodle_tracker_search./' , $keys); // filter all search cookies

    foreach ($cookiekeys as $cookiekey) {
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
function tracker_searchforissues(&$tracker, $cmid) {
    global $CFG;

    tracker_clearsearchcookies($tracker->id);
    $fields = tracker_extractsearchparametersfrompost($tracker->id);
    $success = tracker_setsearchcookies($fields);

    if ($success) {
        if ($tracker->supportmode == 'bugtracker') {
            redirect ("view.php?id={$cmid}&amp;view=view&amp;screen=browse");
        } else {
            redirect("view.php?id={$cmid}&amp;view=view&amp;screen=mytickets");
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
function tracker_getnumissuesreported($trackerid, $status='*', $reporterid = '*', $resolverid='*', $developerids='', $adminid='*') {
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

// User related

/**
 * get available managers/tracker administrators
 * @param object $context
 */
function tracker_getadministrators($context) {
    $allnames = get_all_user_name_fields(true, 'u');
    return get_users_by_capability($context, 'mod/tracker:manage', 'u.id,'.$allnames, 'lastname', '', '', '', '', false);
}

/**
 * get available resolvers
 * @param object $context
 */
function tracker_getresolvers($context) {
    $allnames = get_all_user_name_fields(true, 'u');
    return get_users_by_capability($context, 'mod/tracker:resolve', 'u.id,'.$allnames, 'lastname', '', '', '', '', false);
}

/**
 * get actual reporters from records
 * @uses $CFG
 * @param int $trackerid
 */
function tracker_getreporters($trackerid) {
    global $CFG, $DB;

    $allnames = get_all_user_name_fields(true, 'u');

    $sql = "
        SELECT
            DISTINCT(reportedby) AS id,
            {$allnames},
            u.imagealt
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
function tracker_getdevelopers($context) {
    $allnames = get_all_user_name_fields(true, 'u');
    return get_users_by_capability($context, 'mod/tracker:develop', 'u.id,'.$allnames, 'lastname', '', '', '', '', false);
}

/**
 * get the assignees of a manager
 *
 */
function tracker_getassignees($userid) {
    global $CFG, $DB;

    $allnames = get_all_user_name_fields(true, 'u');

    $sql = "
        SELECT DISTINCT
            u.id,
            {$allnames},
            u.picture,
            u.email,
            u.emailstop,
            u.maildisplay,
            u.imagealt,
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
            u.maildisplay,
            u.imagealt
    ";
    return $DB->get_records_sql($sql, array($userid));
}

/**
 * submits an issue in the current tracker
 * @uses $CFG
 * @param int $trackerid the current tracker
 */
function tracker_submitanissue(&$tracker, &$data) {
    global $CFG, $DB, $USER;

    $issue = new StdClass();
    $issue->datereported = time();
    $issue->summary = $data->summary;
    $issue->description = $data->description_editor['text'];
    $issue->descriptionformat = $data->description_editor['format'];
    $issue->assignedto = $tracker->defaultassignee;
    $issue->bywhomid = 0;
    $issue->trackerid = $tracker->id;
    $issue->status = POSTED;
    $issue->reportedby = $USER->id;

    // Fetch max actual priority.
    $maxpriority = $DB->get_field_select('tracker_issue', 'MAX(resolutionpriority)', " trackerid = ? GROUP BY trackerid ", array($tracker->id));
    $issue->resolutionpriority = $maxpriority + 1;

    if ($issue->id = $DB->insert_record('tracker_issue', $issue)) {
        $data->issueid = $issue->id;
        tracker_recordelements($issue, $data);
        // If not CCed, the assignee should be.
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
function tracker_getownedissuesforresolve($trackerid, $userid = null) {
    global $USER, $DB;
    if (empty($userid)) {
        $userid = $USER->id;
    }
    return $DB->get_records_select('tracker_issue', " trackerid = ? AND assignedto = ? ", array($trackerid, $userid));
}

/**
 * stores in database the element values
 * @uses $CFG
 * @param object $issue
 * @param object $data full form return
 */
function tracker_recordelements(&$issue, &$data) {
    global $CFG, $COURSE, $DB , $PAGE;

    $tracker = $DB->get_record('tracker', array('id' => $issue->trackerid));
    $usedelements = $DB->get_records('tracker_elementused', array('trackerid' => $issue->trackerid), 'id', 'id,elementid');
    foreach ($usedelements as $ueid => $ue) {
        $ueinstance = trackerelement::find_instance_by_usedid($tracker, $ueid);
        $ueinstance->setcontext($PAGE->context);
        $ueinstance->formprocess($data);
    }
}

/**
 * clears element recordings for an issue
 * @see view.controller.php / updateissue
 * @param int $issueid the issue
 * @param int $withfiles if true, the attached files will be deleted too (full deletion)
 */
function tracker_clearelements($issueid, $withfiles = false) {
    global $CFG, $COURSE, $DB;

    if (!$issue = $DB->get_record('tracker_issue', array('id' => "$issueid"))) {
        return;
    }

    $attributeids = $DB->get_records('tracker_issueattribute', array('issueid' => $issueid), 'id', 'id,id');

    if (!$DB->delete_records('tracker_issueattribute', array('issueid' => $issueid))) {
        print_error('errorcannotlearelementsforissue', 'tracker', $issueid);
    }

    // delete issue attribute fields
    if ($withfiles && !empty($attributeids)) {
        $fs = get_file_storage();
        foreach ($attributeids as $attid => $void) {
            $fs->delete_area_files($context->id, 'mod_tracker', 'issueattribute', $attid);
        }
    }
}

/**
 * adds an error css marker in case of matching error
 * @param array $errors the current error set
 * @param string $errorkey
 */
if (!function_exists('print_error_class')) {
    function print_error_class($errors, $errorkeylist) {
        if ($errors) {
            foreach ($errors as $anError) {
                if ($anError->on == '') continue;
                if (preg_match("/\\b{$anError->on}\\b/" ,$errorkeylist)) {
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
function tracker_register_cc(&$tracker, &$issue, $userid) {
    global $DB;

    if ($userid && !$DB->get_record('tracker_issuecc', array('trackerid' => $tracker->id, 'issueid' => $issue->id, 'userid' => $userid))) {
        // Add new the assignee as new CC !!
        // we do not discard the old one as he may be still concerned
        $eventmask = 127;
        if ($userprefs = $DB->get_record('tracker_preferences', array('trackerid' => $tracker->id, 'userid' => $userid, 'name' => 'eventmask'))) {
            $eventmask = $userprefs->value;
        }
        $cc = new StdClass;
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
function tracker_print_user($user, $return = false) {
    global $COURSE, $CFG, $OUTPUT;

    $str = '';

    if ($user) {
        $str .= $OUTPUT->user_picture ($user, array('courseid' => $COURSE->id, 'size' => 25));
        if ($CFG->messaging) {
            $str .= "&nbsp;<a href=\"$CFG->wwwroot/user/view.php?id={$user->id}&amp;course={$COURSE->id}\">".fullname($user)."</a> <a href=\"\" onclick=\"this.target='message'; return openpopup('/message/discussion.php?id={$user->id}', 'message', 'menubar=0,location=0,scrollbars,status,resizable,width=400,height=500', 0);\" ><img src=\"".$OUTPUT->pix_url('t/message', 'core')."\"></a>";
        } elseif (!$user->emailstop && $user->maildisplay) {
            $str .= "&nbsp;<a href=\"$CFG->wwwroot/user/view.php?id={$user->id}&amp;course={$COURSE->id}\">".fullname($user)."</a> <a href=\"mailto:{$user->email}\"><img src=\"".$OUTPUT->pix_url('t/mail', 'core')."\"></a>";
        } else {
            $str .= '&nbsp;'.fullname($user);
        }
    }

    if ($return) return $str;
    echo $str;
}

/**
* prints comments for the given issue
* @uses $CFG
* @param int $issueid
*/
function tracker_printcomments($issueid) {
    global $CFG, $DB;

    $comments = $DB->get_records('tracker_issuecomment', array('issueid' => $issueid), 'datecreated');
    if ($comments) {
        foreach ($comments as $comment) {
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
function tracker_getpotentialdependancies($trackerid, $issueid) {
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
function tracker_get_subtree_list($trackerid, $id) {
    global $DB;

    $res = $DB->get_records_menu('tracker_issuedependancy', array('parentid' => $id), '', 'id,childid');
    $ids = array();
    if (is_array($res)) {
        foreach (array_values($res) as $aSub) {
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
function tracker_printchilds(&$tracker, $issueid, $return=false, $indent='') {
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
    if ($res) {
        foreach ($res as $aSub) {
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
function tracker_printparents(&$tracker, $issueid, $return=false, $indent='') {
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
    if ($res) {
        foreach ($res as $aSub) {
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
function tracker_getwatches($trackerid, $userid) {
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
    if ($watches) {
        foreach ($watches as $awatch) {
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
function tracker_notify_raiserequest($issue, &$cm, $reason, $urgent, $tracker = null) {
    global $COURSE, $SITE, $CFG, $USER, $DB;

    if (empty($tracker)) { // database access optimization in case we have a tracker from somewhere else
        $tracker = $DB->get_record('tracker', array('id' => $issue->trackerid));
    }
    $context = context_module::instance($cm->id);
    $managers = get_users_by_capability($context, 'mod/tracker:manage', 'u.id,firstname,lastname,lang,email,emailstop,mailformat,mnethostid', 'lastname');

    $by = $DB->get_record('user', array('id' => $issue->reportedby));
    $urgentrequest = '';
    if ($urgent) {
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
                  'ISSUEURL' => $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=view&amp;screen=viewanissue&amp;issueid={$issue->id}",
                  );

    include_once($CFG->dirroot."/mod/tracker/mailtemplatelib.php");

    if (!empty($managers)) {
        foreach ($managers as $manager) {
            $notification = tracker_compile_mail_template('raiserequest', $vars, 'tracker', $manager->lang);
            $notification_html = tracker_compile_mail_template('raiserequest_html', $vars, 'tracker', $manager->lang);
            if ($CFG->debugsmtp) echo "Sending Raise Request Mail Notification to " . fullname($manager) . '<br/>'.$notification_html;
            email_to_user($manager, $USER, get_string('raiserequestcaption', 'tracker', $SITE->shortname.':'.format_string($tracker->name)), $notification, $notification_html);
        }
    }

    $systemcontext = context_system::instance();
    $admins = get_users_by_capability($systemcontext, 'moodle/site:doanything', 'u.id,firstname,lastname,lang,email,emailstop,mailformat,mnethostid', 'lastname');

    if (!empty($admins)) {
        foreach ($admins as $admin) {
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
function tracker_notify_submission($issue, &$cm, $tracker = null) {
    global $COURSE, $SITE, $CFG, $USER, $DB;

    if (empty($tracker)) { // database access optimization in case we have a tracker from somewhere else
        $tracker = $DB->get_record('tracker', array('id' => $issue->trackerid));
    }
    $context = context_module::instance($cm->id);
    $managers = get_users_by_capability($context, 'mod/tracker:manage', 'u.id,firstname,lastname,lang,email,emailstop,mailformat,mnethostid', 'lastname');

    $by = $DB->get_record('user', array('id' => $issue->reportedby));
    if (!empty($managers)) {
        $vars = array('COURSE_SHORT' => $COURSE->shortname,
                      'COURSENAME' => format_string($COURSE->fullname),
                      'TRACKERNAME' => format_string($tracker->name),
                      'ISSUE' => $tracker->ticketprefix.$issue->id,
                      'SUMMARY' => format_string($issue->summary),
                      'DESCRIPTION' => format_string(stripslashes($issue->description)),
                      'BY' => fullname($by),
                      'ISSUEURL' => $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=view&amp;screen=viewanissue&amp;issueid={$issue->id}",
                      'CCURL' => $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=profile&amp;screen=mywatches&amp;issueid={$issue->id}&amp;what=register"
                      );
        include_once($CFG->dirroot."/mod/tracker/mailtemplatelib.php");
        foreach ($managers as $manager) {
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
function tracker_notifyccs_changeownership($issueid, $tracker = null) {
    global $COURSE, $SITE, $CFG, $USER, $DB;

    $issue = $DB->get_record('tracker_issue', array('id' => $issueid));
    if (empty($tracker)) { // database access optimization in case we have a tracker from somewhere else
        $tracker = $DB->get_record('tracker', array('id' => $issue->trackerid));
    }

    $issueccs = $DB->get_records('tracker_issuecc', array('issueid' => $issue->id));
    $assignee = $DB->get_record('user', array('id' => $issue->assignedto));
    if (!empty($issueccs)) {
        $vars = array('COURSE_SHORT' => $COURSE->shortname,
                      'COURSENAME' => format_string($COURSE->fullname),
                      'TRACKERNAME' => format_string($tracker->name),
                      'ISSUE' => $tracker->ticketprefix.$issue->id,
                      'SUMMARY' => format_string($issue->summary),
                      'ASSIGNEDTO' => fullname($assignee),
                      'BY' => fullname($USER),
                      'ISSUEURL' => $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=view&amp;screen=viewanissue&amp;issueid={$issue->id}",
                      );
        include_once($CFG->dirroot.'/mod/tracker/mailtemplatelib.php');
        foreach ($issueccs as $cc) {
            $ccuser = $DB->get_record('user', array('id' => $cc->userid));
            $vars['UNCCURL'] = $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=profile&amp;screen=mywatches&amp;ccid={$cc->userid}&amp;what=unregister";
            $vars['ALLUNCCURL'] = $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=profile&amp;screen=mywatches&amp;userid={$cc->userid}&amp;what=unregisterall";
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
function tracker_notifyccs_moveissue($issueid, $tracker, $newtracker = null) {
    global $COURSE, $SITE, $CFG, $USER, $DB;

    $issue = $DB->get_record('tracker_issue', array('id' => $issueid));
    if (empty($tracker)) { // database access optimization in case we have a tracker from somewhere else
        $tracker = $DB->get_record('tracker', array('id' => $issue->trackerid));
    }
    $newcourse = $DB->get_record('course', array('id' => $newtracker->course));

    $issueccs = $DB->get_records('tracker_issuecc', array('issueid' => $issue->id));
    $assignee = $DB->get_record('user', array('id' => $issue->assignedto));
    if (!empty($issueccs)) {
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
                      'ISSUEURL' => $CFG->wwwroot."/mod/tracker/view.php?a={$newtracker->id}&amp;view=view&amp;screen=viewanissue&amp;issueid={$issue->id}",
                      );
        foreach ($issueccs as $cc) {
            $ccuser = $DB->get_record('user', array('id' => $cc->userid));
            $vars['UNCCURL'] = $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=profile&amp;screen=mywatches&amp;ccid={$cc->userid}&amp;what=unregister";
            $vars['ALLUNCCURL'] = $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=profile&amp;screen=mywatches&amp;userid={$cc->userid}&amp;what=unregisterall";
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
function tracker_notifyccs_changestate($issueid, $tracker = null) {
    global $COURSE, $SITE, $CFG, $USER, $DB;

    $issue = $DB->get_record('tracker_issue', array('id' => $issueid));
    if (empty($tracker)) { // database access optimization in case we have a tracker from somewhere else
        $tracker = $DB->get_record('tracker', array('id' => $issue->trackerid));
    }
    $issueccs = $DB->get_records('tracker_issuecc', array('issueid' => $issueid));

    if (!empty($issueccs)) {
        $vars = array('COURSE_SHORT' => $COURSE->shortname,
                      'COURSENAME' => format_string($COURSE->fullname),
                      'TRACKERNAME' => format_string($tracker->name),
                      'ISSUE' => $tracker->ticketprefix.$issueid,
                      'SUMMARY' => format_string($issue->summary),
                      'BY' => fullname($USER),
                      'ISSUEURL' => $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=view&amp;screen=viewanissue&amp;issueid={$issueid}");
        include_once($CFG->dirroot.'/mod/tracker/mailtemplatelib.php');
        foreach ($issueccs as $cc) {
            unset($notification);
            unset($notification_html);
            $ccuser = $DB->get_record('user', array('id' => $cc->userid));
            $vars['UNCCURL'] = $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=profile&amp;screen=mywatches&amp;ccid={$cc->userid}&amp;what=unregister";
            $vars['ALLUNCCURL'] = $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=profile&amp;screen=mywatches&amp;userid={$cc->userid}&amp;what=unregisterall";
            switch ($issue->status) {
                case OPEN :
                    if ($cc->events & EVENT_OPEN) {
                        $vars['EVENT'] = get_string('open', 'tracker');
                        $notification = tracker_compile_mail_template('statechanged', $vars, 'tracker', $ccuser->lang);
                        $notification_html = tracker_compile_mail_template('statechanged_html', $vars, 'tracker', $ccuser->lang);
                    }
                break;
                case RESOLVING :
                    if ($cc->events & EVENT_RESOLVING) {
                        $vars['EVENT'] = get_string('resolving', 'tracker');
                        $notification = tracker_compile_mail_template('statechanged', $vars, 'tracker', $ccuser->lang);
                        $notification_html = tracker_compile_mail_template('statechanged_html', $vars, 'tracker', $ccuser->lang);
                    }
                break;
                case WAITING :
                    if ($cc->events & EVENT_WAITING) {
                        $vars['EVENT'] = get_string('waiting', 'tracker');
                        $notification = tracker_compile_mail_template('statechanged', $vars, 'tracker', $ccuser->lang);
                        $notification_html = tracker_compile_mail_template('statechanged_html', $vars, 'tracker', $ccuser->lang);
                    }
                break;
                case RESOLVED :
                    if ($cc->events & EVENT_RESOLVED) {
                        $vars['EVENT'] = get_string('resolved', 'tracker');
                        $notification = tracker_compile_mail_template('statechanged', $vars, 'tracker', $ccuser->lang);
                        $notification_html = tracker_compile_mail_template('statechanged_html', $vars, 'tracker', $ccuser->lang);
                    }
                break;
                case ABANDONNED :
                    if ($cc->events & EVENT_ABANDONNED) {
                        $vars['EVENT'] = get_string('abandonned', 'tracker');
                        $notification = tracker_compile_mail_template('statechanged', $vars, 'tracker', $ccuser->lang);
                        $notification_html = tracker_compile_mail_template('statechanged_html', $vars, 'tracker', $ccuser->lang);
                    }
                break;
                case TRANSFERED :
                    if ($cc->events & EVENT_TRANSFERED) {
                        $vars['EVENT'] = get_string('transfered', 'tracker');
                        $notification = tracker_compile_mail_template('statechanged', $vars, 'tracker', $ccuser->lang);
                        $notification_html = tracker_compile_mail_template('statechanged_html', $vars, 'tracker', $ccuser->lang);
                    }
                break;
                case TESTING :
                    if ($cc->events & EVENT_TESTING) {
                        $vars['EVENT'] = get_string('testing', 'tracker');
                        $notification = tracker_compile_mail_template('statechanged', $vars, 'tracker', $ccuser->lang);
                        $notification_html = tracker_compile_mail_template('statechanged_html', $vars, 'tracker', $ccuser->lang);
                    }
                break;
                case PUBLISHED :
                    if ($cc->events & EVENT_PUBLISHED) {
                        $vars['EVENT'] = get_string('published', 'tracker');
                        $notification = tracker_compile_mail_template('statechanged', $vars, 'tracker', $ccuser->lang);
                        $notification_html = tracker_compile_mail_template('statechanged_html', $vars, 'tracker', $ccuser->lang);
                    }
                break;
                case VALIDATED :
                    if ($cc->events & EVENT_VALIDATED) {
                        $vars['EVENT'] = get_string('validated', 'tracker');
                        $notification = tracker_compile_mail_template('statechanged', $vars, 'tracker', $ccuser->lang);
                        $notification_html = tracker_compile_mail_template('statechanged_html', $vars, 'tracker', $ccuser->lang);
                    }
                break;
                default:
            }
            if (!empty($notification)) {
                if ($CFG->debugsmtp) echo "Sending State Change Mail Notification to " . fullname($ccuser) . '<br/>'.$notification_html;
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
function tracker_notifyccs_comment($issueid, $comment, $tracker = null) {
    global $COURSE, $SITE, $CFG, $USER, $DB;

    $issue = $DB->get_record('tracker_issue', array('id' => $issueid));
    if (empty($tracker)) { // database access optimization in case we have a tracker from somewhere else
        $tracker = $DB->get_record('tracker', array('id' => $issue->trackerid));
    }

    $issueccs = $DB->get_records('tracker_issuecc', array('issueid' => $issue->id));
    if (!empty($issueccs)) {
        $vars = array('COURSE_SHORT' => $COURSE->shortname,
                      'COURSENAME' => format_string($COURSE->fullname),
                      'TRACKERNAME' => format_string($tracker->name),
                      'ISSUE' => $tracker->ticketprefix.$issue->id,
                      'SUMMARY' => $issue->summary,
                      'COMMENT' => format_string(stripslashes($comment)),
                      'ISSUEURL' => $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=view&amp;screen=viewanissue&amp;issueid={$issue->id}",
                      );
        include_once($CFG->dirroot.'/mod/tracker/mailtemplatelib.php');
        foreach ($issueccs as $cc) {
            $ccuser = $DB->get_record('user', array('id' => $cc->userid));
            if ($cc->events & ON_COMMENT) {
                $vars['CONTRIBUTOR'] = fullname($USER);
                $vars['UNCCURL'] = $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=profile&amp;screen=mywatches&amp;ccid={$cc->userid}&amp;what=unregister";
                $vars['ALLUNCCURL'] = $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=profile&amp;screen=mywatches&amp;userid={$cc->userid}&amp;what=unregisterall";
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
function tracker_loadpreferences($trackerid, $userid = 0) {
    global $USER, $DB;

    if ($userid == 0) $userid = $USER->id;
    $preferences = $DB->get_records_select('tracker_preferences', "trackerid = ? AND userid = ? ", array($trackerid, $userid));
    if ($preferences) {
        foreach ($preferences as $preference) {
            $USER->trackerprefs = new Stdclass();
            $USER->trackerprefs->{$preference->name} = $preference->value;
        }
    }
}

/**
* prints a transfer link follow up to an available parent record
* @uses $CFG
*
*/
function tracker_print_transfer_link(&$tracker, &$issue) {
    global $CFG, $DB;
    if (empty($tracker->parent)) return '';
    if (is_numeric($tracker->parent)) {
        if (!empty($issue->followid)) {
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
function tracker_display_elementmatch($local, $remote) {

    $match = true;

    echo "<ul>";
    foreach ($remote->elements as $name => $element) {
        $description = format_string($element->description);
        if (!empty($local->elements) && in_array($name, array_keys($local->elements))) {
            if ($local->elements[$name]->type == $remote->elements[$name]->type) {
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
    if (!empty($local->elements)) {
        foreach (array_keys($local->elements) as $localelement) {
            if (!empty($remote->elements) && !in_array($localelement, array_keys($remote->elements))) {
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
function tracker_add_cascade_backlink(&$cm, &$issue) {
    global $SITE, $CFG;

    $vieworiginalstr = get_string('vieworiginal', 'tracker');
    $str = get_string('cascadedticket', 'tracker', $SITE->shortname);
    $str .= '<br/>';
    $str .= "<a href=\"{$CFG->wwwroot}/mod/tracker/view.php?id={$cm->id}&amp;view=view&amp;screen=viewanissue&amp;issueid={$issue->id}\">{$vieworiginalstr}</a><br/>";

    return $str;
}

/**
* reorder correctly the priority sequence and discard from the stack
* all resolved and abandonned entries
* @uses $CFG
* @param $reference $tracker
*/
function tracker_update_priority_stack(&$tracker) {
    global $CFG, $DB;

    // discards resolved, transferred or abandoned
    $sql = "
       UPDATE
           {tracker_issue}
       SET
           resolutionpriority = 0
       WHERE
           trackerid = $tracker->id AND
           status IN (".RESOLVED.','.ABANDONNED.','.TRANSFERED.')';
    $DB->execute($sql);

    // fetch prioritarized by order
    $issues = $DB->get_records_select('tracker_issue', "trackerid = ? AND resolutionpriority != 0 ", array($tracker->id), 'resolutionpriority', 'id, resolutionpriority');
    $i = 1;
    if (!empty($issues)) {
        foreach ($issues as $issue) {
            $issue->resolutionpriority = $i;
            $DB->update_record('tracker_issue', $issue);
            $i++;
        }
    }
}

function tracker_get_stats(&$tracker, $from = null, $to = null) {
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
    if ($results = $DB->get_records_sql($sql)) {
        foreach ($results as $r) {
            $stats[$r->status] = $r->value;
        }
    } else {
        $stats[POSTED] = 0;
        $stats[OPEN] = 0;
        $stats[RESOLVING] = 0;
        $stats[WAITING] = 0;
        $stats[TESTING] = 0;
        $stats[PUBLISHED] = 0;
        $stats[VALIDATED] = 0;
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
function tracker_get_stats_by_month(&$tracker, $from = null, $to = null) {
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
    if ($results = $DB->get_records_sql($sql)) {
        foreach ($results as $r) {
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
function tracker_backtrack_stats_by_month(&$tracker) {
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
    if ($issues = $DB->get_records_sql($sql)) {

        // dispatch issue generating events and follow change tracks
        foreach ($issues as $is) {
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
            if ($changes = $DB->get_records_sql($sql)) {
                foreach ($changes as $c) {
                    $tracks[$c->period][$c->issueid] = $c->statusto;
                }
            }
            $issuelist[$is->id] = -1;
        }

        ksort($tracks);

        $availdates = array_keys($tracks);
        $lowest = $availdates[0];
        $highest = $availdates[count($availdates) - 1];
        $low = new StdClass();
        list($low->year, $low->month) = explode('-', $lowest);
        $dateiter = new date_iterator($low->year, $low->month);

        // scan table and snapshot issue states
        $current = $dateiter->current();
        while (strcmp($current, $highest) <= 0) {
            if (array_key_exists($current, $tracks)) {
                foreach ($tracks[$current] as $trackedid => $trackedstate) {
                    $issuelist[$trackedid] = $trackedstate;
                }
            }
            $monthtracks[$current] = $issuelist;
            $dateiter->next();
            $current = $dateiter->current();
        }

        // revert and summarize states
        foreach ($monthtracks as $current => $monthtrack) {
            foreach ($monthtrack as $issueid => $state) {
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
function tracker_get_stats_by_user(&$tracker, $userclass, $from = null, $to = null) {
    global $CFG, $DB;
    $sql = "
        SELECT
            CONCAT(u.id, '-', i.status) as resultdid,
            u.id,
            ".get_all_user_name_fields(true, 'u').",
            count(*) as value,
            i.status
        FROM
            {tracker_issue} i
        LEFT JOIN
            {user} u
        ON
            i.{$userclass} = u.id
        WHERE
            trackerid = ?
        GROUP BY
            CONCAT(u.id, '-', i.status)
        ORDER BY
            u.lastname, u.firstname
    ";
    if ($results = $DB->get_records_sql($sql, array($tracker->id))) {
        foreach ($results as $r) {
            $stats[$r->id] = new StdClass();
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

    function date_iterator($year, $month) {
        $this->year = $year;
        $this->month = $month;
        $this->inityear = $year;
        $this->initmonth = $month;
    }

    function reset() {
        $this->year = $this->inityear;
        $this->month = $this->initmonth;
    }

    function next() {
        $this->month++;
        if ($this->month > 12) {
            $this->month = 1;
            $this->year++;
        }
    }

    function current() {
        return $this->year.'-'.sprintf('%02d', $this->month);
    }

    function getyear() {
        return $this->year;
    }

    function getmonth() {
        return $this->month;
    }

    function getiterations($highest) {
        $year = $this->year;
        $month = $this->month;
        $current = $year.'-'.sprintf('%02d', $month);
        $i = 0;
        while (strcmp($current, $highest) <= 0) {
            $i++;
            $month++;
            if ($month > 12) {
                $month = 1;
                $year++;
            }
            $current = $year.'-'.sprintf('%02d', $month);
        }
        return $i;
    }
}

/**
* Initializes a full featured moodle text editor outside a moodle form context.
* This allow making custom forms with free HMTL layout.
*/
function tracker_print_direct_editor($attributes, $values, $options) {
    global $CFG, $PAGE;

    require_once($CFG->dirroot.'/repository/lib.php');

    $ctx = $options['context'];

    $id           = $attributes['id'];
    $elname       = $attributes['name'];

    $subdirs      = @$options['subdirs'];
    $maxbytes     = @$options['maxbytes'];
    $areamaxbytes = @$options['areamaxbytes'];
    $maxfiles     = @$options['maxfiles'];
    $changeformat = @$options['changeformat']; // TO DO: implement as ajax calls

    $text         = $values['text'];
    $format       = $values['format'];
    $draftitemid  = $values['itemid'];

    // security - never ever allow guest/not logged in user to upload anything
    if (isguestuser() or !isloggedin()) {
        $maxfiles = 0;
    }

    // $str = $this->_getTabs();
    $str = '';
    $str .= '<div>';

    $editor = editors_get_preferred_editor($format);
    $strformats = format_text_menu();
    $formats =  $editor->get_supported_formats();
    foreach ($formats as $fid) {
        $formats[$fid] = $strformats[$fid];
    }

    // get filepicker info
    // $fpoptions = array();
    if ($maxfiles != 0 ) {
        if (empty($draftitemid)) {
            // no existing area info provided - let's use fresh new draft area
            require_once("$CFG->libdir/filelib.php");
            $draftitemid = file_get_unused_draft_itemid();
            echo " Generating fresh filearea $draftitemid ";
        }

        $args = new stdClass();
        // need these three to filter repositories list
        $args->accepted_types = array('web_image');
        $args->return_types = @$options['return_types'];
        $args->context = $ctx;
        $args->env = 'filepicker';
        // advimage plugin
        $image_options = initialise_filepicker($args);
        $image_options->context = $ctx;
        $image_options->client_id = uniqid();
        $image_options->maxbytes = @$options['maxbytes'];
        $image_options->areamaxbytes = @$options['areamaxbytes'];
        $image_options->env = 'editor';
        $image_options->itemid = $draftitemid;

        // moodlemedia plugin
        $args->accepted_types = array('video', 'audio');
        $media_options = initialise_filepicker($args);
        $media_options->context = $ctx;
        $media_options->client_id = uniqid();
        $media_options->maxbytes  = @$options['maxbytes'];
        $media_options->areamaxbytes  = @$options['areamaxbytes'];
        $media_options->env = 'editor';
        $media_options->itemid = $draftitemid;

        // advlink plugin
        $args->accepted_types = '*';
        $link_options = initialise_filepicker($args);
        $link_options->context = $ctx;
        $link_options->client_id = uniqid();
        $link_options->maxbytes  = @$options['maxbytes'];
        $link_options->areamaxbytes  = @$options['areamaxbytes'];
        $link_options->env = 'editor';
        $link_options->itemid = $draftitemid;

        $fpoptions['image'] = $image_options;
        $fpoptions['media'] = $media_options;
        $fpoptions['link'] = $link_options;
    }

    //If editor is required and tinymce, then set required_tinymce option to initalize tinymce validation.
    if (($editor instanceof tinymce_texteditor)  && !empty($attributes['onchange'])) {
        $options['required'] = true;
    }

    // print text area - TODO: add on-the-fly switching, size configuration, etc.
    $editor->use_editor($id, $options, $fpoptions);

    $rows = empty($attributes['rows']) ? 15 : $attributes['rows'];
    $cols = empty($attributes['cols']) ? 80 : $attributes['cols'];

    //Apply editor validation if required field
    $editorrules = '';
    if (!empty($attributes['onblur']) && !empty($attributes['onchange'])) {
        $editorrules = ' onblur="'.htmlspecialchars($attributes['onblur']).'" onchange="'.htmlspecialchars($attributes['onchange']).'"';
    }
    $str .= '<div><textarea id="'.$id.'" name="'.$elname.'[text]" rows="'.$rows.'" cols="'.$cols.'"'.$editorrules.'>';
    $str .= s($text);
    $str .= '</textarea></div>';

    $str .= '<div>';
    if (count($formats)>1) {
        $str .= html_writer::label(get_string('format'), 'menu'. $elname. 'format', false, array('class' => 'accesshide'));
        $str .= html_writer::select($formats, $elname.'[format]', $format, false, array('id' => 'menu'. $elname. 'format'));
    } else {
        $keys = array_keys($formats);
        $str .= html_writer::empty_tag('input',
                array('name' => $elname.'[format]', 'type' => 'hidden', 'value' => array_pop($keys)));
    }
    $str .= '</div>';

    // during moodle installation, user area doesn't exist
    // so we need to disable filepicker here.
    if (!during_initial_install() && empty($CFG->adminsetuppending)) {
        // 0 means no files, -1 unlimited
        if ($maxfiles != 0 ) {
            $str .= '<input type="hidden" name="'.$elname.'[itemid]" value="'.$draftitemid.'" />';

            // used by non js editor only
            $editorurl = new moodle_url("$CFG->wwwroot/repository/draftfiles_manager.php", array(
                'action' => 'browse',
                'env' => 'editor',
                'itemid' => $draftitemid,
                'subdirs' => $subdirs,
                'maxbytes' => $maxbytes,
                'areamaxbytes' => $areamaxbytes,
                'maxfiles' => $maxfiles,
                'ctx_id' => $ctx->id,
                'course' => $PAGE->course->id,
                'sesskey' => sesskey(),
                ));
            $str .= '<noscript>';
            $str .= "<div><object type='text/html' data='$editorurl' height='160' width='600' style='border:1px solid #000'></object></div>";
            $str .= '</noscript>';
        }
    }

    $str .= '</div>';

    return $str;
}

/**
* get all active keys for ticket states? As this may be required for all tickets in a print list, we cache it
* @param object $tracker the tracker instance
* @param object $cm the course module. If given, only role accessible keys will be output
*/
function tracker_get_statuskeys($tracker, $cm = null) {
    static $FULLSTATUSKEYS;
    static $STATUSKEYS;

    if (!isset($FULLSTATUSKEYS)) {
        $FULLSTATUSKEYS = array(
            POSTED => get_string('posted', 'tracker'),
            OPEN => get_string('open', 'tracker'),
            RESOLVING => get_string('resolving', 'tracker'),
            WAITING => get_string('waiting', 'tracker'),
            TESTING => get_string('testing', 'tracker'),
            VALIDATED => get_string('validated', 'tracker'),
            PUBLISHED => get_string('published', 'tracker'),
            RESOLVED => get_string('resolved', 'tracker'),
            ABANDONNED => get_string('abandonned', 'tracker'),
            TRANSFERED => get_string('transfered', 'tracker')
        );

        if (!($tracker->enabledstates & ENABLED_OPEN)) {
            unset($FULLSTATUSKEYS[OPEN]);
        }
        if (!($tracker->enabledstates & ENABLED_RESOLVING)) {
            unset($FULLSTATUSKEYS[RESOLVING]);
        }
        if (!($tracker->enabledstates & ENABLED_WAITING)) {
            unset($FULLSTATUSKEYS[WAITING]);
        }
        if (!($tracker->enabledstates & ENABLED_TESTING)) {
            unset($FULLSTATUSKEYS[TESTING]);
        }
        if (!($tracker->enabledstates & ENABLED_VALIDATED)) {
            unset($FULLSTATUSKEYS[VALIDATED]);
        }
        if (!($tracker->enabledstates & ENABLED_PUBLISHED)) {
            unset($FULLSTATUSKEYS[PUBLISHED]);
        }
        if (!($tracker->enabledstates & ENABLED_RESOLVED)) {
            unset($FULLSTATUSKEYS[RESOLVED]);
        }
        if (!($tracker->enabledstates & ENABLED_ABANDONNED)) {
            unset($FULLSTATUSKEYS[ABANDONNED]);
        }
        if (empty($tracker->parent)) {
            unset($FULLSTATUSKEYS[TRANSFERED]);
        }
    }

    if (!empty($tracker->strictworkflow) && $cm) {
        if (!isset($STATUSKEYS)) {
            $context = context_module::instance($cm->id);

            $STATUSKEYS = array();

            if (has_capability('mod/tracker:report', $context)) {
                $roledef = shop_get_role_definition($tracker, 'report');
                foreach ($FULLSTATUSKEYS as $key => $label) {
                    $eventkey = pow(2,$key);
                    if ($eventkey & $roledef) {
                        $STATUSKEYS[$key] = $label;
                    }
                }
            }
            if (has_capability('mod/tracker:develop', $context)) {
                $roledef = shop_get_role_definition($tracker, 'develop');
                foreach ($FULLSTATUSKEYS as $key => $label) {
                    $eventkey = pow(2,$key);
                    if ($eventkey & $roledef) {
                        $STATUSKEYS[$key] = $label;
                    }
                }
            }
            if (has_capability('mod/tracker:resolve', $context)) {
                $roledef = shop_get_role_definition($tracker, 'resolve');
                foreach ($FULLSTATUSKEYS as $key => $label) {
                    $eventkey = pow(2,$key);
                    if ($eventkey & $roledef) {
                        $STATUSKEYS[$key] = $label;
                    }
                }
            }
            if (has_capability('mod/tracker:manage', $context)) {
                $roledef = shop_get_role_definition($tracker, 'manage');
                foreach ($FULLSTATUSKEYS as $key => $label) {
                    $eventkey = pow(2,$key);
                    if ($eventkey & $roledef) {
                        $STATUSKEYS[$key] = $label;
                    }
                }
            }
        } else {
            // echo "using cache";
        }
        return $STATUSKEYS;
    }

    return $FULLSTATUSKEYS;
}

// allows array reduction for state profiles
function tracker_ror($v, $w) {
    $v |= $w;
    return $v;
}

/**
 *
 *
 */
function tracker_resolve_view(&$tracker, &$cm) {
    global $SESSION;

    $context = context_module::instance($cm->id);

    $view = optional_param('view', @$SESSION->tracker_current_view, PARAM_ALPHA);
    if (empty($view)) {
        $defaultview = 'view';
        $view = $defaultview;
    }

    $SESSION->tracker_current_view = $view;
    return $view;
}

/**
 *
 *
 */
function tracker_resolve_screen(&$tracker, &$cm) {
    global $SESSION;

    $context = context_module::instance($cm->id);

    $screen = optional_param('screen', @$SESSION->tracker_current_screen, PARAM_ALPHA);
    if (empty($screen)) {
        if (has_capability('mod/tracker:develop', $context)) {
            $defaultscreen = 'mywork';
        } elseif (has_capability('mod/tracker:report', $context)) {
            $defaultscreen = 'mytickets';
        } else {
            $defaultscreen = 'browse'; // report
        }
        $screen = $defaultscreen;
    }

    // Some forced modes
    if ($tracker->supportmode == 'taskspread' && @$SESSION->tracker_current_view == 'view') {
        if (has_capability('mod/tracker:develop', $context) && ($screen != 'viewanissue')) {
            $screen = 'mywork';
        }
    }

    // Some forced modes
    if ($tracker->supportmode == 'taskspread' && @$SESSION->tracker_current_view == 'resolved') {
        if (has_capability('mod/tracker:develop', $context) && ($screen != 'viewanissue')) {
            $screen = 'mywork';
        }
    }

    $SESSION->tracker_current_screen = $screen;
    return $screen;
}

/**
 * Conditions for people having access to ticket full edition
 *
 */
function tracker_can_edit(&$tracker, &$context, &$issue) {
    global $USER;

    if (has_capability('mod/tracker:manage', $context)) {
        return true;
    }

    if ($issue->reportedby == $USER->id) {
        return true;
    }

    if ($issue->assignedto == $USER->id && has_capability('mod/tracker:resolve', $context)) {
        return true;
    }

    return false;
}

/**
 * Conditions for people authorized to work on : ticket editor (but non owner)
 * this is used for opening tickets when viweing
 * @see views/viewanissue.php
 */
function tracker_can_workon(&$tracker, &$context, $issue = null) {
    global $USER;

    if (has_capability('mod/tracker:develop', $context)) {
        return true;
    }

    if ($issue) {
        if ($issue->assignedto == $USER->id && has_capability('mod/tracker:resolve', $context)) {
            return true;
        }
    } else {
        if (has_capability('mod/tracker:resolve', $context)) {
            return true;
        }
    }

    return false;
}

function tracker_has_assigned($tracker, $resolved = false) {
    global $DB, $USER;

    $select = '
        trackerid = ? AND
        assignedto = ?
    ';

    if ($resolved) {
        $select .= '
            AND
            status IN ('.RESOLVED.','.ABANDONNED.','.VALIDATED.')
        ';
    } else {
        $select .= '
            AND
            status NOT IN ('.RESOLVED.','.ABANDONNED.','.VALIDATED.')
        ';
    }

    return $DB->count_records_select('tracker_issue', $select, array($tracker->id, $USER->id));
}

function tracker_check_jquery() {
    global $PAGE, $JQUERYVERSION;

    $current = '1.8.2';

    if (empty($JQUERYVERSION)) {
        $JQUERYVERSION = '1.8.2';
        $PAGE->requires->js('/mod/tracker/js/jquery-'.$current.'.min.js', true);
    } else {
        if ($JQUERYVERSION < $current) {
            debugging('the previously loaded version of jquery is lower than required. This may cause issues to tracker reports. Programmers might consider upgrading JQuery version in the component that preloads JQuery library.', DEBUG_DEVELOPER, array('notrace'));
        }
    }
}
