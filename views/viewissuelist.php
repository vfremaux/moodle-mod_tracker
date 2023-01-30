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

define('TRACKER_LIST_PAGE_SIZE', 20);

$PAGE->requires->js_call_amd('mod_tracker/trackerlist', 'init');

$page = optional_param('page', 0, PARAM_INT);
$sort = optional_param('sort', 'datereported DESC', PARAM_TEXT);

if ($page < 0) {
    $page = 0;
}
$limitfrom = ($page) * TRACKER_LIST_PAGE_SIZE;

if (tracker_supports_feature('items/listables')) {
    tracker_loadelementsused($tracker, $notused);
}
list($issues, $totalcount) = tracker_get_issues($tracker, $resolved, $screen, $sort, $limitfrom, TRACKER_LIST_PAGE_SIZE);

// Display list of issues / Start rendering.
echo $output;
echo $renderer->issuelist($issues, $totalcount, $cm, $tracker, $view, $screen, $resolved);