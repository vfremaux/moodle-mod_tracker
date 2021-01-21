<?php
// this lib escapes to standard Moodle codechecks.

/**
 * Adds some overrides that invert role to profile mapping. This is done by role archetype
 * to help custom roles to adopt suitable behaviour.
 */
function tracker_setup_role_overrides(&$tracker, $context) {
    global $DB, $USER;

    tracker_clear_role_overrides($context);

    assert(!$DB->get_records('role_capabilities', array('contextid' => $context->id)));

    $time = time();

    if ($tracker->supportmode == 'taskspread') {
        $overrides = array(
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'teacher',
                'capability' => 'mod/tracker:report',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'editingteacher',
                'capability' => 'mod/tracker:report',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'teacher',
                'capability' => 'mod/tracker:viewallissues',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'editingteacher',
                'capability' => 'mod/tracker:viewallissues',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'teacher',
                'capability' => 'mod/tracker:seeissues',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'editingteacher',
                'capability' => 'mod/tracker:seeissues',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'teacher',
                'capability' => 'mod/tracker:comment',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'editingteacher',
                'capability' => 'mod/tracker:comment',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'teacher',
                'capability' => 'mod/tracker:managepriority',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'editingteacher',
                'capability' => 'mod/tracker:managepriority',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'teacher',
                'capability' => 'mod/tracker:develop',
                'permission' => CAP_PREVENT,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'editingteacher',
                'capability' => 'mod/tracker:develop',
                'permission' => CAP_PREVENT,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'teacher',
                'capability' => 'mod/tracker:resolve',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'editingteacher',
                'capability' => 'mod/tracker:resolve',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'student',
                'capability' => 'mod/tracker:develop',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'student',
                'capability' => 'mod/tracker:report',
                'permission' => CAP_PREVENT,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'student',
                'capability' => 'mod/tracker:comment',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'student',
                'capability' => 'mod/tracker:managepriority',
                'permission' => CAP_PREVENT,
            ),
        );
    } else if ($tracker->supportmode == 'bugtracker') {
        $overrides = array(
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'teacher',
                'capability' => 'mod/tracker:report',
                'permission' => CAP_PREVENT,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'editingteacher',
                'capability' => 'mod/tracker:report',
                'permission' => CAP_PREVENT,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'teacher',
                'capability' => 'mod/tracker:viewallissues',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'editingteacher',
                'capability' => 'mod/tracker:viewallissues',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'teacher',
                'capability' => 'mod/tracker:seeissues',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'editingteacher',
                'capability' => 'mod/tracker:seeissues',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'teacher',
                'capability' => 'mod/tracker:comment',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'editingteacher',
                'capability' => 'mod/tracker:comment',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'teacher',
                'capability' => 'mod/tracker:develop',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'editingteacher',
                'capability' => 'mod/tracker:develop',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'teacher',
                'capability' => 'mod/tracker:resolve',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'editingteacher',
                'capability' => 'mod/tracker:resolve',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'student',
                'capability' => 'mod/tracker:develop',
                'permission' => CAP_PREVENT,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'student',
                'capability' => 'mod/tracker:report',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'student',
                'capability' => 'mod/tracker:comment',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'student',
                'capability' => 'mod/tracker:seeissues',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'student',
                'capability' => 'mod/tracker:viewallissues',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'student',
                'capability' => 'mod/tracker:resolve',
                'permission' => CAP_ALLOW,
            ),
        );
    } else if ($tracker->supportmode == 'ticketting') { // User individual support.
        $overrides = array(
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'teacher',
                'capability' => 'mod/tracker:report',
                'permission' => CAP_PREVENT,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'editingteacher',
                'capability' => 'mod/tracker:report',
                'permission' => CAP_PREVENT,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'teacher',
                'capability' => 'mod/tracker:viewallissues',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'editingteacher',
                'capability' => 'mod/tracker:viewallissues',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'teacher',
                'capability' => 'mod/tracker:seeissues',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'editingteacher',
                'capability' => 'mod/tracker:seeissues',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'teacher',
                'capability' => 'mod/tracker:comment',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'editingteacher',
                'capability' => 'mod/tracker:comment',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'teacher',
                'capability' => 'mod/tracker:develop',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'editingteacher',
                'capability' => 'mod/tracker:develop',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'teacher',
                'capability' => 'mod/tracker:resolve',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'editingteacher',
                'capability' => 'mod/tracker:resolve',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'student',
                'capability' => 'mod/tracker:develop',
                'permission' => CAP_PREVENT,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'student',
                'capability' => 'mod/tracker:report',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'student',
                'capability' => 'mod/tracker:comment',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'student',
                'capability' => 'mod/tracker:viewallissues',
                'permission' => CAP_PREVENT,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'student',
                'capability' => 'mod/tracker:seeissues',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'student',
                'capability' => 'mod/tracker:managepriority',
                'permission' => CAP_ALLOW,
            ),
            array(
                'contextid' => $context->id,
                'rolearchetype' => 'student',
                'capability' => 'mod/tracker:resolve',
                'permission' => CAP_ALLOW,
            ),
        );
    }

    foreach ($overrides as $ov) {

        $overrideobj = (object) $ov;

        $roles = $DB->get_records('role', array('archetype' => $overrideobj->rolearchetype));

        foreach ($roles as $r) {
            $overrideobj->roleid = $r->id;
            $overrideobj->timemodified = $time;
            $overrideobj->modifierid = $USER->id;
            $DB->insert_record('role_capabilities', $overrideobj);
        }
    }
}
