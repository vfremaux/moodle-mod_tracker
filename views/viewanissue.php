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
 *
 * HTML form
 * Print Bug Description
 */

defined('MOODLE_INTERNAL') || die();

$config = get_config('mod_tracker');

// Set initial view mode for additional pannels.
$commentscondition = ($action == 'doaddcomment') || ($config->initialviewcomments == 'open');
$initialcommentsviewmode = ($commentscondition) ? 'visiblediv' : 'hiddendiv';
$ccscondition = ($action == 'register' || $action == 'unregister') || ($config->initialviewccs == 'open');
$initialccsviewmode = ($ccscondition) ? 'visiblediv' : 'hiddendiv';
$initialdepsviewmode = ($config->initialviewdeps == 'open') ? 'visiblediv' : 'hiddendiv';
$initialhistoryviewmode = ($config->initialviewhistory == 'open') ? 'visiblediv' : 'hiddendiv';

$issue = $DB->get_record('tracker_issue', array('id' => $issueid));

if (!$issue) {
    if ($tracker->supportmode == 'bugtrack') {
        $trackerurl = new moodle_url('/mod/tracker/viex.php', array('view' => 'view', 'screen' => 'browse', 'a' => $tracker->id));
        redirect($trackerurl);
    } else {
        $trackerurl = new moodle_url('/mod/tracker/viex.php', array('view' => 'view', 'screen' => 'mytickets', 'a' => $tracker->id));
        redirect($trackerurl);
    }
}

$issue->reporter = $DB->get_record('user', array('id' => $issue->reportedby));
$issue->owner = $DB->get_record('user', array('id' => $issue->assignedto));

tracker_loadelementsused($tracker, $elementsused);

// Check for lower dependancies.

$childtree = tracker_printchilds($tracker, $issue->id, true, 20);
$parenttree = tracker_printparents($tracker, $issue->id, true, -20);
$ccs = $DB->get_records('tracker_issuecc', array('issueid' => $issue->id));
$cced = array();

$select = " trackerid = ? AND issueid = ? ";
$history = $DB->get_records_select('tracker_issueownership', $select, array($tracker->id, $issue->id), 'timeassigned DESC');

$statehistory = $DB->get_records_select('tracker_state_change', $select, array($tracker->id, $issue->id),'timechange ASC');
$showdependancieslink = (!empty($childtree) || !empty($parenttree)) ? "<a id=\"toggledependancieslink\" href=\"javascript:toggledependancies()\">".get_string(($initialviewmode == 'visiblediv') ? 'hidedependancies' : 'showdependancies', 'tracker').'</a>&nbsp;-&nbsp;' : '' ;
$showccslink = (!empty($ccs)) ? "<a id=\"toggleccslink\" href=\"javascript:toggleccs()\">".get_string(($initialccsviewmode == 'visiblediv') ? 'hideccs' : 'showccs', 'tracker').'</a>&nbsp;-&nbsp;' : '' ;
$showhistorylink = (!empty($history) || !empty($statehistory)) ? "<a id=\"togglehistorylink\" href=\"javascript:togglehistory()\">".get_string(($initialhistoryviewmode == 'visiblediv') ? 'hidehistory' : 'showhistory', 'tracker').'</a>&nbsp;-&nbsp;' : '' ;

// fixing embeded files URLS

$issue->description = file_rewrite_pluginfile_urls($issue->description, 'pluginfile.php', $context->id, 'mod_tracker',
                                                   'issuedescription', $issue->id);
$issue->resolution = file_rewrite_pluginfile_urls($issue->resolution, 'pluginfile.php', $context->id, 'mod_tracker',
                                                  'issueresolution', $issue->id);

// get STATUSKEYS labels

$statuskeys = tracker_get_statuskeys($tracker);

// Start printing.

echo $OUTPUT->box_start('generalbox', 'bugreport');
?>

<!-- Print Bug Form -->

<table cellpadding="5" class="tracker-issue">
<script type="text/javascript">
    var showhistory = "<?php print_string('showhistory', 'tracker') ?>";
    var hidehistory = "<?php print_string('hidehistory', 'tracker') ?>";

    var showccs = "<?php print_string('showccs', 'tracker') ?>";
    var hideccs = "<?php print_string('hideccs', 'tracker') ?>";

    var showdependancies = "<?php print_string('showdependancies', 'tracker') ?>";
    var hidedependancies = "<?php print_string('hidedependancies', 'tracker') ?>";

    var showcomments = "<?php print_string('showcomments', 'tracker') ?>";
    var hidecomments = "<?php print_string('hidecomments', 'tracker') ?>";
