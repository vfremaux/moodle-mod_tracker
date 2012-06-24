<?PHP

/**
* A view of owned issues
* @package mod-tracker
* @category mod
* @author Valery Fremaux
* @date 02/12/2007
* @version Moodle 2.0
*
* Print Bug List
*/

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from view.php in mod/tracker
}

include_once $CFG->libdir.'/tablelib.php';

$STATUSKEYS = array(POSTED => get_string('posted', 'tracker'), 
                    OPEN => get_string('open', 'tracker'), 
                    RESOLVING => get_string('resolving', 'tracker'), 
                    WAITING => get_string('waiting', 'tracker'), 
                    TESTING => get_string('testing', 'tracker'), 
                    VALIDATED => get_string('validated', 'tracker'), 
                    PUBLISHED => get_string('published', 'tracker'), 
                    RESOLVED => get_string('resolved', 'tracker'), 
                    ABANDONNED => get_string('abandonned', 'tracker'),
                    TRANSFERED => get_string('transfered', 'tracker'));

/// get search engine related information
// fields can come from a stored query,or from the current query in the user's client environement cookie
if (!isset($fields)){
    $fields = tracker_extractsearchcookies();
}
if (!empty($fields)){
    $searchqueries = tracker_constructsearchqueries($tracker->id, $fields, true);
}

$limit = 20;
$page = optional_param('page', 1, PARAM_INT);

if ($page <= 0){
    $page = 1;
}

if (isset($searchqueries)){
    /* SEARCH DEBUG 
    $strsql = str_replace("\n", "<br/>", $searchqueries->count);
    $strsql = str_replace("\t", "&nbsp;&nbsp;&nbsp;", $strsql);
    echo "<div align=\"left\"> <b>count using :</b> ".$strsql." <br/>";
    $strsql = str_replace("\n", "<br/>", $searchqueries->search);
    $strsql = str_replace("\t", "&nbsp;&nbsp;&nbsp;", $strsql);
    echo " <b>search using :</b> ".str_replace("\n", "<br/>", $strsql)." <br/></div>";
    */
    $sql = $searchqueries->search;
    $numrecords = $DB->count_records_sql($searchqueries->count);
} else {
	$alltracks = optional_param('alltracks', false, PARAM_BOOL);
	$singletrackerclause = (empty($alltracks)) ? " AND i.trackerid = {$tracker->id} " : '' ;
	
    if ($resolved){
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
            i.status,
            t.name,
            t.ticketprefix,
            i.resolutionpriority,
            COUNT(ic.issueid) AS watches
        FROM 
            {tracker_issue} i
        JOIN
            {tracker} t
        ON
        	t.id = i.trackerid
        LEFT JOIN
            {tracker_issuecc} ic 
        ON
            ic.issueid = i.id
        WHERE 
            i.assignedto = ? 
            {$singletrackerclause}
            $resolvedclause
        GROUP BY 
            i.id,
            i.summary, 
            i.datereported, 
            i.reportedby, 
            i.status,
            i.resolutionpriority
    ";

    $sqlcount = "
        SELECT 
            COUNT(*)
        FROM 
            {tracker_issue} i
        WHERE 
            i.assignedto = ? 
            {$singletrackerclause}
            $resolvedclause
    ";
    $numrecords = $DB->count_records_sql($sqlcount, array($USER->id));
}

/// display list of my issues
?>
<center>
<table border="1" width="100%">
<?php
if (isset($searchqueries)){
?>
    <tr>
        <td colspan="2">
            <?php print_string('searchresults', 'tracker') ?>: <?php echo $numrecords ?> <br/>
        </td>
        <td colspan="2" align="right">
                <a href="view.php?id=<?php p($cm->id) ?>&amp;what=clearsearch"><?php print_string('clearsearch', 'tracker') ?></a>
        </td>
    </tr>
<?php
}
?>      
</table>
</center>
<form name="manageform" action="view.php" method="post">
<input type="checkbox" name="alltracks" value="1" <?php if ($alltracks) echo "checked=\"checked\"" ?> /> <?php echo get_string('alltracks', 'tracker') ?>
<input type="hidden" name="id" value="<?php p($cm->id) ?>" />
<input type="hidden" name="what" value="updatelist" />
<?php       

/// define table object
$prioritystr = get_string('priorityid', 'tracker');
$issuenumberstr = get_string('issuenumber', 'tracker');
$summarystr = get_string('summary', 'tracker');
$datereportedstr = get_string('datereported', 'tracker');
$reporterstr = get_string('reportedby', 'tracker');
$statusstr = get_string('status', 'tracker');
$watchesstr = get_string('watches', 'tracker');
$actionstr = '';

if(!empty($tracker->parent)){
    $transferstr = get_string('transfer', 'tracker');
    $tablecolumns = array('resolutionpriority', 'id', 'summary', 'datereported', 'reportedby', 'status', 'watches', 'transfered', 'action');
    $tableheaders = array("<b>$prioritystr</b>", "<b>$issuenumberstr</b>", "<b>$summarystr</b>", "<b>$datereportedstr</b>", "<b>$reporterstr</b>", "<b>$statusstr</b>", "<b>$watchesstr</b>", "<b>$transferstr</b>", "<b>$actionstr</b>");
} else {
    $tablecolumns = array('resolutionpriority', 'id', 'summary', 'datereported', 'reportedby', 'status', 'watches',  'action');
    $tableheaders = array("<b>$prioritystr</b>", "<b>$issuenumberstr</b>", "<b>$summarystr</b>", "<b>$datereportedstr</b>", "<b>$reporterstr</b>", "<b>$statusstr</b>", "<b>$watchesstr</b>", "<b>$actionstr</b>");
}

$table = new flexible_table('mod-tracker-issuelist');
$table->define_columns($tablecolumns);
$table->define_headers($tableheaders);

$table->define_baseurl($CFG->wwwroot.'/mod/tracker/view.php?id='.$cm->id.'&view='.$view.'&screen='.$screen.'&alltracks='.$alltracks);

$table->sortable(true, 'datereported', SORT_DESC); //sorted by datereported by default
$table->collapsible(true);
$table->initialbars(true);

// allow column hiding
// $table->column_suppress('reportedby');
// $table->column_suppress('watches');

$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'issues');
$table->set_attribute('class', 'issuelist');
$table->set_attribute('width', '100%');

