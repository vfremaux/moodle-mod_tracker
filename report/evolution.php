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
 */
defined('MOODLE_INTERNAL') || die();


echo $output;
echo $OUTPUT->heading(get_string('reports', 'tracker'), 1);
echo $OUTPUT->heading(get_string('evolution', 'tracker'), 2);

$alltickets = $DB->count_records('tracker_issue', array('trackerid' => $tracker->id));
if (!$alltickets) {
    echo $OUTPUT->notification(get_string('nodata', 'tracker'));
    return;
}

$renderer = $PAGE->get_renderer('tracker', 'reports');

$renderer->init($tracker);
echo $renderer->counters($alltickets);
echo $renderer->evolution($alltickets);

