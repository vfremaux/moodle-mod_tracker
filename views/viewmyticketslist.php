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
 * A view of owned issues
 * @package mod-tracker
 * @category mod
 * @author Clifford Thamm, Valery Fremaux > 1.8
 * @date 02/12/2007
 *
 * Print Bug List
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');

// Get search engine related information.
// Fields can come from a stored query,or from the current query in the user's client environement cookie.
if (!isset($fields)) {
    $fields = tracker_extractsearchcookies();
}
if (!empty($fields)) {
    $searchqueries = tracker_constructsearchqueries($tracker->id, $fields, true);
}

$limit = 20;
$page = optional_param('page', 1, PARAM_INT);

if ($page <= 0) {
    $page = 1;
}

if (isset($searchqueries)) {
    $sql = $searchqueries->search;
    $numrecords = $DB->count_records_sql($searchqueries->count);
} else {
    if ($resolved) {
        $resolvedclause = " AND
           (status = ".RESOLVED." OR
           status = ".ABANDONNED.")
        ";
    } else {
        $resolvedclause = " AND
           status <> ".RESOLVED." AND
           status <> ".ABANDONNED."
        ";
    }

    $sql = "
        SELECT
            i.id,
            i.summary,
            i.datereported,
            i.assignedto,
            i.status,
            i.resolutionpriority,
            COUNT(ic.issueid) AS watches
        FROM
            {tracker_issue} i
        LEFT JOIN
            {tracker_issuecc} ic
        ON
            ic.issueid = i.id
        WHERE
            i.reportedby = {$USER->id} AND
            i.trackerid = {$tracker->id}
            $resolvedclause
        GROUP BY
            i.id,
            i.summary,
            i.datereported,
            i.assignedto,
            i.status,
            i.resolutionpriority
    ";

    $sqlcount = "
        SELECT
            COUNT(*)
        FROM
            {tracker_issue} i
        WHERE
            i.reportedby = {$USER->id} AND
            i.trackerid = {$tracker->id}
            $resolvedclause
    ";
    $numrecords = $DB->count_records_sql($sqlcount);
}

// Display list of my issues.
echo $output;
echo $renderer->search_queries($cm);

$formurl = new moodle_url('/mod/tracker.view.php');
echo '<form name="manageform" action="'.$formurl.'" method="post">';
echo '<input type="hidden" name="id" value="'.$cm->id.'" />';
echo '<input type="hidden" name="what" value="updatelist" />';
echo '<input type="hidden" name="view" value="resolved" />';

// Define table object.

$prioritystr = get_string('priority', 'tracker');
$issuenumberstr = get_string('issuenumber', 'tracker');
$summarystr = get_string('summary', 'tracker');
$datereportedstr = get_string('datereported', 'tracker');
$assignedtostr = get_string('assignedto', 'tracker');
$statusstr = get_string('status', 'tracker');
$watchesstr = get_string('watches', 'tracker');
$actionstr = '';

if (!empty($tracker->parent)) {
    $transferstr = get_string('transfer', 'tracker');
    if (has_capability('mod/tracker:viewpriority', $context) && !$resolved) {
        $tablecolumns = array('statusthin', 'resolutionpriority', 'id', 'summary', 'datereported', 'assignedto', 'status',
                              'watches', 'transfered', 'action');
        $tableheaders = array('', "<b>$prioritystr</b>", "<b>$issuenumberstr</b>", "<b>$summarystr</b>",
                              "<b>$datereportedstr</b>", "<b>$assignedtostr</b>", "<b>$statusstr</b>", "<b>$watchesstr</b>",
                              "<b>$transferstr</b>", "<b>$actionstr</b>");
    } else {
        $tablecolumns = array('statusthin', 'id', 'summary', 'datereported', 'assignedto', 'status', 'watches', 'transfered',
                              'action');
        $tableheaders = array('', "<b>$issuenumberstr</b>", "<b>$summarystr</b>", "<b>$datereportedstr</b>",
                              "<b>$assignedtostr</b>", "<b>$statusstr</b>", "<b>$watchesstr</b>", "<b>$transferstr</b>",
                              "<b>$actionstr</b>");
    }
} else {
    if (has_capability('mod/tracker:viewpriority', $context) && !$resolved) {
        $tablecolumns = array('statusthin', 'resolutionpriority', 'id', 'summary', 'datereported', 'assignedto', 'status',
                              'watches',  'action');
        $tableheaders = array('', "<b>$prioritystr</b>", '', "<b>$summarystr</b>", "<b>$datereportedstr</b>",
                              "<b>$assignedtostr</b>", "<b>$statusstr</b>", "<b>$watchesstr</b>", "<b>$actionstr</b>");
    } else {
        $tablecolumns = array('statusthin', 'id', 'summary', 'datereported', 'assignedto', 'status', 'watches',  'action');
        $tableheaders = array('', '', "<b>$summarystr</b>", "<b>$datereportedstr</b>", "<b>$assignedtostr</b>",
                              "<b>$statusstr</b>", "<b>$watchesstr</b>", "<b>$actionstr</b>");
    }
}

