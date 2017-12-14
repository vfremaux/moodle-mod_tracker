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
 * @package mod-tracker
 * @category mod
 * @author Valery Fremaux > 1.8
 * @date 02/12/2007
 *
 * Controller for all "element management" related views
 *
 * @usecase 'createelement'
 * @usecase 'doaddelement'
 * @usecase 'editelement'
 * @usecase 'doupdateelement'
 * @usecase 'deleteelement'
 * @usecase 'submitelementoption'
 * @usecase 'viewelementoption'
 * @usecase 'editelementoption'
 * @usecase 'updateelementoption'
 * @usecase 'moveelementoptionup'
 * @usecase 'moveelementoptiondown'
 * @usecase 'addelement'
 * @usecase 'removeelement'
 * @usecase 'raiseelement'
 * @usecase 'lowerelement'
 * @usecase 'localparent'
 * @usecase 'remoteparent'
 * @usecase 'unbind'
 * @usecase 'setinactive'
 * @usecase 'setactive'
 */

defined('MOODLE_INTERNAL') || die();

if ($action == 'createelement') {

    // Create element form *************************************************************************.

    $type = required_param('type', PARAM_TEXT);
    redirect(new moodle_url('/mod/tracker/editelement.php', array('id' => $id, 'type' => $type, 'elementid' => 0)));

} else if ($action == 'editelement') {

    // Edit an element form *************************************************************************.

    $elementid = required_param('elementid', PARAM_INT);
    $type = required_param('type', PARAM_TEXT);
    $params = array('id' => $id, 'type' => $type, 'elementid' => $elementid);
    redirect(new moodle_url('/mod/tracker/editelement.php', $params));
}

if ($action == 'doupdateelement') {

    // Update an element ****************************************************************************.

    $out = '';
    $form = new StdClass;
    $form->elementid = required_param('elementid', PARAM_INT);
    $form->name = required_param('name', PARAM_ALPHANUM);
    $form->description = required_param('description', PARAM_CLEANHTML);
    $form->format = optional_param('format', '', PARAM_INT);
    $form->type = required_param('type', PARAM_ALPHA);
    $form->shared = optional_param('shared', 0, PARAM_INT);
    if (empty($form->elementid)) {
        print_error('errorelementdoesnotexist', 'tracker', $url);
    }

    $errors = array();
    if (empty($form->name)) {
        $error = new StdClass;
        $error->message = get_string('namecannotbeblank', 'tracker');
        $error->on = 'name';
        $errors[] = $error;
    }

    if (!count($errors)) {
        $element = new StdClass;
        $element->id = $form->elementid;
        $element->name = $form->name;
        $element->type = $form->type;
        $element->description = $form->description;
        $element->format = $form->format;
        $element->course = ($form->shared) ? 0 : $COURSE->id;
        $DB->update_record('tracker_element', $element);
    } else {
        $form->action = 'doupdateelement';
        ob_start();
        $out = $renderer->edit_element($cm, $form);
        $out .= ob_get_clean();
    }
}

if ($action == 'deleteelement') {

    // Delete an element from available ***************************************************.

    $elementid = required_param('elementid', PARAM_INT);
    if (!tracker_iselementused($tracker->id, $elementid)) {
        $DB->delete_records ('tracker_element', array('id' => $elementid));
        $DB->delete_records('tracker_elementitem', array('elementid' => $elementid));
    } else {
        // Should not even be proposed by the GUI.
        print_error('errorcannotdeleteelement', 'tracker', $url);
    }
}

