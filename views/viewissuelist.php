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
 * Print Ticket List
 */
defined('MOODLE_INTERNAL') || die();

$PAGE->requires->js_call_amd('mod_tracker/trackerlist', 'init');

$pagesize = 20;
$page = optional_param('page', 0, PARAM_INT);
$sort = optional_param('sort', 'datereported DESC', PARAM_TEXT);

if ($page < 0) {
    $page = 0;
}
$limitfrom = ($page) * $pagesize;

list($issues, $totalcount) = tracker_get_issues($tracker, $resolved, $screen, $sort, $limitfrom, $pagesize);

$baseurl = new moodle_url('/mod/tracker/view.php', array('id' => $cm->id, 'view' => $view, 'screen' => $screen));

$select = " trackerid = ? GROUP BY trackerid ";
$maxpriority = $DB->get_field_select('tracker_issue', 'MAX(resolutionpriority)', $select, array($tracker->id));

$template = new Stdclass;
$template->cmid = $cm->id;
$template->screen = $screen;

if ($totalcount > $pagesize) {
    $template->pager = $OUTPUT->paging_bar($totalcount, $page , $pagesize, $url, 'page');
}

$template->formurl = new moodle_url('/mod/tracker/view.php');
if ($resolved) {
    $template->priority = false;
    $template->transferable = false;
}

if ($screen == 'mywork') {
    $template->ismywork = true;
}
if ($screen == 'mytickets') {
    $template->ismytickets = true;
}

if (!empty($issues)) {
    // Product data for table.
    foreach ($issues as $issue) {

        $issuetpl = new Stdclass;

        $issuetpl->id = $issue->id;
        $issuetpl->contextid = $context->id;

        // Issue number.
        $params = array('id' => $cm->id, 'view' => 'view', 'screen' => 'viewanissue', 'issueid' => $issue->id);
        $issuetpl->issueurl = new moodle_url('/mod/tracker/view.php', $params);
        $issuetpl->issuenumber = '<a href="'.$issuetpl->issueurl.'">'.$tracker->ticketprefix.$issue->id.'</a>';

        // Issue summary.
        $issuetpl->summary = format_string($issue->summary);

        // Issue date.
        $issuetpl->datereported = date('Y/m/d H:i', $issue->datereported);

        // Issue reporter.
        $user = $DB->get_record('user', array('id' => $issue->reportedby));
        $issuetpl->reportedby = fullname($user);

        // Issue assigned.
        $issuetpl->assignedto = $renderer->assignedto_listform_part($issue, $context);

        // Issue status.
        $issuetpl->status = $renderer->status_listform_part($tracker, $cm, $issue, $context);

        $issuetpl->watches = $issue->watches;

        // Issue solved signal.
        $hassolution = $issue->status == RESOLVED && !empty($issue->resolution);
        $alt = get_string('hassolution', 'tracker');
        $pix = $OUTPUT->pix_icon('solution', $alt, 'mod_tracker');
        $issuetpl->solution = ($hassolution) ? $pix : '';

        // Issue controls.
        $issue->maxpriority = $maxpriority;
        $issuetpl->controls = $renderer->controls_listform_part($cm, $issue, $context);

        if (!empty($tracker->parent)) {
            $issuetpl->transfer = ($issue->status == TRANSFERED) ? tracker_print_transfer_link($tracker, $issue) : '';
        }

        // Compose final dataset.
        if (!$resolved) {
            $issuetpl->priority = $maxpriority - $issue->resolutionpriority + 1;
        }
        $template->issues[] = $issuetpl;
    }

    if (tracker_can_workon($tracker, $context)) {
        $template->cansubmit = true;
    }
} else {
    $template->emptylist = true;
    if (!$resolved) {
        $template->emptynotification = $OUTPUT->notification(get_string('noissuesreported', 'tracker'), 'box generalbox', 'notice');
    } else {
        $template->emptynotification = $OUTPUT->notification(get_string('noissuesresolved', 'tracker'), 'box generalbox', 'notice');
    }
}

// Display list of issues / Start rendering.
echo $output;
echo $OUTPUT->render_from_template('mod_tracker/issuelistform', $template);