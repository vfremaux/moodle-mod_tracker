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

defined('MOODLE_INTERNAL') || die();

function xmldb_tracker_upgrade($oldversion = 0) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    $result = true;

    if ($oldversion < 2008091900) {

        // Define field parent to be added to tracker.
        $table = new xmldb_table('tracker');
        $field = new xmldb_field('parent');
        $field->set_attributes(XMLDB_TYPE_CHAR, '80', null, null, null, null, 'timemodified');

        // Launch add field parent.
        $dbman->add_field($table, $field);

        // Tracker savepoint reached.
        upgrade_mod_savepoint(true, 2008091900, 'tracker');
    }

    if ($result && $oldversion < 2008092400) {

        // Setup XML-RPC services for tracker.
        tracker_install();

        // Tracker savepoint reached.
        upgrade_mod_savepoint(true, 2008092400, 'tracker');
    }

    if ($result && $oldversion < 2008092602) {

        // Define field supportmode to be added to tracker.
        $table = new xmldb_table('tracker');
        $field = new xmldb_field('supportmode');
        $field->set_attributes(XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'bugtracker', 'parent');

        // Launch add field supportmode.
        $dbman->add_field($table, $field);

        // Tracker savepoint reached.
        upgrade_mod_savepoint(true, 2008092602, 'tracker');
    }

    if ($result && $oldversion < 2009042500) {

        // Define field supportmode to be added to tracker.
        $table = new xmldb_table('tracker_issue');
        $field = new xmldb_field('resolutionpriority');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, 'resolutionformat');

        // Launch add field supportmode.
        $dbman->add_field($table, $field);

        // Tracker savepoint reached.
        upgrade_mod_savepoint(true, 2009042500, 'tracker');
    }

    if ($result && $oldversion < 2009042503) {

        // Reassign all priorities.
        require_once($CFG->dirroot.'/mod/tracker/locallib.php');
        $trackers = $DB->get_records('tracker', array());
        if ($trackers) {
            foreach ($trackers as $tracker) {
                $issues = $DB->get_records('tracker_issue', array('trackerid' => $tracker->id));
                if ($issues) {
                    $priority = 1;
                    foreach ($issues as $issue) {
                        // set once at upgrade and never again.
                        if ($issue->status < RESOLVED) {
                            $issue->resolutionpriority = $priority;
                            $priority++;
                            $DB->update_record('tracker_issue', $issue);
                        }
                    }
                }
            }
        }

        // Add comment enabling to all events.
        $ccs = $DB->get_records('tracker_issuecc');
        if ($ccs) {
            foreach ($ccs as $cc) {
                $cc->events |= ON_COMMENT;
                $DB->update_record('tracker_issuecc', $cc);
            }
        }

        // Tracker savepoint reached.
        upgrade_mod_savepoint(true, 2009042503, 'tracker');
    }

    // Fix field size for parent encoding in remote cascade. (long wwwroots).
    if ($result && $oldversion < 2009090800) {

        // Changing precision of field parent on table tracker to (80).
        $table = new xmldb_table('tracker');
        $field = new xmldb_field('parent');
        $field->set_attributes(XMLDB_TYPE_CHAR, '80', null, null, null, null, 'timemodified');

        // Launch change of precision for field parent.
        $dbman->change_field_precision($table, $field);

        // Tracker savepoint reached.
        upgrade_mod_savepoint(true, 2009090800, 'tracker');
    }

    if ($result && $oldversion < 2010061000) {

        // Define field defaultassignee to be added to tracker.
        $table = new xmldb_table('tracker');
        $field = new xmldb_field('defaultassignee');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'supportmode');

        // Launch add field defaultassignee
        $dbman->add_field($table, $field);

        // tracker savepoint reached
        upgrade_mod_savepoint(true, 2010061000, 'tracker');
    }

    if ($result && $oldversion < 2011070400) {

        // Define field subtrackers to be added to tracker.
        $table = new xmldb_table('tracker');
        $field = new xmldb_field('subtrackers');
        $field->set_attributes(XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, '0', 'defaultassignee');

        // Launch add field subtrackers.
        $dbman->add_field($table, $field);

        // Tracker savepoint reached.
        upgrade_mod_savepoint(true, 2011070400, 'tracker');
    }

    // Moodle 2.x break line : All new changes to Moodle 1.9 version should remain under this timestamp.

    // Unconditionnally perform Moodle 1.9 => Moodle 2 if necessary for every upgrade.

    // Rename description field to intro, and define field introformat to be added to tracker.
    $table = new xmldb_table('tracker');
    $introfield = new xmldb_field('description', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'name');
    if ($dbman->field_exists($table, $introfield)) {
        $dbman->rename_field($table, $introfield, 'intro', false);

        $formatfield = new xmldb_field('format', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'intro');
        $dbman->rename_field($table, $formatfield, 'introformat', false);
    }

    if ($result && $oldversion < 2013092200) {

        // Define field subtrackers to be added to tracker.
        $table = new xmldb_table('tracker_issue');
        $field = new xmldb_field('format', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'description');
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'descriptionformat', false);
        }

        // Tracker savepoint reached.
        upgrade_mod_savepoint(true, 2013092200, 'tracker');
    }

    if ($result && $oldversion < 2013092300) {

        // Define field subtrackers to be added to tracker.
        $table = new xmldb_table('tracker');
        $field = new xmldb_field('enabledstates', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '511', 'subtrackers');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Tracker savepoint reached.
        upgrade_mod_savepoint(true, 2013092300, 'tracker');
    }

    if ($result && $oldversion < 2013092400) {

        // Define field subtrackers to be added to tracker.
        $table = new xmldb_table('tracker');
        $field = new xmldb_field('thanksmessage', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'enabledstates');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Tracker savepoint reached.
        upgrade_mod_savepoint(true, 2013092400, 'tracker');
    }

    if ($result && $oldversion < 2014010100) {

        // Define field strictworkflow to be added to tracker.
        $table = new xmldb_table('tracker');
        $field = new xmldb_field('strictworkflow', XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'thanksmessage');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // We shifted mask values one factor above.
        $sql = "
            UPDATE {tracker_preferences} SET value = value * 2 WHERE name = 'eventmask'
        ";
        $DB->execute($sql);

        // Tracker savepoint reached.
        upgrade_mod_savepoint(true, 2014010100, 'tracker');
    }

    if ($result && $oldversion < 2015072300) {

        // Define field uplink to be added to tracker.
        $table = new xmldb_table('tracker_issue');
        $field = new xmldb_field('uplink', XMLDB_TYPE_CHAR, 10, null, null, null, null, 'resolutionpriority');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field downlink to be added to tracker.
        $field = new xmldb_field('downlink', XMLDB_TYPE_CHAR, 10, null, null, null, null, 'uplink');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Tracker savepoint reached.
        upgrade_mod_savepoint(true, 2015072300, 'tracker');
    }

    if ($result && $oldversion < 2015080400) {

        // Define field uplink to be added to tracker.
        $table = new xmldb_table('tracker');
        $field = new xmldb_field('networkable', XMLDB_TYPE_INTEGER, 1, null, null, null, 0, 'strictworkflow');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Tracker savepoint reached.
        upgrade_mod_savepoint(true, 2015080400, 'tracker');
    }

    if ($result && $oldversion < 2015080500) {

        // Define field uplink to be added to tracker.
        $table = new xmldb_table('tracker_elementused');
        $field = new xmldb_field('mandatory', XMLDB_TYPE_INTEGER, 1, null, null, null, 0, 'active');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('private', XMLDB_TYPE_INTEGER, 1, null, null, null, 0, 'mandatory');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Tracker savepoint reached.
        upgrade_mod_savepoint(true, 2015080500, 'tracker');
    }

    if ($result && $oldversion < 2015080600) {

        // Define field uplink to be added to tracker.
        $table = new xmldb_table('tracker_element');
        $field = new xmldb_field('paramint1', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'type');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('paramint2', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'paramint1');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('paramchar1', XMLDB_TYPE_CHAR, 32, null, null, null, null, 'paramint2');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('paramchar2', XMLDB_TYPE_CHAR, 100, null, null, null, null, 'paramchar1');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Tracker savepoint reached.
        upgrade_mod_savepoint(true, 2015080600, 'tracker');
    }

    return $result;
}

