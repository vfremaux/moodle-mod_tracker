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
 * Web service for mod tracker
 * @package    mod_tracker
 * @subpackage db
 * @since      Moodle 2.4
 * @copyright  2013 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = array(

    'mod_tracker_get_instances' => array(
            'classname'   => 'mod_tracker_external',
            'methodname'  => 'get_instances',
            'classpath'   => 'mod/tracker/externallib.php',
            'description' => 'Returns description of available instances of trackers in a moodle',
            'type'        => 'read'
    ),

    'mod_tracker_get_infos' => array(
            'classname'   => 'mod_tracker_external',
            'methodname'  => 'get_infos',
            'classpath'   => 'mod/tracker/externallib.php',
            'description' => 'Returns equipement description of a single tracker',
            'type'        => 'read'
    ),

    'mod_tracker_post_issue' => array(
            'classname'   => 'mod_tracker_external',
            'methodname'  => 'post_issue',
            'classpath'   => 'mod/tracker/externallib.php',
            'description' => 'Allows posting an issue for cascading',
            'type'        => 'write'
    )
);
