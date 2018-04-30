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
 * @package mod_tracker
 * @category mod
 * @author Clifford Thamm, Valery Fremaux > 1.8
 * @date 02/12/2007
 *
 * Print Bug List
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');

$fullstatuskeys = tracker_get_statuskeys($tracker);
$statuskeys = tracker_get_statuskeys($tracker, $cm);
$statuskeys[0] = get_string('nochange', 'tracker');
$statuscodes = tracker_get_statuscodes();

/*
 * Get search engine related information
 * fields can come from a stored query,or from the current query in the user's client environement cookie
 */
if (!isset($fields)) {
    $fields = tracker_extractsearchcookies();
}

if (!empty($fields)) {
    $searchqueries = tracker_constructsearchqueries($tracker->id, $fields);
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
    // Check we display only resolved tickets or working.
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
            i.reportedby,
            i.assignedto,
            i.status,
            i.resolutionpriority,
            u.firstname firstname,
            u.lastname lastname,
            COUNT(ic.issueid) watches
        FROM
            {tracker_issue} i
        LEFT JOIN
            {tracker_issuecc} ic
        ON
            ic.issueid = i.id
        LEFT JOIN
            {user} u
        ON
            i.reportedby = u.id
        WHERE
            i.reportedby = u.id AND
            i.trackerid = {$tracker->id}
            $resolvedclause
        GROUP BY
            i.id,
            i.summary,
            i.datereported,
            i.reportedby,
            i.assignedto,
            i.status,
            i.resolutionpriority,
            u.firstname,
            u.lastname
    ";

    $sqlcount = "
        SELECT
            COUNT(*)
        FROM
            {tracker_issue} i,
            {user} u
        WHERE
            i.reportedby = u.id AND
            i.trackerid = {$tracker->id}
            $resolvedclause
    ";
    $numrecords = $DB->count_records_sql($sqlcount);
}

// Display list of issues.
echo $output;
echo '<center>';
echo '<table border="1" width="100%">';

if (isset($searchqueries)) {

    echo '<tr>';
    echo '<td colspan="2">';
    echo get_string('searchresults', 'tracker').': '.$numrecords.' <br/>';
    echo '</td>';
    echo '<td colspan="2" align="right">';
    $params = array('id' => $cm->id, 'view' => 'view', 'screen' => 'browse', 'what' => 'clearsearch');
    $clearurl = new moodle_url('/mod/tracker/view.php', $params);
    echo '<a href="'.$clearurl.'">'.get_string('clearsearch', 'tracker').'</a>';
    echo '</td>';
    echo '</tr>';
}

echo '</table>';
echo '</center>';
echo '<form name="manageform" action="view.php" method="post">';
echo '<input type="hidden" name="id" value="'.$cm->id.'" />';
echo '<input type="hidden" name="what" value="updatelist" />';
echo '<input type="hidden" name="view" value="view" />';
echo '<input type="hidden" name="screen" value="browse" />';

