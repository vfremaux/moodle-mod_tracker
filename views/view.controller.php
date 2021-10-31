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
 * @usecase deletecomment
 * @usecase usequery
 * @usecase register
 * @usecase unregister
 */
namespace mod_tracker;

defined('MOODLE_INTERNAL') || die();

use moodle_url;
use StdClass;

require_once($CFG->dirroot.'/mod/tracker/classes/controller.class.php');

class view_controller extends base_controller {

    public function receive($cmd, $data = null) {

        if (parent::receive($cmd, $data)) {
            return;
        }

        switch ($cmd) {
            case 'solve':
            case 'delete': {
                $this->data->issueid = required_param('issueid', PARAM_INT);
                break;
            }

            case 'updatelist' : {
                $keys = array_keys($_POST);                                // Get the key value of all the fields submitted.
                $this->data->statuskeys = preg_grep('/status./' , $keys);              // Filter out only the status.
                $this->data->assignedtokeys = preg_grep('/assignedto./' , $keys);      // Filter out only the assigned updating.
                $this->data->newassignedtokeys = preg_grep('/assignedtoi./' , $keys);  // Filter out only the new assigned.

                foreach ($this->data->statuskeys as $akey) {
                    $akey = clean_param($akey, PARAM_TEXT); // Ensure we are secure.
                    $issueid = str_replace('status', '', $akey);
                    $this->data->statushaschanged[$issueid] = optional_param('schanged'.$issueid, 0, PARAM_INT);
                    $this->data->status[$akey] = required_param($akey, PARAM_INT);
                }

                foreach ($this->data->assignedtokeys as $akey) {
                    $akey = clean_param($akey, PARAM_TEXT); // Ensure we are secure.
                    $issueid = str_replace('assignedto', '', $akey);
                    // New ownership is triggered only when a change occured.
                    $this->data->haschanged[$issueid] = optional_param('changed'.$issueid, 0, PARAM_INT);
                    $this->data->assignedto[$akey] = required_param($akey, PARAM_INT);
                }
                break;
            }

            case 'register':
            case 'unregister': {
                $this->data->issueid = required_param('issueid', PARAM_INT);
                $this->data->ccid = optional_param('ccid', $USER->id, PARAM_INT);
                break;
            }

            case 'cascade' : {
                $this->data->issueid = required_param('issueid', PARAM_INT);
            }

            case 'distribute' : {
                $this->data->issueid = required_param('issueid', PARAM_INT);
                $this->data->newtrackerid = required_param('target', PARAM_INT);
            }

            case 'raisepriority':
            case 'raisetotop':
            case 'lowerpriority':
            case 'lowertobottom': {
                $this->data->issueid = required_param('issueid', PARAM_INT);
                break;
            }

            case 'quickfind': {
                $this->data->issueid = required_param('findissueid', PARAM_ALPHANUMEXT);
                break;
            }

            case 'split': {
                throw new Exception("Only in pro version");
            }

            case 'deletecomment': {
                require_sesskey();
                $this->data->commentid = required_param('commentid', PARAM_INT);
            }
        }

        $this->received = true;
    }

