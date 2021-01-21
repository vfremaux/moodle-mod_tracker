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
 * Provides support for the conversion of moodle1 backup to the moodle2 format
 * Based off of a template @ http://docs.moodle.org/dev/Backup_1.9_conversion_for_developers
 *
 * @package    mod
 * @subpackage tracker
 * @copyright  2011 Valery Fremaux <valery.fremaux@club-internet.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tracker conversion handler
 */
class moodle1_mod_tracker_handler extends moodle1_mod_handler {

    /** @var moodle1_file_manager */
    protected $fileman = null;

    /** @var int cmid */
    protected $moduleid = null;

    /**
     * Declare the paths in moodle.xml we are able to convert
     *
     * The method returns list of {@link convert_path} instances.
     * For each path returned, the corresponding conversion method must be
     * defined.
     *
     * Note that the path /MOODLE_BACKUP/COURSE/MODULES/MOD/TRACKER does not
     * actually exist in the file. The last element with the module name was
     * appended by the moodle1_converter class.
     *
     * @return array of {@link convert_path} instances
     */
    public function get_paths() {
        return array(
            new convert_path(
                'tracker', '/MOODLE_BACKUP/COURSE/MODULES/MOD/TRACKER',
                array(
                    'renamefields' => array(
                        'description' => 'intro',
                        'format' => 'introformat',
                    ),
                    'newfields' => array(
                        'enabledstates' => 511,
                        'thanksmessage' => '',
                        'strictworkflow' => 0,
                    ),
                )
            ),
            new convert_path(
                'tracker_elements', '/MOODLE_BACKUP/COURSE/MODULES/MOD/TRACKER/ELEMENTS',
                array(
                )
            ),
            new convert_path(
                'tracker_element', '/MOODLE_BACKUP/COURSE/MODULES/MOD/TRACKER/ELEMENTS/ELEMENT',
                array(
                )
            ),
            new convert_path(
                'tracker_elementitems', '/MOODLE_BACKUP/COURSE/MODULES/MOD/TRACKER/ELEMENTITEMS',
                array(
                )
            ),
            new convert_path(
                'tracker_elementitem', '/MOODLE_BACKUP/COURSE/MODULES/MOD/TRACKER/ELEMENTITEMS/ELEMENTITEM',
                array(
                )
            ),
            new convert_path(
                'tracker_usedelements', '/MOODLE_BACKUP/COURSE/MODULES/MOD/TRACKER/ELEMENTUSEDS',
                array(
                )
            ),
            new convert_path(
                'tracker_usedelement', '/MOODLE_BACKUP/COURSE/MODULES/MOD/TRACKER/ELEMENTUSEDS/ELEMENTUSED',
                array(
                )
            ),
            new convert_path(
                'tracker_issues', '/MOODLE_BACKUP/COURSE/MODULES/MOD/TRACKER/ISSUES',
                array(
                )
            ),
            new convert_path(
                'tracker_issue', '/MOODLE_BACKUP/COURSE/MODULES/MOD/TRACKER/ISSUES/ISSUE',
                array(
                )
            ),
            new convert_path(
                'tracker_attributes', '/MOODLE_BACKUP/COURSE/MODULES/MOD/TRACKER/ATTRIBUTES',
                array(
                )
            ),
            new convert_path(
                'tracker_attribute', '/MOODLE_BACKUP/COURSE/MODULES/MOD/TRACKER/ATTRIBUTES/ATTRIBUTE',
                array(
                )
            ),
            new convert_path(
                'tracker_ccs', '/MOODLE_BACKUP/COURSE/MODULES/MOD/TRACKER/CCS',
                array(
                )
            ),
            new convert_path(
                'tracker_cc', '/MOODLE_BACKUP/COURSE/MODULES/MOD/TRACKER/CCS/CC',
                array(
                )
            ),
            new convert_path(
                'tracker_ownerships', '/MOODLE_BACKUP/COURSE/MODULES/MOD/TRACKER/OWNERSHIPS',
                array(
                )
            ),
            new convert_path(
                'tracker_ownership', '/MOODLE_BACKUP/COURSE/MODULES/MOD/TRACKER/OWNERSHIPS/OWNERSHIP',
                array(
                )
            ),
            new convert_path(
                'tracker_comments', '/MOODLE_BACKUP/COURSE/MODULES/MOD/COMMENTS',
                array(
                )
            ),
            new convert_path(
                'tracker_comment', '/MOODLE_BACKUP/COURSE/MODULES/MOD/COMMENTS/COMMENT',
                array(
                )
            ),
            new convert_path(
                'tracker_dependancies', '/MOODLE_BACKUP/COURSE/MODULES/MOD/DEPENDANCIES',
                array(
                )
            ),
            new convert_path(
                'tracker_dependancy', '/MOODLE_BACKUP/COURSE/MODULES/MOD/DEPENDANCIES/DEPENDANCY',
                array(
                )
            ),
            new convert_path(
                'tracker_preferences', '/MOODLE_BACKUP/COURSE/MODULES/MOD/PREFERENCES',
                array(
                )
            ),
            new convert_path(
                'tracker_preference', '/MOODLE_BACKUP/COURSE/MODULES/MOD/PREFERENCES/PREFERENCE',
                array(
                )
            ),
            new convert_path(
                'tracker_queries', '/MOODLE_BACKUP/COURSE/MODULES/MOD/QUERIES',
                array(
                )
            ),
            new convert_path(
                'tracker_query', '/MOODLE_BACKUP/COURSE/MODULES/MOD/QUERIES/QUERY',
                array(
                )
            ),
            new convert_path(
                'tracker_statechanges', '/MOODLE_BACKUP/COURSE/MODULES/MOD/STATECHANGES',
                array(
                )
            ),
            new convert_path(
                'tracker_statechange', '/MOODLE_BACKUP/COURSE/MODULES/MOD/STATECHANGES/STATECHANGE',
                array(
                )
            ),
        );
    }

