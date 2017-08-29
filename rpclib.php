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
 * @author Clifford Tham, Valery Fremaux > 1.8
 * @date 02/12/2007
 *
 * Library of functions for rpc remote calls at tracker. All complex
 * variables transport are performed using JSON format.
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/tracker/locallib.php');

if (!function_exists('debug_trace')) {
    function debug_trace($str) {
        // Empty fake function if missing.
        assert(1);
    }
}

/*
 * Constants
 *
 */
if (!defined('RPC_SUCCESS')) {
        define('RPC_TEST', 100);
        define('RPC_SUCCESS', 200);
        define('RPC_FAILURE', 500);
        define('RPC_FAILURE_USER', 501);
        define('RPC_FAILURE_CONFIG', 502);
        define('RPC_FAILURE_DATA', 503);
        define('RPC_FAILURE_CAPABILITY', 510);
}

define('CREATE_IF_MISSING', true);

/**
 * checks an user has local identity and comes from a known host
 * @param string $username the user's login
 * @param string $remotehostroot the host he comes from
 * @return a failure report if unchecked, null elsewhere.
 */
function tracker_rpc_check($remoteuser, &$localuser, $createmissing = false) {
    global $DB;

    // Get local identity for user.

    if (!$remotehost = $DB->get_record('mnet_host', array('wwwroot' => $remoteuser->hostwwwroot))) {
        $response->status = RPC_FAILURE;
        $response->error[] = "Calling host is not registered. Check MNET configuration";
        return json_encode($response);
    }

    $select = "username = ? AND mnethostid = ? AND deleted = 0";
    if (!$localuser = $DB->get_record_select('user', $select, array($remoteuser->username, $remotehost->id))) {
        if ($createmissing) {

            // We create a minimalistic mnet user. Profile might be completed later.
            $localuser = new StdClass();
            $localuser->username = $remoteuser->username;
            if (is_enabled_auth('multimnet')) {
                $localuser->auth = 'multimnet';
            } else {
                $localuser->auth = 'mnet';
            }
            $localuser->timecreated = time();
            $localuser->firstname = $remoteuser->firstname;
            $localuser->lastname = $remoteuser->lastname;
            $localuser->deleted = 0;
            $localuser->email = $remoteuser->email;
            $localuser->mnethostid = $remotehost->id;

            try {
                $localuser->id = $DB->insert_record('user', $localuser);
            } catch (Exception $e) {
                $response->status = RPC_FAILURE_USER;
                $response->error[] = "User could not be created.";
                $response->error[] = serialize($e);
                return json_encode($response);
            }
        } else {
            $response->status = RPC_FAILURE_USER;
            $response->error[] = "Calling user has no local account. Register remote user first";
            return json_encode($response);
        }
    }

    return null;
}

/**
 * sends tracker information to remote caller. This is intended for
 * administrative binding GUIs.
 * @param int $trackerid the id of the tracker instance
 * @param boolean $nojson when true, avoids serializing through JSON syntax
 * @return string a JSON encoded information structure.
 */
function tracker_rpc_get_infos($trackerid, $nojson = false) {
    global $DB;

    $tracker = $DB->get_record('tracker', array('id' => "$trackerid"));
    $query = "
        SELECT
            te.name,
            te.description,
            te.type
        FROM
            {tracker_element} te,
            {tracker_elementused} teu
        WHERE
            te.id = teu.elementid AND
            teu.trackerid = {$trackerid}
    ";
    $elementused = $DB->get_records_sql($query);
    $tracker->elements = $elementused;

    if ($nojson) {
        return $tracker;
    }

    return json_encode($tracker);
}

/**
 * sends an array of available trackers. Returns only trackers
 * the remote user has capability to manage. Note that this
 * RPC call can be used locally.
 * @param string $username the user's login
 * @param string $remotehostroot the host he comes from
 * @return a stub of instance descriptions
 */
function tracker_rpc_get_instances($username, $remotehostroot) {
    global $CFG, $DB;

    $response->status = RPC_SUCCESS;
    $trackers = $DB->get_records('tracker', null, 'name', 'id, name, networkable');
    if (!empty($trackers)) {
        foreach ($trackers as $id => $tracker) {
            /*
             * A networkable tracker is exposed at once the tracker
             * ticket transport layer is enabled.
             */
            if (!$tracker->networkable) {
                /*
                 * Non networkable trackers will need the remote user
                 * has proper write capabilities to nbe able to link and post
                 */
                try {
                    $cm = get_coursemodule_from_instance('tracker', $id);
                    $modulecontext = context_module::instance($cm->id);
                    if (!has_capability('mod/tracker:report', $modulecontext, $localuser->id)) {
                        unset($trackers[$id]);
                        $response->report[] = "ignoring tracker $id for capability reasons";
                    }
                } catch (Exception $e) {
                    $response->report[] = "No course module for tracker instance $id";
                }
            }
        }
    }
    $response->trackers = $trackers;
    return json_encode($response);
}

