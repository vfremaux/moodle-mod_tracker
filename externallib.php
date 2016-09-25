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
 * This file extends MNET internal web service definitions by wrapping functions
 * on a compliant consistant naming space for Rest or other protocol invocation
 * methods
 *
 */

require_once($CFG->dirroot.'/mod/tracker/rpclib.php');

class mod_tracker_external {

    function get_instances($username, $remotehostroot) {
        return tracker_rpc_get_instances($username, $remotehostroot);
    }

    public static function get_instances_parameters() {

        return new external_function_parameters (
            array(
                'username' => new external_value(
                        PARAM_ALPHANUMEXT,
                        'primary identifier'),
                'remotehostroot' => new external_value(
                        PARAM_RAW,
                        'remote calling root')
            )
        );
    }

    public static function get_instances_returns() {
        return new external_value(PARAM_RAW, 'serialized array of tracker records');
    }

    /**
     *
     *
     */

    function get_infos($trackerid) {
        return json_decode(tracker_rpc_get_infos($trackerid, false));
    }

    public static function get_infos_parameters() {

        return new external_function_parameters (
            array(
                'tracker' => new external_value(
                        PARAM_ALPHANUMEXT,
                        'primary identifier')
            )
        );
    }

    public static function get_infos_returns() {
        return new external_value(PARAM_RAW, 'a serialized array of tracker short infos');
    }

    /**
     *
     *
     */
    function post_issue($username, $remoteuserhostroot, $trackerid, $remote_issue) {
        return tracker_rpc_post_issue($username, $remoteuserhostroot, $trackerid, $remote_issue);
    }

    public static function post_issue_parameters() {

        return new external_function_parameters (
            array(
                'username' => new external_value(
                        PARAM_ALPHANUMEXT,
                        'primary identifier'),
                'remoteuserhostroot' => new external_value(
                        PARAM_TEXT,
                        'remote host root'),
                'trackerid' => new external_value(
                        PARAM_INT,
                        'remote tracker id where to post'),
                'remoteissue' => new external_value(
                        PARAM_RAW,
                        'issue structure'),
            )
        );
    }

    public static function post_issue_returns() {
        return new external_value(PARAM_RAW, 'response object with status, errors or message');
    }
}