    /**
     * This is executed every time we have one /MOODLE_BACKUP/COURSE/MODULES/MOD/TRACKER
     * data available
     */
    public function process_tracker($data) {
        // Get the course module id and context id.
        $instanceid = $data['id'];
        $cminfo     = $this->get_cminfo($instanceid);
        $moduleid   = $cminfo['id'];
        $contextid  = $this->converter->get_contextid(CONTEXT_MODULE, $moduleid);

        // Get a fresh new file manager for this instance.
        $this->fileman = $this->converter->get_file_manager($contextid, 'mod_tracker');

        // Convert course files embedded into the intro.
        $this->fileman->filearea = 'intro';
        $this->fileman->itemid = 0;
        $data['intro'] = moodle1_converter::migrate_referenced_files($data['intro'], $this->fileman);

        // Write tracker.xml.
        $this->open_xml_writer("activities/tracker_{$moduleid}/tracker.xml");
        $this->xmlwriter->begin_tag('activity', array('id' => $instanceid, 'moduleid' => $moduleid,
            'modulename' => 'tracker', 'contextid' => $contextid));

        $this->xmlwriter->begin_tag('tracker', array('id' => $instanceid));

        foreach ($data as $field => $value) {
            if ($field <> 'id') {
                $this->xmlwriter->full_tag($field, $value);
            }
        }

        return $data;
    }

    /**
     * This is executed when we reach the closing </MOD> tag of our 'forum' path
     */
    public function on_tracker_end() {

        // Flush last pending tmp structure (issues).
        $this->flushtmp();

        // Finish writing tracker.xml.
        $this->xmlwriter->end_tag('tracker');
        $this->xmlwriter->end_tag('activity');
        $this->close_xml_writer();

        // Write inforef.xml.
        $this->open_xml_writer("activities/tracker_{$this->moduleid}/inforef.xml");
        $this->xmlwriter->begin_tag('inforef');
        $this->xmlwriter->begin_tag('fileref');
        foreach ($this->fileman->get_fileids() as $fileid) {
            $this->write_xml('file', array('id' => $fileid));
        }
        $this->xmlwriter->end_tag('fileref');
        $this->xmlwriter->end_tag('inforef');
        $this->close_xml_writer();
    }

    // ELEMENT.
    // Need wait for all elements an elements item collected into memory structure as nesting change structure occurs.
    public function on_tracker_elements_start() {
        $this->tmp->subs['elements'] = array();
    }

    public function on_tracker_elements_end() {
        // We should wait for elementitems processing.
    }

