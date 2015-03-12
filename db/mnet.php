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
 * This file contains the mnet services for the Moodle tracker module
 *
 * @since 2.0
 * @package mod
 * @subpackage tracker
 * @copyright 2012 Valery Fremaux
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$publishes = array(
    'tracker' => array(
        'servicename'  => 'tracker',
        'description'  => get_string('tracker_service_name', 'tracker'),
        'apiversion' => 1,
        'classname'  => '',
        'filename'   => 'rpclib.php',
        'methods'    => array(
            'tracker_rpc_check',
            'tracker_rpc_get_infos',
            'tracker_rpc_get_instances',
            'tracker_rpc_post_issue'
        ),
    ),
);
$subscribes = array(
    'tracker' => array(
        'tracker_rpc_check' => 'mod/tracker/rpclib.php/tracker_rpc_check',
        'tracker_rpc_get_infos' => 'mod/tracker/rpclib.php/tracker_rpc_get_infos',
        'tracker_rpc_get_instances' => 'mod/tracker/rpclib.php/tracker_rpc_get_instances',
        'tracker_rpc_post_issue' => 'mod/tracker/rpclib.php/tracker_rpc_post_issue',
    ),
);
