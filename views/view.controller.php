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
 * @package mod-tracker
 * @category mod
 * @author Valery Fremaux
 * @date 02/12/2007
 *
 * Controller for all "view" related views
 *
 * // @usecase submitanissue // gone away
 * @usecase updateanissue
 * @usecase delete
 * @usecase updatelist
 * @usecase addcomment (form)
 * @usecase doaddcomment
 * @usecase usequery
 * @usecase register
 * @usecase unregister
 * @usecase cascade
 * @usecase distribute
 * @usecase raisepriority
 * @usecase lowerpriority
 * @usecase raisetotop
 * @usecase lowertobottom
 * @usecase askraise (form)
 * @usecase doaskraise
 */

defined('MOODLE_INTERNAL') || die();

// Update an issue ********************************************************************.

if ($action == 'updateanissue') {
    /* obsolete path
    $issue = new StdClass;

    $issue->id = required_param('issueid', PARAM_INT);
    $issue->issueid = $issue->id;
    $issue->status = required_param('status', PARAM_INT);
    $issue->assignedto = required_param('assignedto', PARAM_INT);
    $issue->summary = required_param('summary', PARAM_TEXT);
    $issue->description_editor = required_param_array('description_editor', PARAM_CLEANHTML);
    $issue->descriptionformat = $issue->description_editor['format'];
    $editoroptions = array('maxfiles' => 99, 'maxbytes' => $COURSE->maxbytes, 'context' => $context);

    $issue->resolution_editor = required_param_array('resolution_editor', PARAM_CLEANHTML);
    $issue->resolutionformat = $issue->resolution_editor['format'];

    $issue->description = file_save_draft_area_files($issue->description_editor['itemid'], $context->id, 'mod_tracker',
                                                     'issuedescription', $issue->id, $editoroptions,
                                                     $issue->description_editor['text']);
    $issue->resolution = file_save_draft_area_files($issue->resolution_editor['itemid'], $context->id, 'mod_tracker',
                                                    'issueresolution', $issue->id, $editoroptions,
                                                    $issue->resolution_editor['text']);

    if (!empty($issue->resolution)) {
        $issue->status = RESOLVED;
    }

    $issue->datereported = required_param('datereported', PARAM_INT);

    $issue->trackerid = $tracker->id;

    // If ownership has changed, prepare logging.

    $oldrecord = $DB->get_record('tracker_issue', array('id' => $issue->id));
    if ($oldrecord->assignedto != $issue->assignedto) {
        $ownership = new StdClass;
        $ownership->trackerid = $tracker->id;
        $ownership->issueid = $oldrecord->id;
        $ownership->userid = $oldrecord->assignedto;
        $ownership->bywhomid = $oldrecord->bywhomid;
        $ownership->timeassigned = ($oldrecord->timeassigned) ? $oldrecord->timeassigned : time();
        if (!$DB->insert_record('tracker_issueownership', $ownership)) {
            print_error('errorcannotlogoldownership', 'tracker');
        }
        tracker_notifyccs_changeownership($issue->id, $tracker);
    }
    $issue->bywhomid = $USER->id;
    $issue->timeassigned = time();

    if (!$DB->update_record('tracker_issue', $issue)) {
        print_error('errorcannotupdateissue', 'tracker');
    }

    // If not CCed, the assignee should be.
    tracker_register_cc($tracker, $issue, $issue->assignedto);

    // Send state change notification.
    if ($oldrecord->status != $issue->status) {
        tracker_notifyccs_changestate($issue->id, $tracker);

        // Log state change.
        $stc = new StdClass;
        $stc->userid = $USER->id;
        $stc->issueid = $issue->id;
        $stc->trackerid = $tracker->id;
        $stc->timechange = time();
        $stc->statusfrom = $oldrecord->status;
        $stc->statusto = $issue->status;
        $DB->insert_record('tracker_state_change', $stc);

        if ($stc->statusto == RESOLVED || $stc->statusto == PUBLISHED) {
            assert(1);
            // Check if was cascaded and needs backreported then backreport.
            // TODO : backreport to original.
        }
    }

    tracker_clearelements($issue->id);
    tracker_recordelements($issue, $issue);
    // TODO : process dependancies.
    $dependancies = optional_param_array('dependancies', null, PARAM_INT);
    if (is_array($dependancies)) {
        // Cleanup previous depdendancies.
        if (!$DB->delete_records('tracker_issuedependancy', array('childid' => $issue->id))) {
            print_error('errorcannotdeleteolddependancy', 'tracker');
        }
        // Install back new one.
        foreach ($dependancies as $dependancy) {
            $dependancyrec = new StdClass;
            $dependancyrec->trackerid = $tracker->id;
            $dependancyrec->parentid = $dependancy;
            $dependancyrec->childid = $issue->id;
            $dependancyrec->comment = '';
            if (!$DB->insert_record('tracker_issuedependancy', $dependancyrec)) {
                print_error('cannotwritedependancy', 'tracker');
            }
        }
    }
    */
    throw new coding_exception('This use case has been moved to editanissue.php. The code should never reach this point.');
} else if ($action == 'delete') {

    // Delete an issue record ***************************************************************.

    $issueid = required_param('issueid', PARAM_INT);

    $maxpriority = $DB->get_field('tracker_issue', 'resolutionpriority', array('id' => $issueid));

    $DB->delete_records('tracker_issue', array('id' => $issueid));
    $DB->delete_records('tracker_issuedependancy', array('childid' => $issueid));
    $DB->delete_records('tracker_issuedependancy', array('parentid' => $issueid));
    $attributeids = $DB->get_records('tracker_issueattribute', array('issueid' => $issueid), 'id', 'id,id');
    $DB->delete_records('tracker_issueattribute', array('issueid' => $issueid));
    $commentids = $DB->get_records('tracker_issuecomment', array('issueid' => $issueid), 'id', 'id,id');
    $DB->delete_records('tracker_issuecomment', array('issueid' => $issueid));
    $DB->delete_records('tracker_issueownership', array('issueid' => $issueid));
    $DB->delete_records('tracker_state_change', array('issueid' => $issueid));

    // Lower priority of every issue above.
    $sql = "
        UPDATE
            {tracker_issue}
        SET
            resolutionpriority = resolutionpriority - 1
        WHERE
            trackerid = ? AND
            resolutionpriority > ?
    ";

    $DB->execute($sql, array($tracker->id, $maxpriority));

    // TODO : send notification to all cced.

    $DB->delete_records('tracker_issuecc', array('issueid' => $issueid));

    // Clear all associated fileareas.

    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'mod_tracker', 'issuedescription', $issueid);
    $fs->delete_area_files($context->id, 'mod_tracker', 'issueresolution', $issueid);

    if ($attributeids) {
        foreach ($attributeids as $attributeid => $void) {
            $fs->delete_area_files($context->id, 'mod_tracker', 'issueattribute', $issueid);
        }
    }

    if ($commentids) {
        foreach ($commentids as $commentid => $void) {
            $fs->delete_area_files($context->id, 'mod_tracker', 'issuecomment', $commentid);
        }
    }
} else if ($action == 'updatelist') {

    // Updating list and status ******************************************************************.

    $keys = array_keys($_POST);                                // Get the key value of all the fields submitted.
    $statuskeys = preg_grep('/status./' , $keys);              // Filter out only the status.
    $assignedtokeys = preg_grep('/assignedto./' , $keys);      // Filter out only the assigned updating.
    $newassignedtokeys = preg_grep('/assignedtoi./' , $keys);  // Filter out only the new assigned.
    foreach ($statuskeys as $akey) {
        $issueid = str_replace('status', '', $akey);
        $haschanged = optional_param('schanged'.$issueid, 0, PARAM_INT);
        if ($haschanged) {
            $issue = new StdClass;
            $issue->id = $issueid;
            $issue->status = required_param($akey, PARAM_INT);
            $oldstatus = $DB->get_field('tracker_issue', 'status', array('id' => $issue->id));
            $DB->update_record('tracker_issue', $issue);
            // Check status changing and send notifications.
            if ($oldstatus != $issue->status) {
                if ($tracker->allownotifications) {
                    tracker_notifyccs_changestate($issue->id, $tracker);
                }
                // Log state change.
                $stc = new StdClass;
                $stc->userid = $USER->id;
                $stc->issueid = $issue->id;
                $stc->trackerid = $tracker->id;
                $stc->timechange = time();
                $stc->statusfrom = $oldstatus;
                $stc->statusto = $issue->status;
                $DB->insert_record('tracker_state_change', $stc);
            }
        }
    }

    // Always add a record for history.
    foreach ($assignedtokeys as $akey) {
        $issueid = str_replace('assignedto', '', $akey);
        // New ownership is triggered only when a change occured.
        $haschanged = optional_param('changed'.$issueid, 0, PARAM_INT);
        if ($haschanged) {
            // Save old assignement in history.
            $oldassign = $DB->get_record('tracker_issue', array('id' => $issueid));
            if ($oldassign->assignedto != 0) {
                $ownership = new StdClass;
                $ownership->trackerid = $tracker->id;
                $ownership->issueid = $issueid;
                $ownership->userid = $oldassign->assignedto;
                $ownership->bywhomid = $oldassign->bywhomid;
                $ownership->timeassigned = 0 + @$oldassign->timeassigned;
                $DB->insert_record('tracker_issueownership', $ownership);
            }

            // Update actual ticket.
            $issue = new StdClass;
            $issue->id = $issueid;
            $issue->bywhomid = $USER->id;
            $issue->timeassigned = time();
            $issue->assignedto = required_param($akey, PARAM_INT);
            tracker_register_cc($tracker, $issue, $issue->assignedto);
            $DB->update_record('tracker_issue', $issue);

            if ($tracker->allownotifications) {
                tracker_notifyccs_changeownership($issue->id, $tracker);
            }
        }
    }

    // Reorder priority field and discard newly resolved or abandonned.
    tracker_update_priority_stack($tracker);

} else if ($action == 'usequery') {

    // Reactivates a stored search *************************************************************.

    $queryid = required_param('queryid', PARAM_INT);
    $fields = tracker_extractsearchparametersfromdb($queryid);
} else if ($action == 'unregister') {

    // Unregister administratively a user ******************************************************.

    $issueid = required_param('issueid', PARAM_INT);
    $ccid = optional_param('ccid', $USER->id, PARAM_INT);
    $params = array('trackerid' => $tracker->id, 'issueid' => $issueid, 'userid' => $ccid);
    if (!$DB->delete_records ('tracker_issuecc', $params)) {
        print_error('errorcannotdeletecc', 'tracker');
    }
} else if ($action == 'register') {
    $issueid = required_param('issueid', PARAM_INT);
    $ccid = optional_param('ccid', $USER->id, PARAM_INT);
    $issue = $DB->get_record('tracker_issue', array('id' => $issueid));
    tracker_register_cc($tracker, $issue, $ccid);
} else if ($action == 'cascade') {
    global $USER;

    // Copy an issue to a parent tracker *********************************************************.

    $fs = get_file_storage();

    $issueid = required_param('issueid', PARAM_INT);
    $issue = $DB->get_record('tracker_issue', array('id' => $issueid));
    $attributes = $DB->get_records('tracker_issueattribute', array('issueid' => $issue->id));

    // Remaps elementid to elementname for.
    tracker_loadelementsused($tracker, $used);
    if (!empty($attributes)) {
        foreach ($attributes as $attkey => $attribute) {
            $attributes[$attkey]->elementname = @$used[$attributes[$attkey]->id]->name;
            if ($attribute->type == 'file') {
                // Get file content, encode it.
                $files = $fs->get_area_files($context->id, 'mod_tracker', 'issueattribute', $attribute->id);
                if ($files) {
                    $file = array_pop($files);
                    $issue->files[$attkey] = base64_encode($file->get_content());
                }
            }
        }
    }
    $issue->attributes = $attributes;

    /*
     * We get comments and make a single backtrack. There should not
     * be usefull to bring along full user profile. We just want not
     * to loose usefull information the previous track collected.
     */
    $comments = $DB->get_records('tracker_issuecomment', array('issueid' => $issue->id));
    $track = '';
    if (!empty($comments)) {
        // Collect userids.
        foreach ($comments as $comment) {
            $useridsarray[] = $comment->userid;
        }
        list($insql, $inparam) = $DB->get_in_or_equal($useridsarray);
        $users = $DB->get_records_select('user', "id $insql", array($inparams), 'lastname, firstname', 'id, firstname, lastname');

        // Make backtrack.
        foreach ($comments as $comment) {
            $track .= get_string('commentedby', 'tracker');
            $track .= fullname($users[$comment->userid]);
            $track .= get_string('on', 'tracker');
            $track .= userdate($comment->datecreated);
            $track .= '<br/>';
            $track .= format_text($comment->comment, $comment->commentformat);
            $track .= '<hr width="60%"/>';
        }
    }
    $issue->comment = $track;

    // Save it for further reference.
    $oldstatus = $issue->status;

    // Downlink might be appended remote side with the our remote mnet_host identity.
    $issue->downlink = $issue->trackerid.':'.$issue->id;

    include_once($CFG->dirroot.'/mod/tracker/rpclib.php');

    $islocal = false;
    if (strpos($tracker->parent, '@') === false) {
        /*
         * Tracker is local, use the rpc entry point anyway
         * emulate response
         */
        $islocal = true;
        $result = tracker_rpc_post_issue(null, $tracker->parent, $issue, $islocal);
    } else {
        // Tracker is remote, make an RPC call.

        list($remoteid, $mnethostroot) = explode('@', $tracker->parent);

        // Get network tracker properties.
        include_once($CFG->dirroot.'/mnet/xmlrpc/client.php');
        $userroot = $DB->get_field('mnet_host', 'wwwroot', array('id' => $USER->mnethostid));
        $rpcclient = new mnet_xmlrpc_client();
        $rpcclient->set_method('mod/tracker/rpclib.php/tracker_rpc_post_issue');
        $user = new StdClass;
        $user->username = $USER->username;
        $user->firstname = $USER->firstname;
        $user->lastname = $USER->lastname;
        $user->email = $USER->email;
        $user->country = $USER->country;
        $user->city = $USER->city;
        $user->lang = $USER->lang;
        $user->hostwwwroot = $userroot;
        $rpcclient->add_param($user, 'struct');
        $rpcclient->add_param($remoteid, 'int');
        $rpcclient->add_param($issue, 'struct');

        $parentmnetpeer = new mnet_peer();
        $parentmnetpeer->set_wwwroot($mnethostroot);
        if ($rpcclient->send($parentmnetpeer)) {
            $result = $rpcclient->response;
        } else {
            $result = null;
        }
    }

    if (!empty($result)) {
        $response = (object)json_decode($result);
        if ($response->status == RPC_SUCCESS) {
            $issue->status = TRANSFERED;
            if (!$islocal) {
                list($remoteid, $hostroot) = explode('@', $tracker->parent);
                $mnethostid = $DB->get_field('mnet_host', 'id', array('wwwroot' => $mnethostroot));
                $issue->uplink = $mnethostid.':'.$remoteid.':'.$response->followid;
            } else {
                $remoteid = $tracker->parent;
                $issue->uplink = '0:'.$remoteid.':'.$response->followid;
            }
            $issue->downlink = ''; // Reset downlink from what has been sent other side.
            try {
                $DB->update_record('tracker_issue', $issue);
            } catch (Exception $e) {
                print_error('errorcannotupdateissuecascade', 'tracker');
            }

            // Log state change.
            $stc = new StdClass;
            $stc->userid = $USER->id;
            $stc->issueid = $issue->id;
            $stc->trackerid = $tracker->id;
            $stc->timechange = time();
            $stc->statusfrom = $oldstatus;
            $stc->statusto = $issue->status;
            $DB->insert_record('tracker_state_change', $stc);
        } else {
            print_error('errorremote', 'tracker', '', implode('<br/>', $response->error));
        }
    } else {
        print_error('errorremotesendingcascade', 'tracker', $tracker->parent);
    }
} else if ($action == 'distribute') {

    /*
     * distribution only work with local subtrackers. Elements are not remapped
     *
     */

    // Move an issue to a subtracker **********************************************************.

    $issueid = required_param('issueid', PARAM_INT);
    $issue = $DB->get_record('tracker_issue', array('id' => $issueid));

    // Reassign tracker.

    $newtrackerid = required_param('target', PARAM_INT);
    $issue->trackerid = $newtrackerid;
    $newtracker = $DB->get_record('tracker', array('id' => $newtrackerid));

    // Remap assigned to and notify new assignee if changed.

    $trackermoduleid = $DB->get_field('modules', 'id', array('name' => 'tracker'));
    $newcm = $DB->get_record('course_modules', array('instance' => $newtracker->id, 'module' => $trackermoduleid));
    $newcontext = context_module::instance($newcm->id);

    // If assignee is in not this tracker remap assignee to default.

    if (!has_capability('mod/tracker:develop', $newcontext, $issue->assignedto)) {
        $oldassingedto = $issue->assignedto;
        $oldstatus = $issue->status;
        $issue->assignedto = $newtracker->defaultassignee;
        $issue->status = 0; // Reset status to posted so new assignee has to open it again.
        // Only notify if real change.
        if ($tracker->allownotifications &&
                ($oldassingedto != $issue->assignedto) &&
                        $newtracker->defaultassignee) {
            tracker_notifyccs_changeownership($issue->id, $newtracker);
        }

        // Log state change.
        if ($oldstatus != $issue->status) {
            $stc = new StdClass;
            $stc->userid = $USER->id;
            $stc->issueid = $issue->id;
            $stc->trackerid = $newtracker->id;
            $stc->timechange = time();
            $stc->statusfrom = $oldstatus;
            $stc->statusto = $issue->status;
            $DB->insert_record('tracker_state_change', $stc);
        }
    } else {
        if ($tracker->allownotifications) {
            tracker_notifyccs_moveissue($issue->id, $tracker, $newtracker);
        }
    }

    // Move the issue.
    $DB->update_record('tracker_issue', $issue);
    $DB->set_field_select('tracker_issueattribute', 'trackerid', $newtracker->id, " issueid = $issue->id ");
    $DB->set_field_select('tracker_state_change', 'trackerid', $newtracker->id, " issueid = $issue->id ");
    $DB->set_field_select('tracker_issueownership', 'trackerid', $newtracker->id, " issueid = $issue->id ");
    $DB->set_field_select('tracker_issuecomment', 'trackerid', $newtracker->id, " issueid = $issue->id ");
    $DB->set_field_select('tracker_issuecc', 'trackerid', $newtracker->id, " issueid = $issue->id ");

    // We must stay in our own tracker to continue distributing.
    redirect($CFG->wwwroot."/mod/tracker/view.php?id={$cm->id}&view=view&screen=browse");
    // TODO : if watchers do not have capability in the new tracker, discard them.

} else if ($action == 'raisepriority') {

    // Raises the priority of the issue *****************************************************************************.

    $issueid = required_param('issueid', PARAM_INT);
    $issue = $DB->get_record('tracker_issue', array('id' => $issueid));
    $params = array('trackerid' => $tracker->id,
                    'resolutionpriority' => $issue->resolutionpriority + 1);
    $nextissue = $DB->get_record('tracker_issue', $params);
    if ($nextissue) {
        $issue->resolutionpriority++;
        $nextissue->resolutionpriority--;
        $DB->update_record('tracker_issue', $issue);
        $DB->update_record('tracker_issue', $nextissue);
    }
    tracker_update_priority_stack($tracker);

} else if ($action == 'raisetotop') {

    // Raises the priority at top of list ***********************************************************.

    $issueid = required_param('issueid', PARAM_INT);
    $issue = $DB->get_record('tracker_issue', array('id' => $issueid));
    $maxpriority = $DB->get_field('tracker_issue', 'resolutionpriority', array('id' => $issueid));

    if ($issue->resolutionpriority != $maxpriority) {
        // Lower everyone above.
        $sql = "
            UPDATE
                {$CFG->dbprefix}tracker_issue
            SET
                resolutionpriority = resolutionpriority - 1
            WHERE
                trackerid = ? AND
                resolutionpriority > ?
        ";
        $DB->execute($sql, array($tracker->id, $issue->resolutionpriority));

        // Update to max priority.
        $issue->resolutionpriority = $maxpriority;
        $DB->update_record('tracker_issue', $issue);
    }
    tracker_update_priority_stack($tracker);

} else if ($action == 'lowerpriority') {

    // Lowers the priority of the issue ***************************************************************.

    $issueid = required_param('issueid', PARAM_INT);
    $issue = $DB->get_record('tracker_issue', array('id' => $issueid));
    if ($issue->resolutionpriority > 0) {
        $params = array('trackerid' => $tracker->id, 'resolutionpriority' => $issue->resolutionpriority - 1);
        $nextissue = $DB->get_record('tracker_issue', $params);
        $issue->resolutionpriority--;
        $nextissue->resolutionpriority++;
        $DB->update_record('tracker_issue', $issue);
        $DB->update_record('tracker_issue', $nextissue);
    }
    tracker_update_priority_stack($tracker);

} else if ($action == 'lowertobottom') {

    // Raises the priority at top of list **************************************************************.

    $issueid = required_param('issueid', PARAM_INT);
    $issue = $DB->get_record('tracker_issue', array('id' => $issueid));

    if ($issue->resolutionpriority > 0) {
        // Raise everyone beneath.
        $sql = "
            UPDATE
                {$CFG->dbprefix}tracker_issue
            SET
                resolutionpriority = resolutionpriority + 1
            WHERE
                trackerid = ? AND
                resolutionpriority < ?
        ";
        $DB->execute($sql, array($tracker->id, $issue->resolutionpriority));

        // Update to min priority.
        $issue->resolutionpriority = 0;
        $DB->update_record('tracker_issue', $issue);
    }
    tracker_update_priority_stack($tracker);
}