if ($action == 'submitelementoption') {

    // Add an element option ********************************************************************.

    $out = '';
    $form = new StdClass;
    $form->elementid = required_param('elementid', PARAM_INT);
    $form->name = required_param('name', PARAM_ALPHANUM);
    $form->description = required_param('description', PARAM_CLEANHTML);
    $form->type = required_param('type', PARAM_ALPHA);
    $element = $DB->get_record('tracker_element', array('id' => $form->elementid));
    // Check validity.
    $errors = array();

    if ($DB->count_records('tracker_elementitem', array('elementid' => $form->elementid, 'name' => $form->name))) {
        $error = new StdClass;
        $error->message = get_string('optionisused', 'tracker', $url);
        $error->on = 'name';
        $errors[] = $error;
    }

    if ($form->name == '') {
        unset($error);
        $error = new StdClass;
        $error->message = get_string('optionnamecannotbeblank', 'tracker');
        $error->on = 'name';
        $errors[] = $error;
    }

    if ($form->description == '') {
        unset($error);
        $error = new StdClass;
        $error->message = get_string('descriptionisempty', 'tracker');
        $error->on = 'description';
        $errors[] = $error;
    }

    if (!count($errors)) {
        $option = new StdClass;
        $option->name = strtolower($form->name);
        $option->description = $form->description;
        $option->elementid = $form->elementid;
        $countoptions = 0 + $DB->count_records('tracker_elementitem', array('elementid' => $form->elementid));
        $option->sortorder = $countoptions + 1;
        $DB->insert_record('tracker_elementitem', $option);
        $form->name = '';
        $form->description = '';
    } else {
        // Print errors.
        $errorstr = '';
        foreach ($errors as $error) {
            $errorstrs[] = $error->message;
        }
        $out .= $OUTPUT->box(implode('<br/>', $errorstrs), 'center', '70%', '', 5, 'errorbox');
    }
    $out .= $OUTPUT->heading(get_string('editoptions', 'tracker'));
    $element = trackerelement::find_instance_by_id($tracker, $form->elementid);
    $out .= $renderer->option_list_view($cm, $element);
    $caption = get_string('addanoption', 'tracker');
    $out .= $OUTPUT->heading($caption . $OUTPUT->help_icon('options', 'tracker', false));

    $out .= $renderer->edit_option_form($cm, $form, 'submit', @$errors);
    echo $OUTPUT->header();
    echo $out;
    echo $OUTPUT->footer();
    die;
}

if ($action == 'viewelementoptions') {

    // Edit an element option **********************************************************************.

    $form = new StdClass();
    $form->elementid = optional_param('elementid', @$bounceelementid, PARAM_INT);
    if ($form->elementid) {
        $element = trackerelement::find_instance_by_id($tracker, $form->elementid);
        $form->type = $element->type;
        $out = $OUTPUT->heading(get_string('editoptions', 'tracker'));
        $out .= '<center>';
        $element = trackerelement::find_instance_by_id($tracker, $form->elementid);
        $out .= $renderer->option_list_view($cm, $element);
        $out .= $OUTPUT->heading(get_string('addanoption', 'tracker'));

        $out .= $renderer->edit_option_form($cm, $form, 'submit', @$errors);

        $out .= '</center>';

        echo $OUTPUT->header();
        echo $out;
        echo $OUTPUT->footer();
        die;
    } else {
        print_error('errorcannotviewelementoption', 'tracker', $url);
    }
    return -1;
}

if ($action == 'deleteelementoption') {

    // Delete an element option *********************************************************************.

    $form = new StdClass;
    $form->elementid = optional_param('elementid', null, PARAM_INT);
    $form->optionid = required_param('optionid', PARAM_INT);
    $element = trackerelement::find_instance_by_id($tracker, $form->elementid);
    $deletedoption = $element->get_option($form->optionid);
    $form->type = $element->type;

    if ($DB->get_records('tracker_issueattribute', array('elementitemid' => $form->optionid))) {
        print_error('errorcannotdeleteoption', 'tracker');
    }
    $DB->delete_records('tracker_elementitem', array('id' => $form->optionid));

    // Renumber higher records.
    $sql = "
        UPDATE
            {tracker_elementitem}
        SET
            sortorder = sortorder - 1
        WHERE
            elementid = ? AND
            sortorder > ?
    ";
    $DB->execute($sql, array($form->elementid, $deletedoption->sortorder));
    $out = $OUTPUT->heading(get_string('editoptions', 'tracker'));
    $element = trackerelement::find_instance_by_id($tracker, $form->elementid);
    $out .= $renderer->option_list_view($cm, $element);
    $caption = get_string('addanoption', 'tracker');
    $out .= $OUTPUT->heading($caption . $OUTPUT->help_icon('options', 'tracker', false));

    $out .= $renderer->edit_option_form($cm, $form, @$errors);

    echo $OUTPUT->header();
    echo $out;
    echo $OUTPUT->footer();
    die;
}