</script>
<?php

if (tracker_can_workon($tracker, $context, $issue)) {
    // If I can resolve and I have seen, the bug is open
    if ($issue->status < OPEN) {
        $oldstatus = $issue->status;
        $issue->status = OPEN;
        $DB->set_field('tracker_issue', 'status', OPEN, array('id' => $issueid));
        // log state change
        $stc = new StdClass;
        $stc->userid = $USER->id;
        $stc->issueid = $issue->id;
        $stc->trackerid = $tracker->id;
        $stc->timechange = time();
        $stc->statusfrom = $oldstatus;
        $stc->statusto = $issue->status;
        $DB->insert_record('tracker_state_change', $stc);
    }
}

if (tracker_can_edit($tracker, $context, $issue)) {
    echo $renderer->edit_link($issue, $cm);
}

echo $renderer->core_issue($issue, $tracker);
?>
    
    <!--Print Bug Attributes-->
    
<?php
if (is_array($elementsused)) {
    echo $renderer->issue_attributes($issue, $elementsused);
}
if (!empty($issue->resolution)) {
    echo $renderer->resolution($issue);
}
$showcommentslink = '';
$addcommentlink = '';
if ($tracker->enablecomments) {
    $commentscount = $DB->count_records('tracker_issuecomment', array('issueid' => $issue->id));
    $addcommentlink = '';
    if (has_capability('mod/tracker:comment', $context)) {
        $addcommentlink = "<a href=\"addcomment.php?id={$cm->id}&amp;issueid={$issueid}\">".get_string('addacomment', 'tracker').'</a>';
    }
    $showcommentslink = '';
    if ($commentscount) {
        $showcommentslink = "<a id=\"togglecommentlink\" href=\"javascript:togglecomments()\">".get_string('showcomments', 'tracker').'</a>&nbsp;-&nbsp;';
    } else {
        $showcommentslink = '<i>'.get_string('nocomments','tracker').'</i>&nbsp;-&nbsp;';
    }
}

$transferlink = '';
if ($tracker->parent &&
        ($issue->status != TRANSFERED) &&
                (has_capability('mod/tracker:manage', $context) ||
                        has_capability('mod/tracker:resolve', $context) ||
                                has_capability('mod/tracker:develop', $context))) {
    $transferlink = " - <a href=\"view.php?id={$cm->id}&amp;view=view&amp;what=cascade&amp;issueid={$issueid}\">".get_string('cascade','tracker')."</a>";
}

$distribute = '';
if ($tracker->subtrackers &&
        ($issue->status != TRANSFERED) &&
                (has_capability('mod/tracker:manage', $context) ||
                        has_capability('mod/tracker:resolve', $context) ||
                                has_capability('mod/tracker:develop', $context))) {
    $distribute = $renderer->distribution_form($tracker, $issue, $cm);
}

?>
    <tr valign="top">
        <td align="right" colspan="4">
            <?php echo $showhistorylink.$showccslink.$showdependancieslink.$showcommentslink.$addcommentlink.$transferlink.$distribute; ?>
        </td>
    </tr>
<?php
if ($tracker->enablecomments) {
    if (!empty($commentscount)) {
?>
    <tr>
        <td colspan="4">
            <div id="issuecomments" class="<?php echo $initialcommentsviewmode ?> comments">
            <table width="100%">
                <?php echo $renderer->comments($issue->id); ?>
            </table>
            </div>
        </td>
    </tr>
<?php
    }
}
?>
    <tr>
        <td colspan="4" align="center" width="100%">
            <table id="issuedependancytrees" class="<?php echo $initialdepsviewmode ?>">
                <tr>
                    <td>&nbsp;</td>
                    <td align="left" style="white-space : nowrap">
                    <?php 
                        echo $parenttree;
                        echo $tracker->ticketprefix.$issue->id.' - '.format_string($issue->summary).'<br/>';
                        echo $childtree;
                    ?>
                    </td>
                    <td>&nbsp;</td>
                </tr>
            </table>
        </td>
    </tr>
<?php
if ($showccslink) {
    echo $renderer->ccs($ccs, $issue, $cm, $cced, $initialccsviewmode);
}
if (has_capability('mod/tracker:managewatches', $context)) {
    echo $renderer->watches_form($issue, $cm, $cced);
}
if ($showhistorylink) {
    echo $renderer->history($history, $statehistory, $initialhistoryviewmode);
}
?>
</table>
<?php
echo $OUTPUT->box_end();
$nohtmleditorneeded = true;
?>
</center>
