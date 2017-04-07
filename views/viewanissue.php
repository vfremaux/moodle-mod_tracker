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
        $trackerurl = new moodle_url('/mod/tracker/view.php', array('view' => 'view', 'screen' => 'browse', 't' => $tracker->id));
        redirect($trackerurl);
    } else {
        $params = array('view' => 'view', 'screen' => 'mytickets', 't' => $tracker->id);
        $trackerurl = new moodle_url('/mod/tracker/view.php', $params);
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

$statehistory = $DB->get_records_select('tracker_state_change', $select, array($tracker->id, $issue->id), 'timechange ASC');

$linklabel = get_string(($initialdepsviewmode == 'visiblediv') ? 'hidedependancies' : 'showdependancies', 'tracker');
$link = '<a id="toggledependancieslink" href="javascript:toggledependancies()">'.$linklabel.'</a>&nbsp;-&nbsp;';
$showdependancieslink = (!empty($childtree) || !empty($parenttree)) ? $link : '';

$linklabel = get_string(($initialccsviewmode == 'visiblediv') ? 'hideccs' : 'showccs', 'tracker');
$link = '<a id="toggleccslink" href="javascript:toggleccs()">'.$linklabel.'</a>&nbsp;-&nbsp;';
$showccslink = (!empty($ccs)) ? $link : '';

$linklabel = get_string(($initialhistoryviewmode == 'visiblediv') ? 'hidehistory' : 'showhistory', 'tracker');
$link = '<a id="togglehistorylink" href="javascript:togglehistory()">'.$linklabel.'</a>&nbsp;-&nbsp;';
$showhistorylink = (!empty($history) || !empty($statehistory)) ? $link : '';

// Fixing embeded files URLS.

$issue->description = file_rewrite_pluginfile_urls($issue->description, 'pluginfile.php', $context->id, 'mod_tracker',
                                                   'issuedescription', $issue->id);
$issue->resolution = file_rewrite_pluginfile_urls($issue->resolution, 'pluginfile.php', $context->id, 'mod_tracker',
                                                  'issueresolution', $issue->id);

// Get statuskeys labels.

$statuskeys = tracker_get_statuskeys($tracker);

// Start printing.
echo $output;
echo $OUTPUT->box_start('generalbox', 'bugreport');

echo $renderer->issue_js_init();

echo '<!-- Print Bug Form -->';
echo '<table cellpadding="5" class="tracker-issue">';

if (tracker_can_workon($tracker, $context, $issue)) {
    // If I can resolve and I have seen, the bug is open.
    if ($issue->status < OPEN) {
        $oldstatus = $issue->status;
        $issue->status = OPEN;
        $DB->set_field('tracker_issue', 'status', OPEN, array('id' => $issueid));
        // Log state change.
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

echo '<!--Print Bug Attributes-->';

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
        $linkurl = new moodle_url('/mod/tracker/comment.php', array('id' => $cm->id, 'issueid' => $issueid));
        $addcommentlink = '<a href="'.$linkurl.'">'.get_string('addacomment', 'tracker').'</a>';
    }
    $showcommentslink = '';
    if ($commentscount) {
        $label = get_string('showcomments', 'tracker');
        $showcommentslink = '<a id="togglecommentlink" href="javascript:togglecomments()">'.$label.'</a>&nbsp;-&nbsp;';
    } else {
        $showcommentslink = '<i>'.get_string('nocomments', 'tracker').'</i>&nbsp;-&nbsp;';
    }
}

$transferlink = '';
if ($tracker->parent &&
        ($issue->status != TRANSFERED) &&
                (has_capability('mod/tracker:manage', $context) ||
                        has_capability('mod/tracker:resolve', $context) ||
                                has_capability('mod/tracker:develop', $context))) {
    $params = array('id' => $cm->id, 'view' => 'view', 'what' => 'cascade', 'issueid' => $issueid);
    $linkurl = new moodle_url('/mod/tracker/view.php', $params);
    $transferlink = ' - <a href="'.$linkurl.'">'.get_string('cascade', 'tracker').'</a>';
}

$distribute = '';
if ($tracker->subtrackers &&
        ($issue->status != TRANSFERED) &&
                (has_capability('mod/tracker:manage', $context) ||
                        has_capability('mod/tracker:resolve', $context) ||
                                has_capability('mod/tracker:develop', $context))) {
    $distribute = $renderer->distribution_form($tracker, $issue, $cm);
}

echo '<tr valign="top">';
echo '<td align="right" colspan="4">';
echo $showhistorylink.$showccslink.$showdependancieslink.$showcommentslink.$addcommentlink.$transferlink.$distribute;
echo '</td>';
echo '</tr>';

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
    echo $renderer->history($tracker, $history, $statehistory, $initialhistoryviewmode);
}

echo '</table>';

echo $OUTPUT->box_end();
$nohtmleditorneeded = true;

echo '</center>';
