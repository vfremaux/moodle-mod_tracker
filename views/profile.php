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
 * @package     mod_tracker
 * @category    mod
 * @author      Clifford Tham, Valery Fremaux > 1.8
 *
 * Prints prints user's profile and stats
 */

defined('MOODLE_INTERNAL') || die();

$statuskeys = tracker_get_statuskeys($tracker);
$statuscodes = tracker_get_statuscodes();

echo $output;
echo '<br/>';
echo $OUTPUT->heading(get_string('me', 'tracker'));

echo $OUTPUT->box_start('center', '90%', '', '', 'generalbox', 'bugreport');

$table = new html_table();
$table->head = array('', '');
$table->width = '90%';
$table->size = array('30%', '70%');
$table->align = array('right', 'left');

$row = array(get_string('name'), fullname($USER));

$str = '';
if ($reporter = has_capability('mod/tracker:report', $context)) {
    $str .= '<span class="green">'.get_string('icanreport', 'tracker').'</span>';
} else {
    $str .= '<span class="red">'.get_string('icannotreport', 'tracker').'</span>';
}
$str .= '<br/>';
if ($developer = has_capability('mod/tracker:develop', $context)) {
    $str .= '<span class="green">'.get_string('iamadeveloper', 'tracker').'</span>';
} else {
    $str .= '<span class="red">'.get_string('iamnotadeveloper', 'tracker').'</span>';
}
$str .= '<br/>';
if ($resolver = has_capability('mod/tracker:resolve', $context)) {
    $str .= '<span class="green">'.get_string('icanresolve', 'tracker').'</span>';
} else {
    $str .= '<span class="red">'.get_string('icannotresolve', 'tracker').'</span>';
}
$str .= '<br/>';
if ($manager = has_capability('mod/tracker:manage', $context)) {
    $str .= '<span class="green">'.get_string('icanmanage', 'tracker').'</span>';
} else {
    $str .= '<span class="red">'.get_string('icannotmanage', 'tracker').'</span>';
}

$row = array(get_string('tracker-levelaccess', 'tracker'), $str);
$table->data[] = $row;

echo html_writer::table($table);

if ($manager) {
    echo $OUTPUT->heading(get_string('manager', 'tracker'));

    $table = new html_table();
    $table->head = array('', '');
    $table->width = '90%';
    $table->size = array('30%', '70%');
    $table->align = array('right', 'left');

    $str = '';
    $assignees = tracker_getassignees($USER->id);
    if ($assignees) {
        foreach ($assignees as $assignee) {
            tracker_print_user($assignee);
            $str .= ' ('.$assignee->issues.')<br />';
        }
    } else {
        $str .= get_string('noassignees', 'tracker');
    }

    $table->data[] = array(get_string('myassignees', 'tracker'), $str);


    $table->data[] = array(get_string('tracker-levelaccess', 'tracker'), $str);

    echo html_writer::table($table);
}
if ($resolver) {
    echo $OUTPUT->heading(get_string('resolver', 'tracker'));

    $table = new html_table();
    $table->head = array('', '');
    $table->width = '90%';
    $table->size = array('30%', '70%');
    $table->align = array('right', 'left');

    $str = '';
    $assignees = tracker_getassignees($USER->id);
    if ($assignees) {
        foreach ($assignees as $assignee) {
            $str .= tracker_print_user($assignee, true);
            $str .= ' ('.$assignee->issues.')<br />';
        }
    } else {
        $str .= get_string('noassignees', 'tracker');
    }

    $table->data[] = array(get_string('myassignees', 'tracker'), $str);

    $issues = tracker_getownedissuesforresolve($tracker->id, $USER->id);
    $str = '';
    if ($issues) {
        foreach ($issues as $issue) {
            $params = array('id' => $cm->id, 'view' => 'view', 'screen' => 'viewanissue', 'issueid' => $issue->id);
            $linkurl = new moodle_url('/mod/tracker/view.php', $params);
            $str .= $tracker->ticketprefix.$issue->id . ' - <a href="'.$linkurl.'">'.$issue->summary.'</a>';
            $str .= "&nbsp;<span class=\"status_{$statuscodes[$issue->status]}\">".$statuskeys[$issue->status].'</span>';
            $str .= '<br />';
        }
    } else {
        print_string('noresolvingissue', 'tracker');
    }

    $table->data[] = array(get_string('myissues', 'tracker'), $str);

    echo html_writer::table($table);
}
echo $OUTPUT->box_end();
