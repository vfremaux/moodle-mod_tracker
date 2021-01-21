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
 * Define all the restore steps that will be used by the restore_url_activity_task
 *
 * @package     mod
 * @subpackage  tracker
 * @copyright   2010 onwards Valery Fremaux (valery.freamux@club-internet.fr)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Structure step to restore one tracker activity
 */
class restore_tracker_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $tracker = new restore_path_element('tracker', '/activity/tracker');
        $paths[] = $tracker;
        $elements = new restore_path_element('tracker_element', '/activity/tracker/elements/element');
        $paths[] = $elements;
        $path = '/activity/tracker/elements/element/elementitems/elementitem';
        $elementitem = new restore_path_element('tracker_elementitem', $path);
        $paths[] = $elementitem;
        $usedelement = new restore_path_element('tracker_usedelement', '/activity/tracker/usedelements/usedelement');
        $paths[] = $usedelement;

        if ($userinfo) {
            $paths[] = new restore_path_element('tracker_issue', '/activity/tracker/issues/issue');
            $paths[] = new restore_path_element('tracker_issueattribute', '/activity/tracker/issues/issue/attribs/attrib');
            $paths[] = new restore_path_element('tracker_issuecc', '/activity/tracker/issues/issue/ccs/cc');
            $paths[] = new restore_path_element('tracker_issuecomment', '/activity/tracker/issues/issue/comments/comment');
            $paths[] = new restore_path_element('tracker_issueownership', '/activity/tracker/issues/issue/ownerships/ownership');
            $paths[] = new restore_path_element('tracker_state_change', '/activity/tracker/issues/issue/statechanges/state');
            $paths[] = new restore_path_element('tracker_issuedependancy', '/activity/tracker/dependancies/dependancy');
            $paths[] = new restore_path_element('tracker_query', '/activity/tracker/queries/query');
            $paths[] = new restore_path_element('tracker_preferences', '/activity/tracker/preferences/preference');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_tracker($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // Insert the label record.
        $newitemid = $DB->insert_record('tracker', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    protected function after_execute() {
        global $DB;

        // Remap element used to real values.
        if ($used = $DB->get_records('tracker_elementused', array('trackerid' => $this->get_new_parentid('tracker')))) {
            foreach ($used as $u) {
                $u->elementid = $this->get_mappingid('tracker_element', $u->elementid);
                $DB->update_record('tracker_elementused', $u);
            }
        }

        // Add tracker related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_tracker', 'intro', null);
    }

    protected function process_tracker_element($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->course = $this->get_courseid();

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('tracker_element', $data);
        $this->set_mapping('tracker_element', $oldid, $newitemid, false); // Has no related files.
    }

    protected function process_tracker_elementitem($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->elementid = $this->get_mappingid('tracker_element', $data->elementid);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('tracker_elementitem', $data);
        $this->set_mapping('tracker_elementitem', $oldid, $newitemid, false); // Has no related files.
    }

    protected function process_tracker_usedelement($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->trackerid = $this->get_new_parentid('tracker');
        $data->canbemodifiedby = $this->get_mappingid('user', $data->canbemodifiedby);

        /*
         * If the used element addresses a local element backuped in the tracker
         * remap the element and store it. Otherwise forget it.
         */
        $newlocalelementid = $this->get_mappingid('tracker_element', $data->elementid);
        if ($newlocalelementid) {
            $data->elementid = $newlocalelementid;

            // The data is actually inserted into the database later in inform_new_usage_id.
            $newitemid = $DB->insert_record('tracker_elementused', $data);
            $this->set_mapping('tracker_elementused', $oldid, $newitemid, false); // Has no related files.
        }
    }

    protected function process_tracker_issue($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->trackerid = $this->get_new_parentid('tracker');

        $data->reportedby = $this->get_mappingid('user', $data->reportedby);
        $data->assignedto = $this->get_mappingid('user', $data->assignedto);
        $data->bywhomid = $this->get_mappingid('user', $data->bywhomid);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('tracker_issue', $data);
        $this->set_mapping('tracker_issue', $oldid, $newitemid, false); // Has no related files.

        $this->add_related_files('mod_tracker', 'issuecomment', 'tracker_issue', null, $oldid);
        $this->add_related_files('mod_tracker', 'issuedescription', 'tracker_issue', null, $oldid);
        $this->add_related_files('mod_tracker', 'issueresolution', 'tracker_issue', null, $oldid);
    }

    protected function process_tracker_issueattribute($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->trackerid = $this->get_mappingid('tracker', $data->trackerid);
        $data->issueid = $this->get_new_parentid('issue');

        $data->elementid = $this->get_mappingid('tracker_element', $data->elementid);

        // Discard all attributes related to non local elements.
        if ($data->elementid) {
            $data->elementitemid = $this->get_mappingid('tracker_elementitem', $data->elementitemid);
            $data->timemodified = $this->apply_date_offset($data->timemodified);

            // The data is actually inserted into the database later in inform_new_usage_id.
            $newitemid = $DB->insert_record('tracker_issueattribute', $data);
            // Needs no mapping as terminal record.
            $this->set_mapping('tracker_issueattribute', $oldid, $newitemid, false); // Has no related files.

            $this->add_related_files('mod_tracker', 'issueattribute', 'tracker_issueattribute', null, $oldid);
        }
    }

    protected function process_tracker_issuecc($data) {
        global $DB;

        $data = (object)$data;

        $oldid = $data->id;

        $data->trackerid = $this->get_mappingid('tracker', $data->trackerid);
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->issueid = $this->get_new_parentid('issue');

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('tracker_issuecc', $data);
    }

    protected function process_tracker_issuecomment($data) {
        global $DB;

        $data = (object)$data;

        $oldid = $data->id;

        $data->trackerid = $this->get_mappingid('tracker', $data->trackerid);
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->issueid = $this->get_new_parentid('issue');

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('tracker_issuecomment', $data);
        // Needs no mapping as terminal record.
        $this->set_mapping('tracker_issuecomment', $oldid, $newitemid, false); // Has no related files.

        $this->add_related_files('mod_tracker', 'issuecomment', 'tracker_issuecomment', null, $oldid);
    }

    protected function process_tracker_issuedependancy($data) {
        global $DB;

        $data = (object)$data;

        $data->trackerid = $this->get_new_parentid('tracker');
        $data->parentid = $this->get_mappingid('issue', $data->parentid);
        $data->childid = $this->get_mappingid('issue', $data->childid);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('tracker_issuedependancy', $data);
        // Needs no mapping as terminal record.
    }

    protected function process_tracker_issueownership($data) {
        global $DB;

        $data = (object)$data;

        $data->trackerid = $this->get_mappingid('tracker', $data->trackerid);
        $data->issueid = $this->get_new_parentid('issue');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->bywhomid = $this->get_mappingid('user', $data->bywhomid);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('tracker_issueownership', $data);
        // Needs no mapping as terminal record.
    }

    protected function process_tracker_preferences($data) {
        global $DB;

        $data = (object)$data;

        $data->trackerid = $this->get_new_parentid('tracker');
        $data->userid = $this->get_mappingid('user', $data->userid);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('tracker_preferences', $data);
        // Needs no mapping as terminal record.
    }

    protected function process_tracker_query($data) {
        global $DB;

        $data = (object)$data;

        $data->trackerid = $this->get_new_parentid('tracker');
        $data->userid = $this->get_mappingid('user', $data->userid);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('tracker_query', $data);
        // Needs no mapping as terminal record.
    }

    protected function process_tracker_state_change($data) {
        global $DB;

        $data = (object)$data;

        $data->trackerid = $this->get_mappingid('tracker', $data->trackerid);
        $data->issueid = $this->get_new_parentid('issue');
        $data->userid = $this->get_mappingid('user', $data->userid);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('tracker_state_change', $data);
        // Needs no mapping as terminal record.
    }

}
