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
 * Mustache helper to load strings from string_manager.
 *
 * @package    mod_tracker
 * @copyright  2020 Valery Fremaux <valery.fremaux@mail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod\tracker;

use Mustache_LambdaHelper;
use stdClass;

/**
 * This class will load language strings in a template.
 *
 * @copyright  2020 Valery Fremaux <valery.fremaux@mail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      2.9
 */
class mustache_sortby_helper {

    /**
     * Compute a rendered sortby controls for a table columns.
     *
     * One arg expected as sort column name.
     *
     * @param string $text The text to parse for arguments.
     * @param Mustache_LambdaHelper $helper Used to render nested mustache variables.
     * @return string
     */
    public function sortby($text, Mustache_LambdaHelper $helper) {
        global $PAGE;
        $renderer = $PAGE->get_renderer('mod_tracker');
        return $renderer->sorticons($text);
    }
}
