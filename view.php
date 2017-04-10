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
 * @package mod_tracker
 * @category mod
 * @author Clifford Tham, Valery Fremaux from Moodle 1.8 ahead
 * @copyright  2007 MyLearningFactory (http://www.mylearningfactory.com)
 * @date 02/12/2007
 *
 * This page prints a particular instance of a tracker and handles
 * top level interactions
 */
require('../../config.php');
require_once($CFG->dirroot.'/mod/tracker/lib.php');
require_once($CFG->dirroot.'/mod/tracker/locallib.php');

$PAGE->requires->js('/mod/tracker/js/trackerview.js');
$PAGE->requires->jquery();

// Check for required parameters.
$id = optional_param('id', 0, PARAM_INT); // Course Module ID
$t  = optional_param('t', 0, PARAM_INT);  // Tracker instance ID.
$issueid = optional_param('issueid', '', PARAM_INT);  // Ticket number.
$action = optional_param('what', '', PARAM_ALPHA);

list($cm, $tracker, $course) = tracker_get_context($id, $t);

$screen = tracker_resolve_screen($tracker, $cm);
$view = tracker_resolve_view($tracker, $cm);
tracker_requires($view, $screen);

$url = new moodle_url('/mod/tracker/view.php', array('id' => $cm->id, 'view' => $view, 'screen' => $screen));

// Redirect (before outputting) traps.
if ($view == "view" && (empty($screen) || $screen == 'viewanissue' || $screen == 'editanissue') && empty($issueid)) {
    redirect(new moodle_url('/mod/tracker/view.php', array('id' => $cm->id, 'view' => 'view', 'screen' => 'browse')));
}
if ($view == 'reportanissue') {
    redirect(new moodle_url('/mod/tracker/reportissue.php', array('id' => $id)));
}

// Implicit routing.

if ($issueid) {
    $view = 'view';
    if (empty($screen)) {
        $screen = 'viewanissue';
    }
}

// Security.

require_course_login($course->id, true, $cm);

$context = context_module::instance($cm->id);
$config = get_config('mod_tracker');

// Trigger module viewed event.
$eventparams = array(
    'objectid' => $tracker->id,
    'context' => $context,
);

$event = \mod_tracker\event\course_module_viewed::create($eventparams);
$event->add_record_snapshot('tracker', $tracker);
$event->trigger();

tracker_loadpreferences($tracker->id, $USER->id);

// Search controller - special implementation.
// TODO : consider incorporing this controller back into standard MVC.

if ($action == 'searchforissues') {
    $search = optional_param('search', null, PARAM_CLEANHTML);
    $saveasreport = optional_param('saveasreport', null, PARAM_CLEANHTML);

    // Search for issues.
    if (!empty($search)) {
        tracker_searchforissues($tracker, $cm->id);
    } else if (!empty ($saveasreport)) {
        // Save search as a report.
        tracker_saveasreport($tracker->id);
    }
} else if ($action == 'viewreport') {
    tracker_viewreport($tracker->id);
} else if ($action == 'clearsearch') {
    if (tracker_clearsearchcookies($tracker->id)) {
        $returnview = ($tracker->supportmode == 'bugtracker') ? 'browse' : 'mytickets';
        $params = array('id' => $cm->id, 'view' => 'view', 'screen' => $returnview);
        redirect(new moodle_url('/mod/tracker/view.php', $params));
    }
}

$strtrackers = get_string('modulenameplural', 'tracker');
$strtracker  = get_string('modulename', 'tracker');

// Pre requisistes before output.

if ($view == 'reports') {
    require_once($CFG->dirroot.'/local/vflibs/jqplotlib.php');
    require_once($CFG->dirroot.'/mod/tracker/classes/output/mod_tracker_reports_renderer.php');
    local_vflibs_require_jqplot_libs();
}
if ($view == 'admin') {
    require_once($CFG->dirroot.'/mod/tracker/classes/output/mod_tracker_admin_renderer.php');
}

$PAGE->set_context($context);
$PAGE->set_title(format_string($tracker->name));
$PAGE->set_heading(format_string($tracker->name));
$PAGE->set_url($url);
$PAGE->set_button($OUTPUT->update_module_button($cm->id, 'tracker'));

if ($screen == 'print') {
    $PAGE->set_pagelayout('embedded');
}

$renderer = $PAGE->get_renderer('tracker');

// Process controllers.

$result = 0;
if ($view == 'view') {
    if ($action != '') {
        $result = include($CFG->dirroot.'/mod/tracker/views/view.controller.php');
    }
} else if ($view == 'resolved') {
    if ($action != '') {
        $result = include($CFG->dirroot.'/mod/tracker/views/view.controller.php');
    }
} else if ($view == 'admin') {
    if ($action != '') {
        $result = include($CFG->dirroot.'/mod/tracker/views/admin.controller.php');
    }
} else if ($view == 'profile') {
    if ($action != '') {
        $result = include($CFG->dirroot.'/mod/tracker/views/profile.controller.php');
    }
}

$output = $OUTPUT->header();

$output .= $OUTPUT->box_start('', 'tracker-view');
if (!in_array($screen, array('editanissue'))) {
    $output .= $renderer->tabs($view, $screen, $tracker, $cm);
}

// A pre-buffer that may be a controller output.
if (!empty($out)) {
    $output .= $out;
}

/*
 * Print the main part of the page
 *
 * routing to appropriate view against situation
 */

if ($view == 'view') {
    if ($result != -1) {
        switch ($screen) {
            case 'mywork': {
                $resolved = 0;
                include($CFG->dirroot.'/mod/tracker/views/viewmyassignedticketslist.php');
                break;
            }

            case 'browse': {
                if (!has_capability('mod/tracker:viewallissues', $context)) {
                    print_error ('errornoaccessallissues', 'tracker');
                } else {
                    $resolved = 0;
                    include($CFG->dirroot.'/mod/tracker/views/viewissuelist.php');
                }
                break;
            }

            case 'search': {
                include($CFG->dirroot.'/mod/tracker/search.php');
                break;
            }

            case 'viewanissue': {
                // If user it trying to view an issue, check to see if user has privileges to view this issue.
                $caps = array('mod/tracker:seeissues', 'mod/tracker:resolve', 'mod/tracker:develop', 'mod/tracker:manage');
                if (!has_any_capability($caps, $context)) {
                    print_error('errornoaccessissue', 'tracker');
                } else {
                    include($CFG->dirroot.'/mod/tracker/views/viewanissue.php');
                }
                break;
            }

            case 'editanissue': {
                if (!has_capability('mod/tracker:manage', $context)) {
                    print_error('errornoaccessissue', 'tracker');
                } else {
                    include($CFG->dirroot.'/mod/tracker/views/editanissue.php');
                }
                break;
            }

            case 'mytickets':
            default:
                $resolved = 0;
                include($CFG->dirroot.'/mod/tracker/views/viewmyticketslist.php');
        }
    }
} else if ($view == 'resolved') {
    if ($result != -1) {
        switch ($screen) {
            case 'mywork':
                $resolved = 1;
                include($CFG->dirroot.'/mod/tracker/views/viewmyassignedticketslist.php');
                break;

            case 'browse':
                if (!has_capability('mod/tracker:viewallissues', $context)) {
                    print_error('errornoaccessallissues', 'tracker');
                } else {
                    $resolved = 1;
                    include($CFG->dirroot.'/mod/tracker/views/viewissuelist.php');
                }
                break;

            case 'mytickets':
            default:
                $resolved = 1;
                include($CFG->dirroot.'/mod/tracker/views/viewmyticketslist.php');
        }
    }
} else if ($view == 'reports') {
    $result = 0;
    if ($result != -1) {
        switch ($screen) {
            case 'evolution': {
                include($CFG->dirroot.'/mod/tracker/report/evolution.php');
                break;
            }

            case 'print': {
                if (tracker_supports_feature('reports/print')) {
                    include($CFG->dirroot.'/mod/tracker/pro/report/print.php');
                }
                break;
            }

            case 'status':
            default:
                include($CFG->dirroot.'/mod/tracker/report/status.php');
        }
    }
} else if ($view == 'admin') {
    if ($result != -1) {
        switch ($screen) {
            case 'manageelements': {
                include($CFG->dirroot.'/mod/tracker/views/admin_manageelements.php');
                break;
            }

            case 'managenetwork': {
                if (tracker_supports_feature('cascade/mnet')) {
                    include($CFG->dirroot.'/mod/tracker/pro/views/admin_mnetwork.php');
                }
                break;
            }

            case 'summary':
            default:
                include($CFG->dirroot.'/mod/tracker/views/admin_summary.php');
        }
    }
} else if ($view == 'profile') {
    if ($result != -1) {
        switch ($screen) {

            case 'mypreferences': {
                include($CFG->dirroot.'/mod/tracker/views/mypreferences.php');
                break;
            }

            case 'mywatches': {
                include($CFG->dirroot.'/mod/tracker/views/mywatches.php');
                break;
            }

            case 'myqueries': {
                include($CFG->dirroot.'/mod/tracker/views/myqueries.php');
                break;
            }

            case 'myprofile':
            default:
                include($CFG->dirroot.'/mod/tracker/views/profile.php');
        }
    }
} else {
    print_error('errorfindingaction', 'tracker', $action);
}

echo $OUTPUT->box_end();

if ($course->format == 'page') {
    include_once($CFG->dirroot.'/course/format/page/xlib.php');
    page_print_page_format_navigation($cm, $backtocourse = false);
} else {
    if ($COURSE->format != 'singleactivity') {
        echo '<div style="text-align:center;margin:8px">';
        $buttonurl = new moodle_url('/course/view.php', array('id' => $course->id));
        echo $OUTPUT->single_button($buttonurl, get_string('backtocourse', 'tracker'), 'post', array('class' => 'backtocourse'));
        echo '</div>';
    }
}

echo $OUTPUT->footer();