    // Process element in one single write.
    public function process_tracker_element($data) {

        $instanceid = $data['id'];

        // Process data.

        // Store data within temp structure.
        $nestedelm = new StdClass;
        $nestedelm->nodename = 'element';
        $nestedelm->data = $data;
        $nestedelm->subs = array();
        $this->tmp->subs['elements'][$instanceid] = $nestedelm;
    }

    // ELEMENT ITEM.
    public function on_tracker_elementitems_start() {
        assert(1);
        // Nothing to do, will be post processed when all elements/elementitems scanned.
    }

    public function on_tracker_elementitems_end() {
        assert(1);
        // Nothing to do, will be post processed when all elements/elementitems scanned.
    }

    // Process elementitem in one single write.
    public function process_tracker_elementitem($data) {

        $instanceid = $data['id'];
        $elementid = $data['elementid'];

        // Actually process record.

        // Store elementitem in tmp.
        $nestedelm = new StdClass;
        $nestedelm->nodename = 'elementitem';
        $nestedelm->data = $data;
        $this->tmp->subs['elements'][$elementid]->subs['elementitems'][$instanceid] = $nestedelm;
    }

    // USED ELEMENTS.
    public function on_tracker_usedelements_start() {
        $this->xmlwriter->begin_tag('usedelements');
    }

    public function on_tracker_usedelements_end() {
        $this->xmlwriter->end_tag('usedelements');
    }

    // Process usedelement in one single write.
    public function process_tracker_usedelement($data) {
        $elms = array('id' => $data['id'],
                      'trackerid' => $data['trackerid'],
                      'elementid' => $data['elementid'],
                      'sortorder' => $data['sortorder'],
                      'canbemodifiedby' => $data['canbemodifiedby'],
                      'active' => $data['active']);
        $this->write_xml('usedelement', $elms);
    }

    // PREFERENCES.
    public function on_tracker_preferences_start() {
        $this->xmlwriter->begin_tag('preferences');
    }

    public function on_tracker_preferences_end() {
        $this->xmlwriter->end_tag('preferences');
    }

    // Process preference in one single write.
    public function process_tracker_preference($data) {
        $this->write_xml('preference', array('id' => $data['id']));
    }

    // QUERIES.
    public function on_tracker_queries_start() {
        $this->xmlwriter->begin_tag('queries');
    }

    public function on_tracker_queries_end() {
        $this->xmlwriter->end_tag('queries');
    }

    // Process query in one single write.
    public function process_tracker_query($data) {
        $this->write_xml('query', array('id' => $data['id']));
    }

    // DEPENDANCIES.
    public function on_tracker_dependancies_start() {
        $this->xmlwriter->begin_tag('dependancies');
    }

    public function on_tracker_dependancies_end() {
        $this->xmlwriter->end_tag('dependancies');
    }

    // Process dependancy in one single write.
    public function process_tracker_dependancy($data) {
        $this->write_xml('dependancy', array('id' => $data['id']));
    }

    // ISSUES.
    public function on_tracker_issues_start() {
        $this->tmp->subs['issues'] = array();
    }

    public function on_tracker_issues_end() {
        assert(1);
        // We should wait for all issue subs processed.
    }

    // Process issue in one single write.
    public function process_tracker_issue($data) {

         $instanceid     = $data['id'];

        // Store data within temp structure.
        $nestedelm = new StdClass;
        $nestedelm->nodename = 'issue';
        $nestedelm->data = $data;
        $nestedelm->subs = array();
        $this->tmp->subs['issues'][$instanceid] = $nestedelm;
    }

    // ATTRIBUTES.
    public function on_tracker_attributes_start() {
        assert(1);
        // Nothing to do, will be post processed when all issues/subs scanned.
    }

    public function on_tracker_attributes_end() {
        assert(1);
        // Nothing to do, will be post processed when all issues/subs scanned.
    }

    // Process attribute in one single write.
    public function process_tracker_attribute($data) {

        $instanceid = $data['id'];
        $issueid = $data['issueid'];

        // Store data within temp structure.
        $nestedelm = new StdClass;
        $nestedelm->nodename = 'attribute';
        $nestedelm->data = $data;
        $nestedelm->subs = array();
        $this->tmp->subs['issues'][$issueid]->subs['attributes'][$instanceid] = $nestedelm;
    }

