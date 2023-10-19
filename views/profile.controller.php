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
 * @package  mod_tracker
 * @category mod
 * @author   Clifford Tham, Valery Fremaux > 1.8
 *
 * Controller for all "profile" related views
 *
 * @usecase register
 * @usecase unregister
 * @usecase editwatch (form)
 * @usecase updatewatch
 * @usecase saveprefs
 */
namespace mod_tracker;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/tracker/classes/controller.class.php');

class profile_controller extends base_controller {

    public function receive($cmd, $data = null) {

        if (parent::receive($cmd, $data)) {
            return;
        }

        switch ($cmd) {
            case 'register': {
                $this->data->issueid = required_param('issueid', PARAM_INT);
                $this->received = true;
                break;
            }

            case 'unregister': {
                $this->data->issueid = required_param('issueid', PARAM_INT);
                $this->data->ccid = required_param('ccid', PARAM_INT);
                $this->received = true;
                break;
            }
        }
    }

    public function process($cmd) {
        global $DB, $USER;

        parent::process($cmd);

        if ($cmd == 'register') {
            // Register to an issue ************************************************************************.
            $params = array('trackerid' => $this->tracker->id, 'issueid' => $this->data->issueid, 'userid' => $USER->id);
            if (!$DB->get_record('tracker_issuecc', $params)) {
                $cc->trackerid = $this->tracker->id;
                $cc->issueid = $this->data->issueid;
                $cc->userid = $USER->id;
                $cc->events = (isset($USER->trackerprefs->eventmask)) ? $USER->trackerprefs->eventmask : ALL_EVENTS;
                $DB->insert_record('tracker_issuecc', $cc);
            }
            $this->done = true;

        } else if ($cmd == 'unregister') {
            // Unregister a watch on an issue *************************************************************.
            if (!$DB->delete_records('tracker_issuecc', array('trackerid' => $tracker->id, 'issueid' => $issueid, 'userid' => $ccid))) {
                $e->issue = $tracker->ticketprefix.$issueid;
                $e->userid = $ccid;
                print_error('errorcannotdeletecarboncopyforuser', 'tracker', $e);
            }
            $this->done = true;

        } else if ($action == 'unregisterall') {
            // Unregister all my watches ******************************************************************.
            $userid = required_param('userid', PARAM_INT);
            $DB->delete_records ('tracker_issuecc', array('trackerid' => $tracker->id, 'userid' => $userid));
            $this->done = true;

        } else if ($action == 'editwatch') {
            // Ask for editing the watchers configuration ************************************************.
            $ccid = optional_param('ccid', '', PARAM_INT);
            if (!$form = $DB->get_record('tracker_issuecc', array('id' => $ccid))) {
                print_error('errorcannoteditwatch', 'tracker');
            }
            $issue = $DB->get_record('tracker_issue', array('id' => $form->issueid));
            $form->summary = $issue->summary;

            include($CFG->dirroot.'/mod/tracker/views/editwatch.html');
            $this->done = true;
            return -1;

        } else if ($action == 'saveprefs') {
            assert(1);
            // Deferred to mypreferences.php view.
        }
    }
}
