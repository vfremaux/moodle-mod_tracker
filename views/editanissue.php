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

require_once($CFG->dirroot.'/mod/tracker/forms/reportissue_form.php');

$issueid = required_param('issueid', PARAM_INT);

$issue = $DB->get_record('tracker_issue', array('id' => $issueid));

if (tracker_can_edit($tracker, $context, $issue)) {
    // Opens the issue if I have capability to resolve.
    if ($issue->status < OPEN) {
        $issue->status = OPEN;
        $DB->set_field('tracker_issue', 'status', OPEN, array('id' => $issueid));
    }
}

$params = array('tracker' => $tracker,
                'cmid' => $id,
                'mode' => 'update',
                'issueid' => $issueid,
                'view' => 'view',
                'screen' => 'editanissue');
$urlparams = array('issueid' => $issueid,
                    'view' => 'view',
                    'screen' => 'editanissue');
$form = new TrackerIssueForm(new moodle_url('/mod/tracker/view.php', $urlparams), $params);

if ($form->is_cancelled()) {
    $params = array('id' => $cm->id,
                    'view' => 'view',
                    'screen' => 'viewanissue',
                    'issueid' => $issue->id);
    redirect(new moodle_url('/mod/tracker/view.php', $params));
}

if ($data = $form->get_data()) {

    $issueid = $issue->id = $data->issueid;

    if (!$issue = tracker_submitanissue($tracker, $data)) {
        print_error('errorcannotsubmitticket', 'tracker');
    }

    $event = \mod_tracker\event\tracker_issuereported::create_from_issue($tracker, $issue->id);
    $event->trigger();

    // Stores files.
    $data = file_postupdate_standard_editor($data, 'description', $form->editoroptions, $context, 'mod_tracker',
                                            'issuedescription', $data->issueid);
    // Update back reencoded field text content.
    $DB->set_field('tracker_issue', 'description', $data->description, array('id' => $issue->id));

    if (!empty($data->id)) {
        // Stores files.
        $data = file_postupdate_standard_editor($data, 'resolution', $form->editoroptions, $context, 'mod_tracker',
                                                'issueresolution', $data->issueid);
        // Update back reencoded field text content.
        $DB->set_field('tracker_issue', 'resolution', $data->resolution, array('id' => $issue->id));
    }

    // Log state change.
    $stc = new StdClass;
    $stc->userid = $USER->id;
    $stc->issueid = $issue->id;
    $stc->trackerid = $tracker->id;
    $stc->timechange = time();
    $stc->statusfrom = POSTED;
    $stc->statusto = POSTED;
    $DB->insert_record('tracker_state_change', $stc);

    if ($stc->statusto == RESOLVED || $stc->statusto == PUBLISHED) {
        assert(1);
        // Check if was cascaded and needs backreported then backreport.
        // TODO : backreport to original.
    }

    // Notify all admins.
    if ($tracker->allownotifications) {
        tracker_notify_submission($issue, $cm, $tracker);
        if ($issue->assignedto) {
            tracker_notifyccs_changeownership($issue->id, $tracker);
        }

        if (@$issue->oldstatus != $issue->status) {
            tracker_notifyccs_changestate($issue->id, $tracker);
        }
    }

    tracker_clearelements($issue->id);
    tracker_recordelements($issue, $data);

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

    $params = array('id' => $cm->id,
                    'view' => 'view',
                    'screen' => 'viewanissue',
                    'issueid' => $issueid);
    redirect(new moodle_url('/mod/tracker/view.php', $params));
}

// Start screen.
echo $output;

// Transfer ids to proper form attributes.
$formdata = clone($issue);
$formdata->issueid = $issue->id;
$formdata->id = $cm->id;
$form->set_data($formdata);
$form->display();