/**
 * remote post an entry in a tracker
 * @param object $remoteuser a user description
 * @param int $trackerid the local trackerid where to post
 * @param string $remoteissue a JSON encoded variable containing all
 * information about an issue.
 * @return the local issue record id
 */
function tracker_rpc_post_issue($remoteuser, $trackerid, $remoteissue, $islocalcall = false) {
    global $DB, $USER;

    $tracker = $DB->get_record('tracker', array('id' => $trackerid));
    if (!$tracker) {
        $response = new StdClass;
        $response->status = RPC_FAILURE;
        $response->error[] = 'Tracker not found';
        return json_encode($response);
    }

    // Objectify received arrays.
    $remoteuser = (object)$remoteuser;
    // Clone is important here to unbind instances.
    $newissue = clone((object)$remoteissue);

    if (!$islocalcall) {
        if ($tracker->networkable) {
            /*
             * If tracker is networkable, we consider service binding is enough to accept
             * local user creation if missing.
             */
            if ($failedcheck = tracker_rpc_check($remoteuser, $localuser, CREATE_IF_MISSING)) {
                return $failedcheck;
            }
        } else {
            // Simply checks user and returns $localuser record.
            if ($failedcheck = tracker_rpc_check($remoteuser, $localuser)) {
                return $failedcheck;
            }
        }
        $originhostid = $DB->get_field('mnet_host', 'id', array('wwwroot' => $remoteuser->hostwwwroot));
    } else {
        $localuser = $USER;
        $originhostid = 0;
    }

    $response = new StdClass;
    $response->status = RPC_SUCCESS;

    // Get additional data and cleanup the issue record for insertion.
    if (isset($newissue->attributes)) {
        $attributes = $newissue->attributes;
        unset($newissue->attributes); // Clears attributes so we have an issue record.
    }

    $comment = $newissue->comment;
    unset($newissue->comment);

    unset($newissue->id); // Clears id, so it will be a new record.
    $newissue->trackerid = $trackerid;
    $newissue->status = POSTED;
    $newissue->reportedby = $localuser->id;
    $newissue->assignedto = 0;
    if (!empty($tracker->defaultassignee)) {
        $newissue->assignedto = $tracker->defaultassignee;
    }
    $newissue->bywhomid = $localuser->id;
    $newissue->downlink = $originhostid.':'.$newissue->downlink;
    $newissue->uplink = '';

    try {
        $followid = $DB->insert_record('tracker_issue', $newissue);
    } catch (Exception $e) {
        $response->status = RPC_FAILURE;
        $response->error[] = "Remote error : Could not insert cascade issue record";
        $response->error[] = $e->error;
        return json_encode($response);
    }

    // TODO : rebind attributes and add them.
    if (!empty($newissue->attributes)) {
        $used = tracker_getelementsused_by_name($tracker);
        foreach ($newissue->attributes as $attribute) {
            // Cleanup and crossmap attribute records.
            $attribute->elementid = $used[$attribute->elementname]->id;
            unset($attribute->elementname);
            unset($attribute->id);
            $attribute->trackerid = $trackerid;
            $attribute->issueid = $followid;
            // Don't really worry if it fails.
            try {
                $DB->insert_record('tracker_issueattribute', $attribute);
            } catch (Exception $e) {
                assert(1);
            }
        }
    }
    // Get comment track and add starting comment backtrace.
    $issuecomment = new StdClass;
    $issuecomment->trackerid = $trackerid;
    $issuecomment->issueid = $followid;
    $issuecomment->userid = $localuser->id;
    $issuecomment->comment = $comment;
    $issuecomment->commentformat = FORMAT_HTML;
    $issuecomment->datecreated = time();
    try {
        $DB->insert_record('tracker_issuecomment', $issuecomment);
    } catch (Exception $e) {
        $response->status = RPC_FAILURE;
        $response->error[] = "Remote error : Could not insert cascade commment record";
    }

    $response->followid = $followid;

    return json_encode($response);
}