// Define table object.
$prioritystr = get_string('priority', 'tracker');
$issuenumberstr = get_string('issuenumber', 'tracker');
$summarystr = get_string('summary', 'tracker');
$datereportedstr = get_string('datereported', 'tracker');
$reportedbystr = get_string('reportedby', 'tracker');
$assignedtostr = get_string('assignedto', 'tracker');
$statusstr = get_string('status', 'tracker');
$watchesstr = get_string('watches', 'tracker');
$actionstr = '';
if ($resolved) {
    if (!empty($tracker->parent)) {
        $transferstr = get_string('transfer', 'tracker');
        $tablecolumns = array('id', 'summary', 'datereported', 'reportedby', 'assignedto', 'status', 'watches',
                              'transfered', 'action');
        $tableheaders = array("<b>$issuenumberstr</b>", "<b>$summarystr</b>", "<b>$datereportedstr</b>", "<b>$reportedbystr</b>",
                              "<b>$assignedtostr</b>", "<b>$statusstr</b>", "<b>$watchesstr</b>", "<b>$transferstr</b>",
                              "<b>$actionstr</b>");
    } else {
        $tablecolumns = array('id', 'summary', 'datereported', 'reportedby', 'assignedto', 'status', 'watches', 'action');
        $tableheaders = array("<b>$issuenumberstr</b>", "<b>$summarystr</b>", "<b>$datereportedstr</b>", "<b>$reportedbystr</b>",
                              "<b>$assignedtostr</b>", "<b>$statusstr</b>", "<b>$watchesstr</b>", "<b>$actionstr</b>");
    }
} else {
    if (!empty($tracker->parent)) {
        $transferstr = get_string('transfer', 'tracker');
        $tablecolumns = array('resolutionpriority', 'id', 'summary', 'datereported', 'reportedby', 'assignedto', 'status',
                              'watches', 'transfered', 'action');
        $tableheaders = array("<b>$prioritystr</b>", "<b>$issuenumberstr</b>", "<b>$summarystr</b>", "<b>$datereportedstr</b>",
                              "<b>$reportedbystr</b>", "<b>$assignedtostr</b>", "<b>$statusstr</b>", "<b>$watchesstr</b>",
                              "<b>$transferstr</b>", "<b>$actionstr</b>");
    } else {
        $tablecolumns = array('resolutionpriority', 'id', 'summary', 'datereported', 'reportedby', 'assignedto', 'status',
                              'watches', 'action');
        $tableheaders = array("<b>$prioritystr</b>", "<b>$issuenumberstr</b>", "<b>$summarystr</b>", "<b>$datereportedstr</b>",
                              "<b>$reportedbystr</b>", "<b>$assignedtostr</b>", "<b>$statusstr</b>", "<b>$watchesstr</b>",
                              "<b>$actionstr</b>");
    }
}

$table = new flexible_table('mod-tracker-issuelist');
$table->define_columns($tablecolumns);
$table->define_headers($tableheaders);

$table->define_baseurl(new moodle_url('/mod/tracker/view.php', array('id' => $cm->id, 'view' => $view, 'screen' => $screen)));

$table->sortable(true, 'resolutionpriority', SORT_ASC); // Sorted by priority by default.
$table->collapsible(true);
$table->initialbars(true);

// Allow column hiding.

$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'issues');
$table->set_attribute('class', 'issuelist');
$table->set_attribute('width', '100%');

$table->column_class('resolutionpriority', 'list-priority');
$table->column_class('id', 'list-issuenumber');
$table->column_class('summary', 'list-summary');
$table->column_class('datereported', 'timelabel');
$table->column_class('reportedby', 'list-reportedby');
$table->column_class('assignedto', 'list-assignedto');
$table->column_class('watches', 'list-watches');
$table->column_class('status', 'list-status');
$table->column_class('action', 'list-action');

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
} else {
    $sql .= " ORDER BY resolutionpriority ASC";
}

$issues = $DB->get_records_sql($sql, null, $table->get_page_start(), $table->get_page_size());

$select = " trackerid = ? GROUP BY trackerid ";
$maxpriority = $DB->get_field_select('tracker_issue', 'MAX(resolutionpriority)', $select, array($tracker->id));

