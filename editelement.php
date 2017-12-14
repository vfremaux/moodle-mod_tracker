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
require('../../config.php');
require_once($CFG->dirroot.'/mod/tracker/lib.php');
require_once($CFG->dirroot.'/mod/tracker/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or.
$t  = optional_param('t', 0, PARAM_INT);  // Tracker ID.
$type = required_param('type', PARAM_TEXT);  // Element class name.
$elementid = optional_param('elementid', 0, PARAM_INT);  // Element instance id.

list($cm, $tracker, $course) = tracker_get_context($id, $t);

$screen = tracker_resolve_screen($tracker, $cm);
$view = tracker_resolve_view($tracker, $cm);
// Security.

$context = context_module::instance($cm->id);
require_course_login($course->id, false, $cm);
require_capability('mod/tracker:report', $context);

// Setting page.
$url = new moodle_url('/mod/tracker/editelement.php', array('id' => $id));
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(format_string($tracker->name));
$PAGE->set_heading(format_string($tracker->name));

if (!file_exists($CFG->dirroot.'/mod/tracker/classes/trackercategorytype/'.$type.'/tracker_element_'.$type.'_form.php')) {
    print_error('Missing element form');
}

require_once($CFG->dirroot.'/mod/tracker/classes/trackercategorytype/'.$type.'/tracker_element_'.$type.'_form.php');

$formname = 'tracker_element_'.$type.'_form';
$form = new $formname(new moodle_url('/mod/tracker/editelement.php'), array('id' => $id));

if ($form->is_cancelled()) {
    $params = array('id' => $id, 'view' => $view, 'screen' => $screen);
    redirect(new moodle_url('/mod/tracker/view.php', $params));
}

if ($data = $form->get_data()) {
    $element = new StdClass;
    $element->name = $data->name;
    $element->description = $data->description;
    $element->type = $data->type;
    $element->course = (@$data->shared) ? 0 : $COURSE->id;
    $element->paramint1 = 0 + @$data->paramint1;
    $element->paramint2 = 0 + @$data->paramint2;
    $element->paramchar1 = @$data->paramchar1;
    $element->paramchar2 = @$data->paramchar2;
    if (!$data->elementid) {
        $element->id = $DB->insert_record('tracker_element', $element);
    } else {
        $element->id = $data->elementid;
        $DB->update_record('tracker_element', $element);
    }

    $elementobj = trackerelement::find_instance_by_id($tracker, $element->id);
    if (!$data->elementid && $elementobj->has_options()) {
        // Bounces to the option editor.

        // Prepare use case bounce to further code (later in controller).
        $params = array('id' => $id, 'view' => 'admin', 'what' => 'viewelementoptions', 'elementid' => $element->id);
        $url = new moodle_url('/mod/tracker/view.php', $params);
        redirect($url);
    }

    $params = array('id' => $id, 'view' => $view, 'screen' => $screen);
    redirect(new moodle_url('/mod/tracker/view.php', $params));
}

echo $OUTPUT->header();

if ($elementid) {
    $data = $DB->get_record('tracker_element', array('id' => $elementid));
    $data->elementid = $data->id;
    $data->id = $id;
} else {
    $data = new StdClass();
    $data->id = $id;
    $data->type = $type;
}

echo $OUTPUT->heading(get_string('editelement', 'tracker'));

$form->set_data($data);
$form->display();

echo $OUTPUT->footer();