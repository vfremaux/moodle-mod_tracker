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
 * Global Search Engine for Moodle
 *
 * @package mod_sharedresource
 * @category mod
 * @subpackage document_wrappers
 * @author Valery Fremaux [valery.fremaux@gmail.com] > 1.8
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 *
 * document handling for the mplayer page module
 * A video media can be indexed using description and some metadata information.
 */
namespace local_search;

use StdClass;
use context_course;
use context_module;
use moodle_url;
use context;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/search/documents/document.php');
require_once($CFG->dirroot.'/local/search/documents/document_wrapper.class.php');

define('X_SEARCH_TYPE_TRACKER', 'tracker');

/**
 * a class for representing searchable information
 *
 */
class TrackerIssueSearchDocument extends SearchDocument {

    /**
     * constructor.
     * Context may be system, or category context if resource is category limited
     */
    public function __construct(&$trackerissue, $contextid) {
        global $DB;

        // Generic information; required.
        $doc = new StdClass;
        $doc->docid         = $trackerissue['id'];
        $doc->documenttype  = X_SEARCH_TYPE_TRACKER;
        $doc->itemtype      = 'issue';
        $doc->contextid     = $contextid;

        // We cannot call userdate with relevant locale at indexing time.
        $doc->title         = $trackerissue['summary'];
        $doc->date          = $trackerissue['timemodified'];

        // Remove '(ip.ip.ip.ip)' from chat author list.
        $doc->author        = $trackerissue['author'];
        $doc->contents      = strip_tags($trackerissue['description']).' '.strip_tags($trackerissue['comments']);
        $doc->url           = tracker_document_wrapper::make_link($trackerissue['id'], $contextid);

        // Module specific information; optional.
        $data = new StdClass;
        $data->metadata = '';

        // Construct the parent class.
        parent::__construct($doc, $data, 0, 0, 0, 'mod/'.X_SEARCH_TYPE_TRACKER);
    }
}

class tracker_document_wrapper extends document_wrapper {

    /**
     * constructs a valid link to a page content
     *
     * @param media_id the mplayer course module
     * @return a well formed link to session display
     */
    public static function make_link($instanceid, $contextid = null) {
        $context = context::instance_by_id($contextid);
        return new moodle_url('/mod/tracker/view.php', array('id' => $context->instanceid, 'view' => 'view', 'screen' => 'viewanissue', 'issueid' => $instanceid));
    }

    /**
     * part of search engine API. Get all searchable object fro a complete index reset.
     *
     */
    public static function get_iterator() {
        global $DB;

        $trackers = $DB->get_records('tracker');
        return $trackers;
    }

    /**
     * part of search engine API. Retrieves all items for one instance.
     *
     */
    public static function get_content_for_index(&$instance) {
        global $DB;

        $issues = $DB->get_records('tracker_issue', ['trackerid' => $instance->id]);
        $cm = get_coursemodule_from_instance('tracker', $instance->id);
        if (!$cm) {
            // Cm is gone.
            return null;
        }
        $context = context_module::instance($cm->id);

        $documents = array();
        foreach ($issues as $issue) {
            $reporter = $DB->get_record('user', ['id' => $issue->reportedby]);
            $issue->author = fullname($reporter);
            $issuearr = get_object_vars($issue);
            $issuearr['comments'] = '';
            $comments = $DB->get_records('tracker_issuecomment', ['issueid' => $issue->id]);
            if ($comments) {
                foreach ($comments as $cmt) {
                    $issuearr['comments'] .= ' '.$cmt->comment;
                }
            }
            $documents[] = new TrackerIssueSearchDocument($issuearr, $context->id);
            mtrace("finished tracker issue {$issue->id}");
        }
        return $documents;
    }