$table = new flexible_table('mod-tracker-issuelist');
$table->define_columns($tablecolumns);
$table->define_headers($tableheaders);

$table->define_baseurl($CFG->wwwroot.'/mod/tracker/view.php?id='.$cm->id.'&view='.$view.'&screen='.$screen);

$table->sortable(true, 'datereported', SORT_DESC); // Sorted by datereported by default.
$table->collapsible(true);
$table->initialbars(true);

$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'issues');
$table->set_attribute('class', 'issuelist');
$table->set_attribute('width', '100%');

$table->column_class('statusthin', 'list_statusthin');
$table->column_class('resolutionpriority', 'list_priority');
$table->column_class('id', 'list_issuenumber');
$table->column_class('summary', 'list_summary');
$table->column_class('datereported', 'timelabel');
$table->column_class('assignedto', 'list_assignedto');
$table->column_class('watches', 'list_watches');
$table->column_class('status', 'list_status');
$table->column_class('action', 'list_action');
if (!empty($tracker->parent)) {
    $table->column_class('transfered', 'list_transfered');
}

$table->setup();

// Get extra query parameters from flexible_table behaviour.
$where = $table->get_sql_where();
$sort = $table->get_sql_sort();
$table->pagesize($limit, $numrecords);

if (!empty($sort)) {
    $sql .= " ORDER BY $sort";
}

$issues = $DB->get_records_sql($sql, null, $table->get_page_start(), $table->get_page_size());
$select = " trackerid = ? GROUP BY trackerid ";
$maxpriority = $DB->get_field_select('tracker_issue', 'MAX(resolutionpriority)', $select, array($tracker->id));

$fullstatuskeys = tracker_get_statuskeys($tracker);
$statuskeys = tracker_get_statuskeys($tracker, $cm);
$statuskeys[0] = get_string('nochange', 'tracker');
$statuscodes = tracker_get_statuscodes();

