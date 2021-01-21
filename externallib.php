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
 * @package     mod_tracker
 * @category    mod
 * @author Clifford Tham, Valery Fremaux > 1.8
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/tracker/rpclib.php');

class mod_tracker_external {

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

    public static function get_instances($user) {

        $parameters = array(
            'user' => $user
        );
        $params = validate_parameters(self::get_instances);
        return tracker_rpc_get_instances($username, $remotehostroot);
    }

    public static function get_instances_returns() {
        return new external_value(PARAM_RAW, 'serialized array of tracker records');
    }

    /**
     *
     *
     */

    public static function get_infos($trackerid) {
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

    public static function post_issue_parameters() {

        return new external_function_parameters (
            array(
                'instance' => new external_single_structure(
                    array(
                        'type' => new external_value(PARAM_ALPHA, 'primary identifier field'),
                        'instanceid' => new external_value(PARAM_TEXT, 'instance identifier value'),
                    )
                ),
                'user' => new external_single_structure(
                    array(
                        'type' => new external_value(PARAM_ALPHA, 'primary identifier field'),
                        'userid' => new external_value(PARAM_TEXT, 'user identifier value'),
                        'userhostroot' => new external_value(PARAM_TEXT, 'remote host root', VALUE_OPTIONAL),
                    )
                ),
                'issue' => new external_single_structure(
                    array(
                        'summary' => new external_value(PARAM_RAW, 'issue summary'),
                        'description' => new external_value(PARAM_RAW, 'issue description'),
                        'descriptionformat' => new external_value(PARAM_INT, 'description format'),
                        'status' => new external_value(PARAM_INT, 'issue status', VALUE_OPTIONAL),
                        'attributes' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'elementname' => new external_value(PARAM_TEXT, 'tracker element name'),
                                    'elementitemvalue' => new external_value(PARAM_TEXT, 'tracker element item name or value'),
                                )
                            ), VALUE_OPTIONAL
                        ),
                    )
                ),
            )
        );
    }

    /**
     *
     *
     */
    public static function post_issue($instance, $user, $issue) {

        $parameters = array(
            'instance' => $instance,
            'user' => $user,
            'issue' => $issue
        );

        $params = validate_parameters(self::post_issue_parameters(), $parameters);

        return tracker_rpc_post_issue($params['username'], $params['remoteuserhostroot'], $params['trackerid'], $issue);
    }

    public static function post_issue_returns() {
        return new external_value(PARAM_RAW, 'response object with status, errors or message');
    }

    public static function add_subtracker_parameters() {
        return new external_function_parameters (
            array(
                'instance' => new external_single_structure(
                    array(
                        'type' => new external_value(PARAM_ALPHA, 'primary identifier field'),
                        'instanceid' => new external_value(PARAM_TEXT, 'instance identifier value'),
                    )
                ),
                'subtracker' => new external_single_structure(
                    array(
                        'type' => new external_value(PARAM_ALPHA, 'primary identifier field'),
                        'instanceid' => new external_value(PARAM_TEXT, 'instance identifier value'),
                    )
                ),
            )
        );
    }

    public static function add_subtracker($instance, $subtracker) {
    }

    public static function add_subtracker_returns() {
        return new external_value(PARAM_BOOL, 'Operation status');
    }

    public static function remove_subtracker_parameters() {
        return new external_function_parameters (
            array(
                'instance' => new external_single_structure(
                    array(
                        'type' => new external_value(PARAM_ALPHA, 'primary identifier field'),
                        'instanceid' => new external_value(PARAM_TEXT, 'instance identifier value'),
                    )
                ),
                'subtracker' => new external_single_structure(
                    array(
                        'type' => new external_value(PARAM_ALPHA, 'primary identifier field'),
                        'instanceid' => new external_value(PARAM_TEXT, 'instance identifier value'),
                    )
                ),
            )
        );
    }

    public static function remove_subtracker($instance, $subtracker) {
    }

    public static function remove_subtracker_returns() {
        return new external_value(PARAM_BOOL, 'Operation status');
    }
}