    /**
     * returns a single data search document based on an issue
     * @param itemtype the type of information (page is the only type)
     */
    public static function single_document($id, $itemtype) {
        global $DB;

        $config = get_config('local_search');

        $systemcontext = \context_system::instance();
        $issue = $DB->get_record('tracker_issue', array('id' => $id));
        $cm = get_coursemodule_from_instance('tracker', $issue->trackerid);
        if (!$cm) {
            // Cm is gone.
            return null;
        }
        $context = context_module::instance($cm->id);

        $reporter = $DB->get_record('user', ['id' => $issue->reportedby]);
        $issue->author = fullname($reporter);
        $issuearr = get_object_vars($page);
        $issuearr['comments'] = '';
        $comments = $DB->get_records('tracker_issuecomment', ['issueid' => $issue->id]);
        if ($comments) {
            foreach ($comments as $cmt) {
                $issuearr['comments'] .= ' '.$cmt->comment;
            }
        }
        $document = new TrackerIssueSearchDocument($issuearr, $context->id);
        return $document;
    }

    /**
     * returns the var names needed to build a sql query for addition/deletions
     * // TODO cms indexable records are virtual. Should proceed in a special way
     */
    public static function db_names() {
        // Template: [primary id], [table name], [time created field name], [time modified field name].
        return array('id', 'tracker_issue', 'timecreated', 'timemodified');
    }

    /**
     * this function handles the access policy to contents indexed as searchable documents. If this
     * function does not exist, the search engine assumes access is allowed.
     * When this point is reached, we already know that :
     * - user is legitimate in the surrounding context
     * - user may be guest and guest access is allowed to the module
     * - the function may perform local checks within the module information logic
     * @param path the access path to the module script code
     * @param itemtype the information subclassing (usefull for complex modules, defaults to 'standard')
     * @param this_id the item id within the information class denoted by entry_type. In cms pages, this navi_data id
     * @param user the user record denoting the user who searches
     * @param group_id the current group used by the user when searching
     * @return true if access is allowed, false elsewhere
     */
    public static function check_text_access($path, $itemtype, $thisid, $user, $groupid, $contextid) {
        global $CFG, $DB;

        $config = get_config('local_search');

        include_once("{$CFG->dirroot}/{$path}/lib.php");

        // Get the tracker issue and all related stuff.
        $issue = $DB->get_record('tracker_issue', array('id' => $thisid));
        $tracker = $DB->get_record('tracker', array('id' => $issue->trackerid));
        $context = $DB->get_record('context', array('id' => $contextid));
        $cm = $DB->get_record('course_modules', array('id' => $context->instanceid));
        if (empty($cm)) {
            return false; // Shirai 20093005 - MDL19342 - course module might have been delete.
        }

        if (!$cm->visible and !has_capability('moodle/course:viewhiddenactivities', $context)) {
            if (!empty($config->access_debug)) {
                echo "Search reject : hidden tracker resource ";
            }
            return false;
        }

        // Capability check : user should have tracker access
        if (!has_capability('mod/tracker:seeissues', $context)) {
            if (!empty($config->access_debug)) {
                echo "search reject : No capability to view";
            }
            return false;
        }

        $aminccs = $DB->record_exists('tracker_issuecc', ['issueid' => $thisid, 'userid' => $USER->id]);

        // Mode check : if tracker is user support, see only owned, or assigned issues.
        switch ($tracker->supportmode) {
            case 'ticketting' : {
                if ($issue->reportedby != $USER->id && $issue->assignedto != $USER->id && !$aminccs) {
                    return false;
                }
                break;
            }

            case 'taskspread' : {
                if ($issue->reportedby != $USER->id && $issue->assignedto != $USER->id && !$aminccs) {
                    return false;
                }
                break;
            }

            case 'bugtracker' : {
                // more liberal access to issues. Everyone can see.
                break;
            }
        }

        // Group check : entries should be in accessible groups.
        $course = $DB->get_record('course', array('id' => $tracker->course));
        if ($groupid >= 0 &&
                (groups_get_activity_groupmode($cm) == SEPARATEGROUPS) &&
                        (groups_is_member($groupid))) {
            if (!empty($config->access_debug)) {
                echo "search reject : separated grouped tracker item";
            }
            return false;
        }

        return true;
    }
}