if (!empty($issues)) {
    // Product data for table.
    $developersmenu = array();
    foreach ($issues as $issue) {

        $statusthin = '<div class="status-'.$statuscodes[$issue->status].' tracker-status-thin"></div>';

        $params = array('id' => $cm->id, 'view' => 'view', 'issueid' => $issue->id);
        $ticketurl = new moodle_url('/mod/tracker/view.php', $params);
        $issuenumber = '<a href="'.$ticketurl.'">'.$tracker->ticketprefix.$issue->id.'</a>';

        $params = array('id' => $cm->id, 'view' => 'view', 'screen' => 'viewanissue', 'issueid' => $issue->id);
        $summaryurl = new moodle_url('/mod/tracker/view.php', $params);
        $summary = '<a href="'.$summaryurl.'">'.format_string($issue->summary).'</a>';

        $datereported = date('Y/m/d H:i', $issue->datereported);

        $user = $DB->get_record('user', array('id' => $issue->assignedto));

        if (has_capability('mod/tracker:manage', $context)) {
            // Managers can assign bugs.
            $status = $fullstatuskeys[0 + $issue->status].'<br/>';
            $attrs = array('onchange' => "document.forms['manageform'].schanged{$issue->id}.value = 1;");
            $status .= html_writer::select($statuskeys, "status{$issue->id}", 0, array(), $attrs);

            $fields = 'u.id,'.get_all_user_name_fields(true, 'u');
            $developers = get_users_by_capability($context, 'mod/tracker:develop', $fields, 'lastname');
            foreach ($developers as $developer) {
                $developersmenu[$developer->id] = fullname($developer);
            }
            $attrs = array('onchange' => "document.forms['manageform'].changed{$issue->id}.value = 1;");
            $nochoose = array('' => get_string('unassigned', 'tracker'));
            $assignedto = html_writer::select($developersmenu, 'assignedto'.$issue->id, $issue->assignedto, $nochoose, $attrs);

        } else if (has_capability('mod/tracker:resolve', $context)) {
            // Resolvers can give a bug back to managers.
            $status = $fullstatuskeys[0 + $issue->status].'<br/>';
            $attrs = array('onchange' => 'document.forms[\'manageform\'].schanged'.$issue->id.'.value = 1;');
            $status .= html_writer::select($statuskeys, 'status'.$issue->id, 0, array(), $attrs);
            $managers = get_users_by_capability($context, 'mod/tracker:manage', 'u.id,'.get_all_user_name_fields(true, 'u'), 'lastname');
            foreach ($managers as $manager) {
                $managersmenu[$manager->id] = fullname($manager);
            }
            $managersmenu[$USER->id] = fullname($USER);
            $attrs = array('onchange' => 'document.forms[\'manageform\'].changed'.$issue->id.'.value = 1;');
            $nochoose = array('' => get_string('unassigned', 'tracker'));
            $assignedto = html_writer::select($developersmenu, 'assignedto'.$issue->id, $issue->assignedto, $nochoose, $attrs);
        } else {
            $status = $fullstatuskeys[0 + $issue->status];
            $assignedto = fullname($user);
        }
        $status = '<div class="status-'.$statuscodes[$issue->status].'" class="tracker-status">'.$status.'</div>';
        $hassolution = $issue->status == RESOLVED && !empty($issue->resolution);
        $alt = get_string('hassolution', 'tracker');
        $pix = $OUTPUT->pix_icon('solution', $alt, 'mod_tracker');
        $solution = ($hassolution) ? $pix : '';
        $actions = '';
        if (has_capability('mod/tracker:manage', $context) ||
                has_capability('mod/tracker:resolve', $context)) {
            $params = array('id' => $cm->id, 'view' => 'resolved', 'issueid' => $issue->id, 'screen' => 'editanissue');
            $actionurl = new moodle_url('/mod/tracker/view.php', $params);
            $alt = get_string('update');
            $pix = $OUTPUT->pix_icon('t/edit', $alt, 'core');
            $actions = '<a href="'.$actionurl.'" title="'.$alt.'" >'.$pix.'</a>';
        }
        if (has_capability('mod/tracker:manage', $context)) {
            $params = array('id' => $cm->id, 'view' => 'resolved', 'issueid' => $issue->id, 'what' => 'delete');
            $deleteurl = new moodle_url('/mod/tracker/view.php', $params);
            $alt = get_string('delete');
            $pix = $OUTPUT->pix_icon('t/delete', $alt, 'core');
            $actions .= '&nbsp;<a href="'.$deleteurl.'" title="'.$alt.'" >'.$pix.'</a>';
        }
        // Ergo Report I3 2012 => self list displays owned tickets. Already registered.
        if (tracker_supports_feature('priority/askraise')) {
            if ($issue->resolutionpriority < $maxpriority &&
                    has_capability('mod/tracker:viewpriority', $context) &&
                            !has_capability('mod/tracker:managepriority', $context)) {
                $params = array('id' => $cm->id, 'issueid' => $issue->id);
                $raiseurl = new moodle_url('/mod/tracker/pro/raiserequest.php', $params);
                $alt = get_string('askraise', 'tracker');
                $pix = $OUTPUT->pix_icon('askraise', $alt, 'mod_tracker');
                $actions .= '&nbsp;<a href="'.$raiseurl.'" title="'..'" >'.$pix.'</a>';
            }
        }
        if (!empty($tracker->parent)) {
            $transfer = ($issue->status == TRANSFERED) ? tracker_print_transfer_link($tracker, $issue) : '';
            if (has_capability('mod/tracker:viewpriority', $context) &&
                    !$resolved) {
                $cond = ($issue->status < RESOLVED || $issue->status == TESTING);
                $ticketpriority = $cond ? $maxpriority - $issue->resolutionpriority + 1 : '';
                $dataset = array($statusthin, $ticketpriority, $issuenumber, $summary.' '.$solution, $datereported, $assignedto,
                                 $status, 0 + $issue->watches, $transfer, $actions);
            } else {
                $dataset = array($statusthin, $issuenumber, $summary.' '.$solution, $datereported, $assignedto, $status,
                                 0 + $issue->watches, $transfer, $actions);
            }
        } else {
            if (has_capability('mod/tracker:viewpriority', $context) &&
                    !$resolved) {
                $cond = ($issue->status < RESOLVED || $issue->status == TESTING);
                $ticketpriority = $cond ? $maxpriority - $issue->resolutionpriority + 1 : '';
                $dataset = array($statusthin, $ticketpriority, $issuenumber, $summary.' '.$solution, $datereported, $assignedto,
                                 $status, 0 + $issue->watches, $actions);
            } else {
                $dataset = array($statusthin, $issuenumber, $summary.' '.$solution, $datereported, $assignedto, $status,
                                 0 + $issue->watches, $actions);
            }
        }
        $table->add_data($dataset);
    }
    $table->print_html();

    echo '<center>';
    echo '<p><input type="submit" name="go_btn" value="'.get_string('savechanges').'" /></p>';
    echo '</center>';
} else {
    echo '<br/>';
    echo '<br/>';
    echo $OUTPUT->notification(get_string('notickets', 'tracker'), 'box generalbox', 'notice');
}

echo '</form>';