if ($action == 'editelementoption') {

    // Edit an element option *******************************************************.

    $form = new StdClass;
    $form->elementid = required_param('elementid', PARAM_INT);
    $form->optionid = required_param('optionid', PARAM_INT);
    $element = trackerelement::find_instance_by_id($tracker, $form->elementid);
    $option = $element->get_option($form->optionid);
    $form->type = $element->type;
    $form->name = $option->name;
    $form->description = $option->description;

    $out .= $renderer->edit_option_form($cm, $form, 'update', @$errors);

    return -1;
}

if ($action == 'updateelementoption') {

    // Edit an element option *****************************.

    $form = new Stdclass();
    $form->elementid = required_param('elementid', PARAM_INT);
    $form->optionid = required_param('optionid', PARAM_INT);
    $form->name = required_param('name', PARAM_ALPHANUM);
    $form->description = required_param('description', PARAM_CLEANHTML);
    $form->format = optional_param('format', 0, PARAM_INT);

    $element = trackerelement::find_instance_by_id($tracker, $form->elementid);
    $form->type = $element->type;

    // Check validity.
    $errors = array();
    $select = " elementid = ? AND name = ? AND id != ? ";
    $params = array($form->elementid, $form->name, $form->optionid);
    if ($DB->count_records_select('tracker_elementitem', $select, $params)) {
        $error = new StdClass;
        $error->message = get_string('optionisused', 'tracker');
        $error->on = 'name';
        $errors[] = $error;
    }

    if ($form->name == '') {
        unset($error);
        $error = new StdClass;
        $error->message = get_string('optionnamecannotbeblank', 'tracker');
        $error->on = 'name';
        $errors[] = $error;
    }

    if ($form->description == '') {
        unset($error);
        $error = new StdClass;
        $error->message = get_string('descriptionisempty', 'tracker');
        $error->on = 'description';
        $errors[] = $error;
    }

    if (!count($errors)) {
        $update = new StdClass;
        $update->id = $form->optionid;
        $update->name = $form->name;
        $update->description = $form->description;
        $update->format = $form->format;
        $DB->update_record('tracker_elementitem', $update);
        $out = $OUTPUT->heading(get_string('editoptions', 'tracker'));
        $element = trackerelement::find_instance_by_id($tracker, $form->elementid);
        $out .= $renderer->option_list_view($cm, $element);
        $out .= $OUTPUT->heading(get_string('addanoption', 'tracker'));

        $out .= $renderer->edit_option_form($cm, $form, 'submit', @$errors);

    } else {
        // Print errors.
        $errorstr = '';
        foreach ($errors as $error) {
            $errorstrs[] = $error->message;
        }
        $out .= $OUTPUT->box(implode("<br/>", $errorstrs), 'center', '70%', '', 5, 'errorbox');

        $out .= $renderer->edit_option_form($cm, $form, 'update', @$errors);
    }
    echo $OUTPUT->header();
    echo $out;
    echo $OUTPUT->footer();
    die;
}

if ($action == 'moveelementoptionup') {

    // Move an option up in list ******************************************************.

    $form = new StdClass;
    $form->elementid = required_param('elementid', PARAM_INT);
    $form->optionid = required_param('optionid', PARAM_INT);

    $option = $DB->get_record('tracker_elementitem', array('elementid' => $form->elementid, 'id' => $form->optionid));
    $element = trackerelement::find_instance_by_id($tracker, $form->elementid);
    $form->type = $element->type;
    $option->id = $form->optionid;
    $params = array('elementid' => $form->elementid, 'id' => $form->optionid);
    $sortorder = $DB->get_field('tracker_elementitem', 'sortorder', $params);

    if ($sortorder > 1) {
        $option->sortorder = $sortorder - 1;
        $previousoption = new StdClass();
        $params = array('elementid' => $form->elementid, 'sortorder' => $sortorder - 1);
        $previousoption->id = $DB->get_field('tracker_elementitem', 'id', $params);
        $previousoption->sortorder = $sortorder;

        // Swap options in database.
        $DB->update_record('tracker_elementitem', $option);
        $DB->update_record('tracker_elementitem', $previousoption);
    }
    $out = $OUTPUT->heading(get_string('editoptions', 'tracker'));
    $element = trackerelement::find_instance_by_id($tracker, $form->elementid);
    $out .= $renderer->option_list_view($cm, $element);
    $caption = get_string('addanoption', 'tracker');
    $out .= $OUTPUT->heading($caption . $OUTPUT->help_icon('options', 'tracker', false));

    $out .= $renderer->edit_option_form($cm, $form, 'submit', @$errors);

    echo $OUTPUT->header();
    echo $out;
    echo $OUTPUT->footer();
    die;
    return -1;
}

