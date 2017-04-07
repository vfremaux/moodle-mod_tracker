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
 * Special report output for printing for reviewes.
 *
 * @package     mod_tracker
 * @category    mod
 * @author      Valery Fremaux (valery.fremaux@gmail.com)
 */
defined('MOODLE_INTERNAL') || die();

echo $OUTPUT->heading(get_string('reports', 'tracker'), 1);
echo $OUTPUT->heading(get_string('status', 'tracker'), 2);

$renderer = $PAGE->get_renderer('tracker', 'reports');
$renderer->init($tracker);

echo $renderer->status_stats();
