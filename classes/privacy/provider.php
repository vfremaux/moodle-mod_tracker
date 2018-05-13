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

namespace mod_tracker\privacy;

use \core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

class provider implements \core_privacy\local\metadata\provider {

    public static function get_metadata(collection $collection) : collection {

        $fields = [
            'trackerid' => 'privacy:metadata:tracker_issue:trackerid',
            'summary' => 'privacy:metadata:tracker_issue:summary',
            'description' => 'privacy:metadata:tracker_issue:description',
            'datereported' => 'privacy:metadata:tracker_issue:datereported',
            'reportedby' => 'privacy:metadata:tracker_issue:reportedby',
            'status' => 'privacy:metadata:tracker_issue:status',
            'assignedto' => 'privacy:metadata:tracker_issue:assignedto',
            'bywhomid' => 'privacy:metadata:tracker_issue:bywhomid',
            'timeassigned' => 'privacy:metadata:tracker_issue:timeassigned',
            'resolution' => 'privacy:metadata:tracker_issue:resolution',
            'resolutionpriority' => 'privacy:metadata:tracker_issue:resolutionpriority',
            'uplink' => 'privacy:metadata:tracker_issue:uplink',
            'downlink' => 'privacy:metadata:tracker_issue:downlink',
        ];

        $collection->add_database_table('tracker_issue', $fields, 'privacy:metadata:tracker_issue');

        $fields = [
            'trackerid' => 'privacy:metadata:tracker_issuecc:trackerid',
            'userid' => 'privacy:metadata:tracker_issuecc:userid',
            'issueid' => 'privacy:metadata:tracker_issuecc:issueid',
            'events' => 'privacy:metadata:tracker_issuecc:events',
        ];

        $collection->add_database_table('tracker_issuecc', $fields, 'privacy:metadata:tracker_issuecc');

        $fields = [
            'trackerid' => 'privacy:metadata:tracker_issuecomment:trackerid',
            'userid' => 'privacy:metadata:tracker_issuecomment:userid',
            'issueid' => 'privacy:metadata:tracker_issuecomment:issueid',
            'comment' => 'privacy:metadata:tracker_issuecomment:comment',
            'datecreated' => 'privacy:metadata:tracker_issuecomment:datecreated',
        ];

        $collection->add_database_table('tracker_issuecomment', $fields, 'privacy:metadata:tracker_issuecomment');

        $fields = [
            'trackerid' => 'privacy:metadata:tracker_issueownership:trackerid',
            'userid' => 'privacy:metadata:tracker_issueownership:userid',
            'issueid' => 'privacy:metadata:tracker_issueownership:issueid',
            'bywhom' => 'privacy:metadata:tracker_issueownership:bywhom',
            'timeassigned' => 'privacy:metadata:tracker_issueownership:timeassigned',
        ];

        $collection->add_database_table('tracker_issueownership', $fields, 'privacy:metadata:tracker_issueownership');

        $fields = [
            'trackerid' => 'privacy:metadata:tracker_preferences:trackerid',
            'userid' => 'privacy:metadata:tracker_preferences:userid',
            'name' => 'privacy:metadata:tracker_preferences:name',
            'value' => 'privacy:metadata:tracker_preferences:value',
        ];

        $collection->add_database_table('tracker_preferences', $fields, 'privacy:metadata:tracker_preferences');

        $fields = [
            'trackerid' => 'privacy:metadata:tracker_query:trackerid',
            'userid' => 'privacy:metadata:tracker_query:userid',
            'name' => 'privacy:metadata:tracker_query:name',
            'description' => 'privacy:metadata:tracker_query:description',
            'published' => 'privacy:metadata:tracker_query:published',
            'fieldnames' => 'privacy:metadata:tracker_query:fieldnames',
            'fieldvalues' => 'privacy:metadata:tracker_query:fieldvalues',
        ];

        $collection->add_database_table('tracker_query', $fields, 'privacy:metadata:tracker_query');

        $fields = [
            'trackerid' => 'privacy:metadata:tracker_state_change:trackerid',
            'issueid' => 'privacy:metadata:tracker_state_change:issueid',
            'userid' => 'privacy:metadata:tracker_state_change:userid',
            'timechange' => 'privacy:metadata:tracker_state_change:timechange',
            'statusfrom' => 'privacy:metadata:tracker_state_change:statusfrom',
            'statusto' => 'privacy:metadata:tracker_state_change:statusto',
        ];

        $collection->add_database_table('tracker_state_change', $fields, 'privacy:metadata:tracker_state_change');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   int           $userid       The user to search.
     * @return  contextlist   $contextlist  The list of contexts used in this plugin.
     */
  public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();

        // Fetching flashcard_cards context should be sufficiant to get contexts where user is involved in.
        // It may have NO states if it has no deck cards.

        $sql = "
            SELECT
                c.id
            FROM
                {context} c
            INNER JOIN
                {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN
                {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN
                {tracker} t ON t.id = cm.instance
            LEFT JOIN
                {tracker_issue} ti ON ti.trackerid = t.id
            WHERE
                ti.reportedby = :userid
        ";

        $params = [
            'modname'           => 'tracker',
            'contextlevel'      => CONTEXT_MODULE,
            'userid'  => $userid,
        ];
 
        $contextlist->add_from_sql($sql, $params);

        $sql = "
            SELECT
                c.id
            FROM
                {context} c
            INNER JOIN
                {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN
                {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN
                {tracker} t ON t.id = cm.instance
            LEFT JOIN
                {tracker_issuecc} ticc ON ticc.trackerid = t.id
            WHERE
                ticc.userid = :userid
        ";

        $params = [
            'modname'           => 'tracker',
            'contextlevel'      => CONTEXT_MODULE,
            'userid'  => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        $sql = "
            SELECT
                c.id
            FROM
                {context} c
            INNER JOIN
                {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN
                {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN
                {tracker} t ON t.id = cm.instance
            LEFT JOIN
                {tracker_issuecomment} tico ON tico.trackerid = t.id
            WHERE
                tico.userid = :userid
        ";

        $params = [
            'modname'           => 'tracker',
            'contextlevel'      => CONTEXT_MODULE,
            'userid'  => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        $sql = "
            SELECT
                c.id
            FROM
                {context} c
            INNER JOIN
                {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN
                {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN
                {tracker} t ON t.id = cm.instance
            LEFT JOIN
                {tracker_issueownership} tiow ON tiow.trackerid = t.id
            WHERE
                tiow.userid = :userid
        ";

        $params = [
            'modname'           => 'tracker',
            'contextlevel'      => CONTEXT_MODULE,
            'userid'  => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts, using the supplied exporter instance.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $ctx) {
            $instance = writer::withcontext($ctx);

            $data = new StdClass;

            // Get issues and elements.
            $params = ['trackerid' => $ctx->instanceid, 'reportedby' => $user->id];
            $issues = $DB->get_records('tracker_issue', $params);

            foreach ($issues as $issue) {
                $data->issues[] = $issue;

                $params2 = ['issueid' => $issue->id];
                $elements = $DB->get_records('issue_attribute', $params2);
                if ($elements) {
                    foreach ($elements as $element) {
                        $data->issueelements[$issue->id][] = $element;
                    }
                }
            }

            // Get ccs.
            $params = ['trackerid' => $ctx->instanceid, 'issueid' => $user->id];
            $ccs = $DB->get_records('tracker_issuecc', $params);
            if ($ccs) {
                foreach ($ccs as $cc) {
                    $data->ccs[$cc->issueid][] = $cc;
                }
            }

            // Get comments.
            $params = ['trackerid' => $ctx->instanceid, 'issueid' => $user->id];
            $comments = $DB->get_records('tracker_issuecomment', $params);
            foreach ($comments as $comment) {
                $data->comments[$comment->issueid][] = $comment;
            }

            // Get ownerships.
            $params = ['trackerid' => $ctx->instanceid, 'issueid' => $user->id];
            $ownerships = $DB->get_records('tracker_issueownership', $params);
            if ($ownerships) {
                foreach ($ownerships as $own) {
                    $data->ownerships[$own->userid][] = $own;
                }
            }

            // Get instance preferences.
            $params = ['trackerid' => $ctx->instanceid, 'issueid' => $user->id];
            $prefs = $DB->get_records('tracker_preferences', $params);
            if ($prefs) {
                foreach ($prefs as $pref) {
                    $data->ownerships[$prefs->userid][] = $pref;
                }
            }

            $instance->export_data(null, $data);
        }
    }

    public static function delete_data_for_all_users_in_context(deletion_criteria $criteria) {
        global $DB;

        $fs = get_file_storage();

        $context = $criteria->get_context();
        if (empty($context)) {
            return;
        }

        $DB->delete_records('tracker_issue', ['trackerid' => $context->instanceid]);
        $DB->delete_records('tracker_issueattribute', ['trackerid' => $context->instanceid]);
        $DB->delete_records('tracker_issuecomment', ['trackerid' => $context->instanceid]);
        $DB->delete_records('tracker_issuecc', ['trackerid' => $context->instanceid]);
        $DB->delete_records('tracker_issueownership', ['trackerid' => $context->instanceid]);
        $DB->delete_records('tracker_issuedependancy', ['trackerid' => $context->instanceid]);
        $DB->delete_records('tracker_issue_change_state', ['trackerid' => $context->instanceid]);
        $DB->delete_records('tracker_preferences', ['trackerid' => $context->instanceid]);
        $DB->delete_records('tracker_query', ['trackerid' => $context->instanceid]);

        // Delete files attached in issue related fileareas.
        $fs->clear_area_files($ctx->id, 'mod_tracker', 'issuecomment');
        $fs->clear_area_files($ctx->id, 'mod_tracker', 'issuedescription');
        $fs->clear_area_files($ctx->id, 'mod_tracker', 'issueresolution');
        $fs->clear_area_files($ctx->id, 'mod_tracker', 'issueattribute');
    }

    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $fs = get_file_storage();

        if (empty($contextlist->count())) {
            return;
        }
        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $ctx) {
            $params = ['trackerid' => $ctx->instanceid, 'reportedby' => $userid];
            $userissues = $DB->get_records('tracker_issue', $params);
            if ($userissues) {
                foreach ($userissues as $issue) {
                    $DB->delete_records('tracker_issueattribute', ['issueid' => $issue->id]);
                    $DB->delete_records('tracker_issuecomment', ['issueid' => $issue->id]);
                    $DB->delete_records('tracker_issuecc', ['issueid' => $issue->id]);
                    $DB->delete_records('tracker_issueownership', ['issueid' => $issue->id]);
                    $DB->delete_records('tracker_issuedependancy', ['parentid' => $issue->id]);
                    $DB->delete_records('tracker_issuedependancy', ['childid' => $issue->id]);
                    $DB->delete_records('tracker_issue_change_state', ['issueid' => $issue->id]);
                    $DB->delete_records('tracker_issue', $params);

                    // Delete files attached in issue context.
                    $fs->clear_area_files($ctx->id, 'mod_tracker', 'issuecomment', $comment->id);
                    $fs->clear_area_files($ctx->id, 'mod_tracker', 'issuedescription', $issue->id);
                    $fs->clear_area_files($ctx->id, 'mod_tracker', 'issueresolution', $issue->id);
                    $fs->clear_area_files($ctx->id, 'mod_tracker', 'issueattribute', $attribute->id);
                }
            }

            $DB->delete_records('tracker_query', ['trackerid' => $context->instanceid, 'userid' => $userid]);
            $DB->delete_records('tracker_preferences', ['trackerid' => $context->instanceid, 'userid' => $userid]);
        }
    }
}