if ($action == 'moveelementoptiondown') {

    // Move an option down in list *************************************************.

    $form = new StdClass;
    $form->elementid = required_param('elementid', PARAM_INT);
    $form->optionid = required_param('optionid', PARAM_INT);

    $params = array('elementid' => $form->elementid, 'id' => $form->optionid);
    $option = $DB->get_record('tracker_elementitem', $params);
    $element = trackerelement::find_instance_by_id($tracker, $form->elementid);
    $form->type = $element->type;
    $option->id = $form->optionid;
    $params = array('elementid' => $form->elementid, 'id' => $form->optionid);
    $sortorder = $DB->get_field('tracker_elementitem', 'sortorder', $params);

    if ($sortorder < $element->maxorder) {
        $option->sortorder = $sortorder + 1;
        $nextoption = new StdClass;
        $params = array('elementid' => $form->elementid, 'sortorder' => $sortorder + 1);
        $nextoption->id = $DB->get_field('tracker_elementitem', 'id', $params);
        $nextoption->sortorder = $sortorder;

        // Swap options in database.
        $DB->update_record('tracker_elementitem', $option);
        $DB->update_record('tracker_elementitem', $nextoption);
    }

    $out = $OUTPUT->heading(get_string('editoptions', 'tracker'));
    $element = trackerelement::find_instance_by_id($tracker, $form->elementid);
    $out .= $renderer->option_list_view($cm, $element);
    $caption = get_string('addanoption', 'tracker');
    $out .= $OUTPUT->heading($caption . $OUTPUT->help_icon('options', 'tracker', false));

    $out .= $renderer->edit_option_form($cm, $form, 'submit', @$errors);

    echo $OUTPUT->header();
    echo $out;
    echo $OUTPUT->footer();
    die;

    return -1;
}

if ($action == 'addelement') {

    // Add an element to be used **************************************************.

    $elementid = required_param('elementid', PARAM_INT);

    if (!tracker_iselementused($tracker->id, $elementid)) {

        // Add element to element used table.
        $used = new StdClass;
        $used->elementid = $elementid;
        $used->trackerid = $tracker->id;
        $used->canbemodifiedby = $USER->id;
        $used->mandatory = $config->initiallymandatory;
        $used->private = $config->initiallyprivate;
        $used->active = $config->initiallyactive;

        // Get last sort order.
        $select = "trackerid = ? GROUP BY trackerid";
        $params = array($tracker->id);
        $sortorder = 0 + $DB->get_field_select('tracker_elementused', 'MAX(sortorder)', $select, $params);
        $used->sortorder = $sortorder + 1;
        $DB->insert_record ('tracker_elementused', $used);
    } else {
        // Feedback message that element is already in uses.
        print_error('erroralreadyinuse', 'tracker', $url.'&amp;view=admin');
    }
}

if ($action == 'removeelement') {

    // Remove an element from usable list ******************************************************.

    $usedid = required_param('usedid', PARAM_INT);
    $params = array('elementid' => $usedid, 'trackerid' => $tracker->id);
    $DB->delete_records ('tracker_elementused', $params);
}

if ($action == 'raiseelement') {

    // Raise element pos in usable list ********************************************************.

    $usedid = required_param('elementid', PARAM_INT);
    $params = array('elementid' => $usedid, 'trackerid' => $tracker->id);
    $used = $DB->get_record('tracker_elementused', $params);
    $params = array('sortorder' => $used->sortorder - 1, 'trackerid' => $tracker->id);
    $previous = $DB->get_record('tracker_elementused', $params);
    $used->sortorder--;
    $previous->sortorder++;
    $DB->update_record('tracker_elementused', $used);
    $DB->update_record('tracker_elementused', $previous);
}