$table->column_class('resolutionpriority', 'list_priority');
$table->column_class('id', 'list_issuenumber');
$table->column_class('summary', 'list_summary');
$table->column_class('datereported', 'timelabel');
$table->column_class('reporter', 'list_reporter');
$table->column_class('watches', 'list_watches');
$table->column_class('status', 'list_status');
$table->column_class('action', 'list_action');
if (!empty($tracker->parent)){
    $table->column_class('transfered', 'list_transfered');
}

$table->setup();


/// set list length limits
/*
if ($limit > $numrecords){
    $offset = 0;
}
else{
    $offset = $limit * ($page - 1);
}
$sql = $sql . ' LIMIT ' . $limit . ' OFFSET ' . $offset;
*/

/// get extra query parameters from flexible_table behaviour
$where = $table->get_sql_where();
$sort = $table->get_sql_sort();
$table->pagesize($limit, $numrecords);

if (!empty($sort)){
    $sql .= " ORDER BY $sort";
}

$issues = $DB->get_records_sql($sql, array($USER->id), $table->get_page_start(), $table->get_page_size());
$maxpriority = $DB->get_field_select('tracker_issue', 'MAX(resolutionpriority)', " trackerid = ? GROUP BY trackerid ", array($tracker->id));

if (!empty($issues)){
    /// product data for table
    $developersmenu = array();
    foreach ($issues as $issue){
        $issuenumber = "<a href=\"view.php?id={$cm->id}&amp;issueid={$issue->id}\">{$issue->ticketprefix}{$issue->id}</a>";
        $summary = "<a href=\"view.php?id={$cm->id}&amp;view=view&amp;screen=viewanissue&amp;issueid={$issue->id}\">".format_string($issue->summary).'</a>';
        $datereported = date('Y/m/d h:i', $issue->datereported);
        if (has_capability('mod/tracker:manage', $context)){ // managers can assign bugs
            $status = choose_from_menu($STATUSKEYS, "status{$issue->id}", $issue->status, '', "document.forms['manageform'].schanged{$issue->id}.value = 1;", '', true). "<input type=\"hidden\" name=\"schanged{$issue->id}\" value=\"0\" />";
            $developers = get_users_by_capability($context, 'mod/tracker:develop', 'u.id,lastname,firstname', 'lastname');
            foreach($developers as $developer){
                $developersmenu[$developer->id] = fullname($developer);
            }
        } elseif (has_capability('mod/tracker:resolve', $context)){ // resolvers can give a bug back to managers
            $status = html_writer::select($STATUSKEYS, "status{$issue->id}", $issue->status, '', "document.forms['manageform'].schanged{$issue->id}.value = 1;", '', true) . "<input type=\"hidden\" name=\"schanged{$issue->id}\" value=\"0\" />";
            $managers = get_users_by_capability($context, 'mod/tracker:manage', 'u.id,lastname,firstname', 'lastname');
            foreach($managers as $manager){
                $managersmenu[$manager->id] = fullname($manager);
            }
            $managersmenu[$USER->id] = fullname($USER);
        } else {
            $status = $STATUSKEYS[0 + $issue->status]; 
        }
        $reporteruser = $DB->get_record('user', array('id' => $issue->reportedby));
        $reporter = fullname($reporteruser);
        $status = '<div class="status_'.$STATUSCODES[$issue->status].'" style="width: 110%; height: 105%; text-align:center">'.$status.'</div>';
        $hassolution = $issue->status == RESOLVED && !empty($issue->resolution);
        $solution = ($hassolution) ? "<img src=\"{$CFG->wwwroot}/mod/tracker/pix/solution.gif\" height=\"15\" alt=\"".get_string('hassolution','tracker')."\" />" : '' ;
        $actions = '';
        if (has_capability('mod/tracker:manage', $context) || has_capability('mod/tracker:resolve', $context)){
            $actions = "<a href=\"view.php?id={$cm->id}&amp;issueid={$issue->id}&screen=editanissue\" title=\"".get_string('update')."\" ><img src=\"{$CFG->pixpath}/t/edit.gif\" border=\"0\" /></a>";
        }
        if (has_capability('mod/tracker:manage', $context)){
            $actions .= "&nbsp;<a href=\"view.php?id={$cm->id}&amp;issueid={$issue->id}&what=delete\" title=\"".get_string('delete')."\" ><img src=\"{$CFG->pixpath}/t/delete.gif\" border=\"0\" /></a>";
        }
        $actions .= "&nbsp;<a href=\"view.php?id={$cm->id}&amp;view=profile&amp;screen=mywatches&amp;issueid={$issue->id}&what=register\" title=\"".get_string('register', 'tracker')."\" ><img src=\"{$CFG->wwwroot}/mod/tracker/pix/register.gif\" border=\"0\" /></a>";
        if ($issue->resolutionpriority < $maxpriority && has_capability('mod/tracker:viewpriority', $context) && !has_capability('mod/tracker:managepriority', $context)){
            $actions .= "&nbsp;<a href=\"view.php?id={$cm->id}&amp;issueid={$issue->id}&amp;what=askraise\" title=\"".get_string('askraise', 'tracker')."\" ><img src=\"{$CFG->wwwroot}/mod/tracker/pix/askraise.gif\" border=\"0\" /></a>";
        }
        if (!empty($tracker->parent)){
            $transfer = ($issue->status == TRANSFERED) ? tracker_print_transfer_link($tracker, $issue) : '' ;
            $dataset = array($issue->resolutionpriority, $issuenumber, $summary.' '.$solution, $datereported, $reporter, $status, 0 + $issue->watches, $transfer, $actions);
        } else {
            $dataset = array($issue->resolutionpriority, $issuenumber, $summary.' '.$solution, $datereported, $reporter, $status, 0 + $issue->watches, $actions);
        }
        $table->add_data($dataset);     
    }
    $table->print_html();
} else {
	echo '<br/>';
	echo '<br/>';
	echo '<br/>';
    notice(get_string('notickets', 'tracker'), "view.php?id=$cm->id"); 
}

if (has_capability('mod/tracker:manage', $context) || has_capability('mod/tracker:resolve', $context)){
?>
<center>
    <p><input type="submit" name="go_btn" value="<?php print_string('savechanges') ?>" /></p>
</center>
</form>
<?php

$nohtmleditorneeded = true;
}
?>
