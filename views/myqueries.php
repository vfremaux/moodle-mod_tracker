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
 * @package      mod_tracker
 * @category     mod
 * @author       Clifford Tham, Valery Fremaux > 1.8
 *
 * Prints list of user's stored queries
 */
defined('MOODLE_INTERNAL') || die();

$select = " userid = ? AND trackerid = ? ";
$queries = $DB->get_records_select('tracker_query', $select, array($USER->id, $tracker->id));

if (!empty($queries)) {
    $searchstr = get_string('query', 'tracker');
    $namestr = get_string('name');
    $descriptionstr = get_string('description');
    $actionstr = get_string('action', 'tracker');
    $table->head = array("<b>$searchstr</b>", "<b>$namestr</b>", "<b>$descriptionstr</b>", "<b>$actionstr</b>");
    $table->size = array(50, 100, 500, 100);
    $table->align = array('center', 'left', 'center', 'center');
    foreach ($queries as $query) {
        $fields = tracker_extractsearchparametersfromdb($query->id);
        $query->description = tracker_printsearchfields($fields);
        $params = array('id' => $cm->id, 'view' => 'view', 'screen' => 'browse', 'what' => 'usequery', 'queryid' => $query->id);
        $searchurl = new moodle_url('/mod/tracker/view.php', $params);
        $alt = get_string('searchwiththat', 'tracker');
        $pix = $OUTPUT->pix_icon('search', $alt, 'mod_tracker');
        $searchlink = '<a href="'.$searchurl.'" title="'.$alt.'">'.$pix.'</a>';

        $params = array('id' => $cm->id, 'what' => 'editquery', 'queryid' => $query->id);
        $editurl = new moodle_url('/mod/tracker/view.php', $params);
        $alt = get_string('update');
        $pix = $OUTPUT->pix_icon('t/edit', $alt, 'core');
        $action = '<a href="'.$editurl.'" title="'.$alt.'" >'.$pix.'</a>';

        $params = array('id' => $cm->id, 'what' => 'deletequery', 'queryid' => $query->id);
        $deleteurl = new moodle_url('/mod/tracker/view.php', $params);
        $alt = get_string('delete');
        $pix = $OUTPUT->pix_icon('t/delete', $alt, 'core');
        $action .= '&nbsp;<a href="'.$deleteurl.'" title="'.$alt.'" >'.$pix.'</a>';
        $table->data[] = array($searchlink, "&nbsp;{$query->name}", format_string($query->description), $action);
    }
    $tablehtml = html_writer::table($table);
} else {
    $tablehtml = $OUTPUT->notification(get_string('noqueryssaved', 'tracker'));
}

// Start printing screen.

echo $output;
echo $OUTPUT->heading(get_string('myqueries', 'tracker'));

echo $OUTPUT->box_start('center', '80%', '', '', 'generalbox', 'tracker-queries');

echo '<center>';
echo $tablehtml;
echo '</center>';

echo $OUTPUT->box_end();