    // CCS.
    public function on_tracker_ccs_start() {
        assert(1);
        // Nothing to do, will be post processed when all issues/subs scanned.
    }

    public function on_tracker_ccs_end() {
        assert(1);
        // Nothing to do, will be post processed when all issues/subs scanned.
    }

    // Process cc in one single write.
    public function process_tracker_cc($data) {

        $instanceid = $data['id'];
        $issueid = $data['issueid'];

        // Store data within temp structure.
        $nestedelm = new StdClass;
        $nestedelm->nodename = 'cc';
        $nestedelm->data = $data;
        $nestedelm->subs = array();
        $this->tmp->subs['issues'][$issueid]->subs['ccs'][$instanceid] = $nestedelm;
    }

    // COMMENTS.
    public function on_tracker_comments_start() {
        assert(1);
        // Nothing to do, will be post processed when all issues/subs scanned.
    }

    public function on_tracker_comments_end() {
        assert(1);
        // Nothing to do, will be post processed when all issues/subs scanned.
    }

    // Process comment in one single write.
    public function process_tracker_comment($data) {

        $instanceid = $data['id'];
        $issueid = $data['issueid'];

        // Store data within temp structure.
        $nestedelm = new StdClass;
        $nestedelm->nodename = 'comment';
        $nestedelm->data = $data;
        $nestedelm->subs = array();
        $this->tmp->subs['issues'][$issueid]->subs['comments'][$instanceid] = $nestedelm;
    }

    // OWNERSHIPS.
    public function on_tracker_ownerships_start() {
        assert(1);
        // Nothing to do, will be post processed when all issues/subs scanned.
    }

    public function on_tracker_ownerships_end() {
        assert(1);
        // Nothing to do, will be post processed when all issues/subs scanned.
    }

    // Process ownership in one single write.
    public function process_tracker_ownership($data) {

        $instanceid = $data['id'];
        $issueid = $data['issueid'];

        // Store data within temp structure.
        $nestedelm = new StdClass;
        $nestedelm->nodename = 'ownership';
        $nestedelm->data = $data;
        $nestedelm->subs = array();
        $this->tmp->subs['issues'][$issueid]->subs['ownerships'][$instanceid] = $nestedelm;
    }

    // CHANGESTATES.
    public function on_tracker_statechanges_start() {
        assert(1);
        // Nothing to do, will be post processed when all issues/subs scanned.
    }

    public function on_tracker_statechanges_end() {
        assert(1);
        // Nothing to do, will be post processed when all issues/subs scanned.
    }

    // Process statechange in one single write.
    public function process_tracker_statechange($data) {

        $instanceid = $data['id'];
        $issueid = $data['issueid'];

        // Store data within temp structure.
        $nestedelm = new StdClass;
        $nestedelm->nodename = 'statechange';
        $nestedelm->data = $data;
        $nestedelm->subs = array();
        $this->tmp->subs['issues'][$issueid]->subs['statechanges'][$instanceid] = $nestedelm;
    }

    /**
     * pursuant a tmp structure is set, flushes in xml file all the structure in order.
     * processes recursively through the tmp structure.
     */
    protected function flushtmp($node = null) {

        if (is_null($node) && !isset($this->tmp)) {
            return;
        }
        if (is_null($node)) {
            $node = $this->tmp;
        }
        if (empty($node->subs)) {
            return;
        }

        foreach ($node->subs as $subset => $subdata) {
            // Start element set.
            $this->xmlwriter->begin_tag($subset);

            foreach ($subdata as $sub) {

                $instanceid = $sub->data['id'];

                // Start element.
                $this->xmlwriter->begin_tag($sub->nodename, array('id' => $instanceid));

                // Write fields.
                if (isset($sub->data)) {
                    foreach ($sub->data as $field => $value) {
                        if ($field <> 'id') {
                            $this->xmlwriter->full_tag($field, $value);
                        }
                    }
                }

                // If has own subs, recurse.
                if (!empty($sub->subs)) {
                    $this->flushtmp($sub);
                }

                // End element.
                $this->xmlwriter->end_tag($sub->nodename);
            }

            // End element set.
            $this->xmlwriter->end_tag($subset);
        }

        // Free some memory.
        unset($node);
    }
}