if (!empty($issues)) {
    // Product data for table.
    foreach ($issues as $issue) {
        $params = array('id' => $cm->id, 'view' => 'view', 'screen' => 'viewanissue', 'issueid' => $issue->id);
        $issueurl = new moodle_url('/mod/tracker/view.php', $params);
        $issuenumber = '<a href="'.$issueurl.'">'.$tracker->ticketprefix.$issue->id.'</a>';
        $summary = format_string($issue->summary);
        $datereported = date('Y/m/d H:i', $issue->datereported);
        $user = $DB->get_record('user', array('id' => $issue->reportedby));
        $reportedby = fullname($user);
        $assignedto = '';
        $user = $DB->get_record('user', array('id' => $issue->assignedto));
        if (has_capability('mod/tracker:manage', $context)) {
            // Managers can assign bugs.
            $status = $fullstatuskeys[0 + $issue->status].'<br/>';
            $attrs = array('onchange' => "document.forms['manageform'].schanged{$issue->id}.value = 1;");
            $status .= html_writer::select($statuskeys, "status{$issue->id}", 0, array('' => 'choose'), $attrs);
            $status .= '<input type="hidden" name="schanged'.$issue->id.'" value="0" />';
            $developers = tracker_getdevelopers($context);
            if (!empty($developers)) {
                $developersmenu = array();
                foreach ($developers as $developer) {
                    $developersmenu[$developer->id] = fullname($developer);
                }
                $nochoice = array('' => get_string('unassigned', 'tracker'));
                $attrs = array('onchange' => "document.forms['manageform'].changed{$issue->id}.value = 1;");
                $assignedto = html_writer::select($developersmenu, "assignedto{$issue->id}", $issue->assignedto, $nochoice, $attrs);
                $assignedto .= '<input type="hidden" name="changed'.$issue->id.'" value="0" />';
            }
        } else if (has_capability('mod/tracker:resolve', $context)) {
            // Resolvers can give a bug back to managers.
            $status = $fullstatuskeys[0 + $issue->status].'<br/>';
            $attrs = array('onchange' => "document.forms['manageform'].schanged{$issue->id}.value = 1;");
            $status .= html_writer::select($statuskeys, "status{$issue->id}", 0, array('' => 'choose'), $attrs);
            $status .= '<input type="hidden" name="schanged'.$issue->id.'" value="0" />';
            $managers = tracker_getadministrators($context);
            if (!empty($managers)) {
                foreach ($managers as $manager) {
                    $managersmenu[$manager->id] = fullname($manager);
                }
                $managersmenu[$USER->id] = fullname($USER);
                $nochoice = array('' => get_string('unassigned', 'tracker'));
                $attrs = array('onchange' => "document.forms['manageform'].changed{$issue->id}.value = 1;");
                $assignedto = html_writer::select($managersmenu, "assignedto{$issue->id}", $issue->assignedto, $nochoice, $attrs);
                $assignedto .= '<input type="hidden" name="changed'.$issue->id.'" value="0" />';
            }
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

        if (has_capability('mod/tracker:manage', $context) || has_capability('mod/tracker:resolve', $context)) {
            $params = array('id' => $cm->id, 'view' => 'view', 'issueid' => $issue->id, 'screen' => 'editanissue');
            $updateurl = new moodle_url('/mod/tracker/view.php', $params);
            $alt = get_string('update');
            $pix = $OUTPUT->pix_icon('t/edit', $alt, 'core');
            $actions = '<a href="'.$updateurl.'" title="'.$alt.'" >'.$pix.'</a>';
        }

        if (has_capability('mod/tracker:manage', $context)) {
            $params = array('id' => $cm->id, 'issueid' => $issue->id, 'what' => 'delete');
            $deleteurl = new moodle_url('/mod/tracker/view.php', $params);
            $alt = get_string('delete');
            $pix = $OUTPUT->pix_icon('t/delete', $alt, 'core');
            $actions .= '&nbsp;<a href="'.$deleteurl.'" title="'.$alt.'" >'.$pix.'</a>';
        }

        if (!$DB->get_record('tracker_issuecc', array('userid' => $USER->id, 'issueid' => $issue->id))) {
            $params = array('id' => $cm->id,
                            'view' => 'profile',
                            'screen' => $screen,
                            'issueid' => $issue->id,
                            'what' => 'register');
            $registerurl = new moodle_url('/mod/tracker/view.php', $params);
            $alt = get_string('register', 'tracker');
            $pix = $OUTPUT->pix_icon('register', $alt, 'mod_tracker');
            $actions .= '&nbsp;<a href="'.$registerurl.'" title="'.$alt.'" >'.$pix.'</a>';
        }

        if (preg_match('/^resolutionpriority/', $sort) &&
                has_capability('mod/tracker:managepriority', $context)) {

            if ($issue->resolutionpriority < $maxpriority) {
                $params = array('id' => $cm->id, 'issueid' => $issue->id, 'what' => 'raisetotop');
                $raiseurl = new moodle_url('/mod/tracker/view.php', $params);
                $alt = get_string('raisetotop', 'tracker');
                $pix = $OUTPUT->pix_icon('totop', $alt, 'mod_tracker');
                $actions .= '&nbsp;<a href="'.$raiseurl.'" title="'.$alt.'" >'.$pix.'</a>';

                $params = array('id' => $cm->id, 'issueid' => $issue->id, 'what' => 'raisepriority');
                $rpurl = new moodle_url('/mod/tracker/view.php', $params);
                $alt = get_string('raisepriority', 'tracker');
                $pix = $OUTPUT->pix_icon('up', $alt, 'mod_tracker');
                $actions .= '&nbsp;<a href="'.$rpurl.'" title="'.$alt.'" >'.$pix.'</a>';
            } else {
                $actions .= '&nbsp;'.$OUTPUT->pix_icon('up_shadow', '', 'mod_tracker');
                $actions .= '&nbsp;'.$OUTPUT->pix_icon('totop_shadow', '', 'mod_tracker');
            }

            if ($issue->resolutionpriority > 1) {
                $params = array('id' => $cm->id, 'issueid' => $issue->id, 'what' => 'lowerpriority');
                $lowerurl = new moodle_url('/mod/tracker/view.php', $params);
                $alt = get_string('lowerpriority', 'tracker');
                $pix = $OUTPUT->pix_icon('down', $alt, 'mod_tracker');
                $actions .= '&nbsp;<a href="'.$lowerurl.'" title="'.$alt.'" >'.$pix.'</a>';

                $params = array('id' => $cm->id, 'issueid' => $issue->id, 'what' => 'lowerpriority');
                $lburl = new moodle_url('/mod/tracker/view.php', $params);
                $alt = get_string('lowertobottom', 'tracker');
                $pix = $OUTPUT->pix_icon('tobottom', $alt, 'mod_tracker');
                $actions .= '&nbsp;<a href="'.$lburl.'" title="'.$alt.'" ></a>';
            } else {
                $actions .= '&nbsp;'.$OUTPUT->pix_icon('down_shadow', '', 'mod_tracker');
                $actions .= '&nbsp;'.$OUTPUT->pix_icon('tobottom_shadow', '', 'mod_tracker');
            }
        }

        if ($resolved) {
            if (!empty($tracker->parent)) {
                $transfer = ($issue->status == TRANSFERED) ? tracker_print_transfer_link($tracker, $issue) : '';
                $dataset = array($issuenumber, $summary.' '.$solution, $datereported, $reportedby, $assignedto,
                                 $status, 0 + $issue->watches, $transfer, $actions);
            } else {
                $dataset = array($issuenumber, $summary.' '.$solution, $datereported, $reportedby, $assignedto,
                                 $status, 0 + $issue->watches, $actions);
            }
        } else {
            if (!empty($tracker->parent)) {
                $transfer = ($issue->status == TRANSFERED) ? tracker_print_transfer_link($tracker, $issue) : '';
                $dataset = array($maxpriority - $issue->resolutionpriority + 1, $issuenumber, $summary.' '.$solution,
                                 $datereported, $reportedby, $assignedto, $status, 0 + $issue->watches, $transfer, $actions);
            } else {
                $dataset = array($maxpriority - $issue->resolutionpriority + 1, $issuenumber, $summary.' '.$solution,
                                 $datereported, $reportedby, $assignedto, $status, 0 + $issue->watches, $actions);
            }
        }
        $table->add_data($dataset);
    }
    $table->print_html();
    echo '<br/>';
    if (tracker_can_workon($tracker, $context)) {
        echo '<center>';
        echo '<p><input type="submit" name="go_btn" value="'.get_string('savechanges').'" /></p>';
        echo '</center>';
    }
} else {
    if (!$resolved) {
        echo '<br/>';
        echo '<br/>';
        echo $OUTPUT->notification(get_string('noissuesreported', 'tracker'), 'box generalbox', 'notice');
    } else {
        echo '<br/>';
        echo '<br/>';
        echo $OUTPUT->notification(get_string('noissuesresolved', 'tracker'), 'box generalbox', 'notice');
    }
}

echo '</form>';