if ($action == 'lowerelement') {

    // Lower element pos in usable list ************************************************************.

    $usedid = required_param('elementid', PARAM_INT);
    $params = array('elementid' => $usedid, 'trackerid' => $tracker->id);
    $used = $DB->get_record('tracker_elementused', $params);
    $params = array('sortorder' => $used->sortorder + 1, 'trackerid' => $tracker->id);
    $next = $DB->get_record('tracker_elementused', $params);
    $used->sortorder++;
    $next->sortorder--;
    $DB->update_record('tracker_elementused', $used);
    $DB->update_record('tracker_elementused', $next);
}

if ($action == 'localparent') {

    // Update parent tracker binding *******************************************************************.

    $parent = optional_param('localtracker', null, PARAM_INT);

    $params = array('id' => $tracker->id);
    $DB->set_field('tracker', 'parent', $parent, $params);
    $tracker->parent = $parent;
}

if ($action == 'remoteparent') {

    // Update remote parent tracker binding **************************************************************.

    $step = optional_param('step', 0, PARAM_INT);
    switch ($step) {

        case 1 : {
            // We choose the host.
            $parenthost = optional_param('remotehost', null, PARAM_RAW);
            break;
        }

        case 2 : {
            // We choose the tracker.
            $remoteparent = optional_param('remotetracker', null, PARAM_RAW);

            $params = array('id' => $tracker->id);
            $DB->set_field('tracker', 'parent', $remoteparent, $params);
            $tracker->parent = $remoteparent;
            $step = 0;
            break;
        }
    }
}

if ($action == 'unbind') {

    // Unbinds any cascade  ****************************************************************.

    $DB->set_field('tracker', 'parent', '', array('id' => $tracker->id));
    $tracker->parent = '';
}

if ($action == 'setinactive') {

    // Set a used element inactive for form ****************************************************.

    $usedid = required_param('usedid', PARAM_INT);
    $select = " elementid = ? AND trackerid = ? ";
    $params = array($usedid, $tracker->id);
    $DB->set_field_select('tracker_elementused', 'active', 0, $select, $params);
}

if ($action == 'setactive') {

    // Set a used element active for form ************************************************************.

    $usedid = required_param('usedid', PARAM_INT);
    $select = " elementid = ? AND trackerid = ? ";
    $params = array($usedid, $tracker->id);
    $DB->set_field_select('tracker_elementused', 'active', 1, $select, $params);
}

if ($action == 'setnotmandatory') {

    // Set a used element not mandatory for form ************************************************************.

    $usedid = required_param('usedid', PARAM_INT);
    $select = " elementid = ? AND trackerid = ? ";
    $params = array($usedid, $tracker->id);
    $DB->set_field_select('tracker_elementused', 'mandatory', 0, $select, $params);
}

if ($action == 'setmandatory') {

    // Set a used element mandatory for form ***********************************************************.

    $usedid = required_param('usedid', PARAM_INT);
    $params = array($usedid, $tracker->id);
    $DB->set_field_select('tracker_elementused', 'mandatory', 1, " elementid = ? AND trackerid = ? ", $params);
    $DB->set_field_select('tracker_elementused', 'active', 1, " elementid = ? AND trackerid = ? ", $params);
    $DB->set_field_select('tracker_elementused', 'private', 0, " elementid = ? AND trackerid = ? ", $params);
}

if ($action == 'setpublic') {

    // Set a used element public for form *********************************************************************.

    $usedid = required_param('usedid', PARAM_INT);
    $params = array($usedid, $tracker->id);
    $DB->set_field_select('tracker_elementused', 'private', 0, " elementid = ? AND trackerid = ? ", $params);
}

if ($action == 'setprivate') {

    // Set a used element private for form *************************************************************.

    $usedid = required_param('usedid', PARAM_INT);
    $params = array($usedid, $tracker->id);
    $DB->set_field_select('tracker_elementused', 'private', 1, " elementid = ? AND trackerid = ? ", $params);
}