    public function process($cmd) {
        global $DB, $USER;

        $return = parent::process($cmd);
        if ($this->done || !empty($return)) {
            // If parent class has processed, or gices a redirect url back.
            return $return;
        }

        // Update an issue ********************************************************************.

        if ($cmd == 'updateanissue') {
            throw new coding_exception('This use case has been moved to editanissue.php. The code should never reach this point.');

        } else if ($cmd == 'solve') {

            $issueid = $this->data->issueid;
            $issue = $DB->get_record('tracker_issue', array('id' => $issueid));
            $oldstate = $issue->status;
            $issue->status = RESOLVED;
            $DB->update_record('tracker_issue', $issue);

            // Log state change.
            $stc = new StdClass;
            $stc->userid = $USER->id;
            $stc->issueid = $issueid;
            $stc->trackerid = $this->tracker->id;
            $stc->timechange = time();
            $stc->statusfrom = $oldstate;
            $stc->statusto = RESOLVED;
            $DB->insert_record('tracker_state_change', $stc);

            // Check if was cascaded and needs backreported then backreport.
            // TODO : backreport to original.

            // Notify all admins.
            if ($this->tracker->allownotifications) {

                tracker_notify_update($issue, $this->cm, $this->tracker);

                if ($oldstate != RESOLVED) {
                    tracker_notifyccs_changestate($issueid, $this->tracker);
                }
            }
            $params = array('id' => $this->cm->id, 'view' => 'view', 'screen' => 'mytickets');
            redirect(new moodle_url('/mod/tracker/view.php', $params));

        } else if ($cmd == 'delete') {

            // Delete an issue record ***************************************************************.

            $issueid = $this->data->issueid;
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

            $DB->execute($sql, array($this->tracker->id, $maxpriority));

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

        } else if ($cmd == 'updatelist') {

            $issueid = $this->data->issueid;
            // Updating list and status ******************************************************************.
            foreach ($this->data->statuskeys as $akey) {
                $akey = clean_param($akey, PARAM_TEXT); // Ensure we are secure.
                $issueid = str_replace('status', '', $akey);
                if ($this->data->statushaschanged[$issueid]) {
                    $issue = new StdClass;
                    $issue->id = $issueid;
                    $issue->status = $this->data->status[$akey];
                    $oldstatus = $DB->get_field('tracker_issue', 'status', array('id' => $issue->id));
                    $DB->update_record('tracker_issue', $issue);
                    // Check status changing and send notifications.
                    if ($oldstatus != $issue->status) {
                        if ($this->tracker->allownotifications) {
                            tracker_notifyccs_changestate($issue->id, $this->tracker);
                        }
                        // Log state change.
                        $stc = new StdClass;
                        $stc->userid = $USER->id;
                        $stc->issueid = $issue->id;
                        $stc->trackerid = $this->tracker->id;
                        $stc->timechange = time();
                        $stc->statusfrom = $oldstatus;
                        $stc->statusto = $issue->status;
                        $DB->insert_record('tracker_state_change', $stc);
                    }
                }
            }

            // Always add a record for history.
            foreach ($this->data->assignedtokeys as $akey) {
                $akey = clean_param($akey, PARAM_TEXT); // Ensure we are secure.
                $issueid = str_replace('assignedto', '', $akey);
                // New ownership is triggered only when a change occured.
                if ($this->data->haschanged[$issueid]) {
                    // Save old assignement in history.
                    $oldassign = $DB->get_record('tracker_issue', array('id' => $issueid));
                    if ($oldassign->assignedto != 0) {
                        $ownership = new StdClass;
                        $ownership->trackerid = $this->tracker->id;
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
                    $issue->assignedto = $this->data->assignedto[$akey];
                    tracker_register_cc($this->tracker, $issue, $issue->assignedto);
                    $DB->update_record('tracker_issue', $issue);

                    if ($this->tracker->allownotifications) {
                        tracker_notifyccs_changeownership($issue->id, $this->tracker);
                    }
                }
            }

            // Reorder priority field and discard newly resolved or abandonned.
            tracker_update_priority_stack($this->tracker);

        } else if ($cmd == 'unregister') {

            // Unregister administratively a user ******************************************************.

            $issueid = $this->data->issueid;
            $params = array('trackerid' => $this->tracker->id, 'issueid' => $issueid, 'userid' => $this->data->ccid);
            if (!$DB->delete_records ('tracker_issuecc', $params)) {
                print_error('errorcannotdeletecc', 'tracker');
            }

        } else if ($cmd == 'register') {

            $issueid = $this->data->issueid;
            $issue = $DB->get_record('tracker_issue', array('id' => $issueid));
            tracker_register_cc($this->tracker, $issue, $this->data->ccid);

        } else if ($cmd == 'cascade') {
            global $USER;

            // Copy an issue to a parent tracker *********************************************************.

            $fs = get_file_storage();

            $issueid = $this->data->issueid;
            $issue = $DB->get_record('tracker_issue', array('id' => $issueid));
            $attributes = $DB->get_records('tracker_issueattribute', array('issueid' => $issue->id));

            // Remaps elementid to elementname for.
            tracker_loadelementsused($this->tracker, $used);
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
            if (strpos($this->tracker->parent, '@') === false) {
                /*
                 * Tracker is local, use the rpc entry point anyway
                 * emulate response
                 */
                $islocal = true;
                $result = tracker_rpc_post_issue(null, $this->tracker->parent, $issue, $islocal);
            } else {
                // Tracker is remote, make an RPC call.

                list($remoteid, $mnethostroot) = explode('@', $this->tracker->parent);

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
                        list($remoteid, $hostroot) = explode('@', $this->tracker->parent);
                        $mnethostid = $DB->get_field('mnet_host', 'id', array('wwwroot' => $mnethostroot));
                        $issue->uplink = $mnethostid.':'.$remoteid.':'.$response->followid;
                    } else {
                        $remoteid = $this->tracker->parent;
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
                    $stc->trackerid = $this->tracker->id;
                    $stc->timechange = time();
                    $stc->statusfrom = $oldstatus;
                    $stc->statusto = $issue->status;
                    $DB->insert_record('tracker_state_change', $stc);
                } else {
                    print_error('errorremote', 'tracker', '', implode('<br/>', $response->error));
                }
            } else {
                print_error('errorremotesendingcascade', 'tracker', $this->tracker->parent);
            }

        } else if ($cmd == 'distribute') {

            /*
             * distribution only work with local subtrackers. Elements are not remapped
             *
             */

            // Move an issue to a subtracker **********************************************************.

            $issueid = $this->data->issueid;
            $issue = $DB->get_record('tracker_issue', array('id' => $issueid));

            // Reassign tracker.

            $issue->trackerid = $newtrackerid;
            $newtracker = $DB->get_record('tracker', array('id' => $this->data->newtrackerid));

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
                if ($this->tracker->allownotifications &&
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
                if ($this->tracker->allownotifications) {
                    tracker_notifyccs_moveissue($issue->id, $this->tracker, $newtracker);
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
            $params = array('id' => $this->cm->id, 'view' => 'view', 'screen' => tracker_resolve_screen($this->tracker, $this->cm, true));
            $trackerurl = new moodle_url('/mod/tracker/view.php', $params);
            redirect($trackerurl);
            // TODO : if watchers do not have capability in the new tracker, discard them.

        } else if ($cmd == 'raisepriority') {

            // Raises the priority of the issue *****************************************************************************.

            $issueid = $this->data->issueid;
            $issue = $DB->get_record('tracker_issue', array('id' => $issueid));
            $params = array('trackerid' => $this->tracker->id,
                            'resolutionpriority' => $issue->resolutionpriority + 1);
            $nextissue = $DB->get_record('tracker_issue', $params);
            if ($nextissue) {
                $issue->resolutionpriority++;
                $nextissue->resolutionpriority--;
                $DB->update_record('tracker_issue', $issue);
                $DB->update_record('tracker_issue', $nextissue);
            }
            tracker_update_priority_stack($this->tracker);

        } else if ($cmd == 'raisetotop') {

            // Raises the priority at top of list ***********************************************************.

            $issueid = $this->data->issueid;
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
                $DB->execute($sql, array($this->tracker->id, $issue->resolutionpriority));

                // Update to max priority.
                $issue->resolutionpriority = $maxpriority;
                $DB->update_record('tracker_issue', $issue);
            }
            tracker_update_priority_stack($this->tracker);

        } else if ($cmd == 'lowerpriority') {

            // Lowers the priority of the issue ***************************************************************.

            $issueid = $this->data->issueid;
            $issue = $DB->get_record('tracker_issue', array('id' => $issueid));
            if ($issue->resolutionpriority > 0) {
                $params = array('trackerid' => $this->tracker->id, 'resolutionpriority' => $issue->resolutionpriority - 1);
                $nextissue = $DB->get_record('tracker_issue', $params);
                $issue->resolutionpriority--;
                $nextissue->resolutionpriority++;
                $DB->update_record('tracker_issue', $issue);
                $DB->update_record('tracker_issue', $nextissue);
            }
            tracker_update_priority_stack($this->tracker);

        } else if ($cmd == 'lowertobottom') {

            // Raises the priority at top of list **************************************************************.

            $issueid = $this->data->issueid;
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
                $DB->execute($sql, array($this->tracker->id, $issue->resolutionpriority));

                // Update to min priority.
                $issue->resolutionpriority = 0;
                $DB->update_record('tracker_issue', $issue);
            }
            tracker_update_priority_stack($this->tracker);

        } else if ($cmd == 'quickfind') {

            $issueid = $this->data->issueid;
            if ($DB->get_record('tracker_issue', ['id' => $issueid, 'trackerid' => $this->tracker->id])) {
                $params = ['id' => $this->cm->id, 'view' => 'view', 'screen' => 'viewanissue', 'issueid' => $issueid];
                $ticketurl = new moodle_url('/mod/tracker/view.php', $params);
                redirect($ticketurl);
            }

        } else if ($cmd == 'split') {

            // We make a new ticket entry from a comment point.
            // - Move all subsequent comments
            // - Copy ccs
            // Reset state to "posted"
            $comment = $DB->get_record('tracker_issuecomment', ['id' => $this->data->commentid]);
            $issue = $DB->get_record('tracker_issue', ['id' => $comment->issueid]);
            $oldid = $issue->id;
            unset($issue->id);
            $issue->summary = get_string('splittedfrom', 'tracker', $issue->summary);
            $issue->description = $comment->comment;
            $issue->status = POSTED;
            $issue->reportedby = $comment->userid;
            $issue->datereported = time();
            $issue->resolution = '';

            $newid = $DB->insert_record('tracker_issue', $issue);

            /*
             * No need for attributes. Those will be reedited later.
             * Usually we fork because a comment leads to another new subject,
             * so attributtes will be usually irrelevant.
             */

            // Copy all ccs.
            if ($ccs = $DB->get_records('tracker_issueccs', ['issueid' => $oldid])) {
                foreach ($ccs as $cc) {
                    unset($cc->id);
                    $cc->issueid = $newid;
                    $DB->insert_record('tracker_issueccs', $cc);
                }
            }

            // Copy all dependancies.
            if ($deps = $DB->get_records('tracker_issuedependancy', ['issueid' => $oldid])) {
                foreach ($deps as $dep) {
                    unset($dep->id);
                    $dep->issueid = $newid;
                    $DB->insert_record('tracker_issuedependancy', $dep);
                }
            }

            // Move all subsequent comments.
            $select = ' issueid = ? AND datereported > ? ';
            if ($comments = $DB->get_records_select('tracker_issuecomments', $select, [$oldid, $comment->datereported])) {
                foreach ($comments as $comment) {
                    $comment->issueid = $newid;
                    $DB->update_record('tracker_issuecomments', $comment);
                }
            }

            // Redirect to ticket editing so issue can be completed with complete info.
            $params = ['view' => $view, 'screen' => 'editanissue', 'issueid' => $newid];
            $redirecturl = new moodle_url('/mod/tracker/view.php', $params);
            redirect($redirecturl);

        } else if ($cmd == 'deletecomment') {

            $DB->delete_records('tracker_issuecomment', ['id' => $this->data->commentid]);

            $fs = get_file_storage();
            $areafiles = $fs->delete_area_files($context->id, 'mod_tracker', 'issuecomment', $commentid);
        }
    }
}