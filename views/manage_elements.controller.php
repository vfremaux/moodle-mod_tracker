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
* @package  mod-tracker
* @category mod
* @author   Valery Fremaux > 1.8
*
* Controller for all "element management" related views
*
* @usecase createelement
* @usecase doaddelement
* @usecase editelement
* @usecase doupdateelement
* @usecase deleteelement
* @usecase submitelementoption
* @usecase viewelementoptions
* @usecase deleteelementoption
* @usecase editelementoption
* @usecase updateelementoption
* @usecase moveelementoptionup
* @usecase moveelementoptiondown
* @usecase addelement
* @usecase removeelement
*/

defined('MOODLE_INTERNAL') || die();

if ($action == 'createelement') {

    // Create element form ********************************************************************.

    $form->type = required_param('type', PARAM_ALPHA);
    $form->action = 'doaddelement';
    echo $renderer->edit_element($cm, $form);
    return -1;

} else if ($action == 'doaddelement') {

    // Add an element **********************************************************************.

    $form->name = required_param('name', PARAM_ALPHANUM);
    $form->name = preg_replace('/\s+|-|\\\'|\"/', '', $form->name); // Remove all spaces.
    $form->description = required_param('description', PARAM_CLEANHTML);
    $form->type = required_param('type', PARAM_ALPHA);
    $form->shared = optional_param('shared', 0, PARAM_INT);
    $errors = array();
    if (empty($form->name)) {
        $error->message = get_string('namecannotbeblank', 'tracker');
        $error->on = 'name';
        $errors[] = $error;
    }

    if (!count($errors)) {
        $element->name = $form->name;
        $element->description = str_replace("'", "''", $form->description);
        $form->type = $element->type = $form->type;
        $element->course = ($form->shared) ? 0 : $COURSE->id;
        if (!$form->elementid = $DB->insert_record('tracker_element', $element)) {
            print_error('errorcannotcreateelement', 'tracker');
        }

        $elementobj = tracker_getelement(null, $form->type);
        if ($elementobj->hasoptions()) {
            // Bounces to the option editor.
            $form->name = '';
            $form->description = '';
            $action = 'viewelementoptions';
        }
    } else {
        $form->name = '';
        $form->description = '';
        echo $renderer->edit_element($cm, $form);
    }

} else if ($action == 'editelement') {

    // Edit an element form ***********************************************************.

    $form->elementid = required_param('elementid', PARAM_INT);
    if ($form->elementid != null) {
        $element = tracker_getelement($form->elementid);
        $form->type = $element->type;
        $form->name = $element->name;
        $form->description = $element->description;
        $form->format = $element->format;
        $form->shared = ($element->course == 0) ;
        $form->action = 'doupdateelement';
        include($CFG->dirroot.'/mod/classes/trackercategorytype/editelement.html');
    } else {
        print_error('errorinvalidelementid', 'tracker');
    }
    return -1;
}

if ($action == 'doupdateelement') {

    // Update an element ***********************************************************************.

    $form->elementid = required_param('elementid', PARAM_INT);
    $form->name = required_param('name', PARAM_ALPHANUM);
    $form->name = preg_replace('/\s+|-|\\\'|\"/', '', $form->name); // Remove all spaces.
    $form->description = required_param('description', PARAM_CLEANHTML);
    $form->format = optional_param('format', '', PARAM_INT);
    $form->type = required_param('type', PARAM_ALPHA);
    $form->shared = optional_param('shared', 0, PARAM_INT);

    if (empty($form->elementid)) {
        print_error('errorelementdoesnotexist', 'tracker');
    }

    $errors = array();
    if (empty($form->name)) {
        $error->message = get_string('namecannotbeblank', 'tracker');
        $error->on = "name";
        $errors[] = $error;
    }

    if (!count($errors)) {
        $element->id = $form->elementid;
        $element->name = $form->name;
        $element->type = $form->type;
        $element->description = $form->description;
        $element->format = $form->format;
        $element->course = ($form->shared) ? 0 : $COURSE->id;
        $DB->update_record('tracker_element', $element);
    } else {
        $form->action = 'doupdateelement';
        echo $renderer->edit_element($cm, $form);
    }
}

if ($action == 'deleteelement') {

    // Delete an element from available ******************************************************.

    $elementid = required_param('elementid', PARAM_INT);
    if (!tracker_iselementused($tracker->id, $elementid)) {
        $DB->delete_records ('tracker_element', 'id', $elementid);
        $DB->delete_records('tracker_elementitem', array('elementid' => $elementid));
    }
}

if ($action == 'submitelementoption') {

    // Add an element option ******************************************************************.

    $form->elementid = required_param('elementid', PARAM_INT);
    $form->name = required_param('name', PARAM_ALPHANUM);
    $form->description = required_param('description', PARAM_CLEANHTML);
    $form->type = required_param('type', PARAM_ALPHA);
    $element = $DB->get_record('tracker_element', array('id' => $form->elementid));

    // Check validity.
    $errors = array();
    if ($DB->count_records('tracker_elementitem', array('elementid' => $form->elementid, 'name' => $form->name))) {
        $error->message = get_string('optionisused', 'tracker');
        $error->on = 'name';
        $errors[] = $error;
    }

    if ($form->name == '') {
        unset($error);
        $error->message = get_string('optionnamecannotbeblank', 'tracker');
        $error->on = 'name';
        $errors[] = $error;
    }

    if ($form->description == '') {
        unset($error);
        $error->message = get_string('descriptionisempty', 'tracker');
        $error->on = 'description';
        $errors[] = $error;
    }

    if (!count($errors)) {
        $option->name = strtolower($form->name);
        $option->description = str_replace("'", "''", $form->description);
        $option->elementid = $form->elementid;
        $countoptions = 0 + $DB->count_records('tracker_elementitem', array('elementid' => $form->elementid));
        $option->sortorder = $countoptions + 1;
        $DB->insert_record('tracker_elementitem', $option);
        $form->name = '';
        $form->description = '';
    } else {
        // Print errors.
        $errorstr = '';
        foreach ($errors as $anError) {
            $errorstrs[] = $anError->message;
        }
        echo $OUTPUT->box(implode('<br/>', $errorstrs), '', 'errorbox');
    }
    echo $OUTPUT->heading(get_string('editoptions', 'tracker'));
    $element = tracker_getelement($form->elementid);
    $element->optionlistview($cm);
    $caption = get_string('addanoption', 'tracker');
    echo $OUTPUT->heading($caption . $OUTPUT->help_icon('options', 'tracker', false));
    include($CFG->dirroot.'/mod/tracker/classes/trackercategorytype/editoptionform.html');
    return -1;
}

if ($action == 'viewelementoptions') {

    // Edit an element option *******************************************************************.

    $form->elementid = optional_param('elementid', @$form->elementid, PARAM_INT);
    if ($form->elementid != null) {
        $element = tracker_getelement($form->elementid);
        $form->type = $element->type;
        echo $OUTPUT->heading(get_string('editoptions', 'tracker'));
        $element = tracker_getelement($form->elementid);
        $element->optionlistview($cm);
        echo $OUTPUT->heading(get_string('addanoption', 'tracker'));
        include($CFG->dirroot.'/mod/tracker/classes/trackercategorytype/editoptionform.html');
    } else {
        error("Cannot view element options for elementid:" . $form->elementid);
    }
    return -1;
}

if ($action == 'deleteelementoption') {

    // Delete an element option ****************************************************************.

    $form->elementid = optional_param('elementid', null, PARAM_INT);
    $form->optionid = required_param('optionid', PARAM_INT);
    $element = tracker_getelement($form->elementid);
    $deletedoption = $element->getoption($form->optionid);
    $form->type = $element->type;

    if ($DB->get_records('tracker_issueattribute', array('elementitemid' => $form->optionid))) {
        error ('Cannot delete the element option:"' . $element->options[$form->optionid]->name . '" (id:' . $form->optionid . ') because it is currently being used as a attribute for an issue', "view.php?id={$cm->id}&amp;what=viewelementoptions&amp;elementid=" . $form->elementid);
    }
    $DB->delete_records('tracker_elementitem', array('id' => $form->optionid));

    // Renumber higher records.
    $sql = "
        UPDATE
            {tracker_elementitem}
        SET
            sortorder = sortorder - 1
        WHERE
            elementid = $form->elementid AND
            sortorder > $deletedoption->sortorder;
    ";
    $DB->execute($sql);
    echo $OUTPUT->heading(get_string('editoptions', 'tracker'));
    $element = tracker_getelement($form->elementid);
    $element->optionlistview($cm);
    $caption = get_string('addanoption', 'tracker');
    echo $OUTPUT->heading($caption . $OUTPUT->help_icon('options', 'tracker', false));
    include($CFG->dirroot.'/mod/tracker/classes/trackercategorytype/editoptionform.html');
    return -1;
}

if ($action == 'editelementoption') {

    // Edit an element option ************************************************************************.

    $form->elementid = required_param('elementid', PARAM_INT);
    $form->optionid = required_param('optionid', PARAM_INT);
    $element = tracker_getelement($form->elementid);
    $option = $element->getoption($form->optionid);
    $form->type = $element->type;
    $form->name = $option->name;
    $form->description = $option->description;
    include($CFG->dirroot.'/mod/tracker/classes/trackercategorytype/updateoptionform.html');
    return -1;
}

if ($action == 'updateelementoption') {

    // Edit an element option ***********************************************************************.

    $form->elementid = required_param('elementid', PARAM_INT);
    $form->optionid = required_param('optionid', PARAM_INT);
    $form->name = required_param('name', PARAM_ALPHANUM);
    $form->description = required_param('description', PARAM_CLEANHTML);
    $form->format = optional_param('format', 0, PARAM_INT);

    $element = tracker_getelement($form->elementid);
    $form->type = $element->type;

    // Check validity.
    $errors = array();
    $select = "elementid = ? AND name = ? AND id != ? ";

    if ($DB->count_records_select('tracker_elementitem', $select, array($form->elementid, $form->name, $form->optionid))) {
        $error->message = get_string('optionisused', 'tracker');
        $error->on = 'name';
        $errors[] = $error;
    }

    if ($form->name == '') {
        unset($error);
        $error->message = get_string('optionnamecannotbeblank', 'tracker');
        $error->on = 'name';
        $errors[] = $error;
    }

    if ($form->description == '') {
        unset($error);
        $error->message = get_string('descriptionisempty', 'tracker');
        $error->on = 'description';
        $errors[] = $error;
    }

    if (!count($errors)) {
        $update->id = $form->optionid;
        $update->name = $form->name;
        $update->description = $form->description;
        $update->format = $form->format;
        if ($DB->update_record('tracker_elementitem', $update)) {
            echo $OUTPUT->heading(get_string('editoptions', 'tracker'));
            $element = tracker_getelement($form->elementid);
            $element->optionlistview($cm);
            echo $OUTPUT->heading(get_string('addanoption', 'tracker'));
            include($CFG->dirroot.'/mod/tracker/classes/trackercategorytype/editoptionform.html');
        } else {
            error ('Cannot update the element option:"' . $element->options[$form->optionid]->name . '" (id:' . $form->optionid . ') because it is currently being used as a attribute for an issue', 'view.php?id={$cm->id}&amp;what=viewelementoptions&amp;elementid=' . $form->elementid);
        }
    } else {
        // print errors
        $errorstr = '';
        foreach ($errors as $anError) {
            $errorstrs[] = $anError->message;
        }
        echo $OUTPUT->box(implode("<br/>", $errorstrs), 'center', '70%', '', 5, 'errorbox');
        include($CFG->dirroot.'/mod/tracker/classes/trackercategorytype/updateoptionform.html');
    }
    return -1;
}

if ($action == 'moveelementoptionup') {

    // Move an option up in list ***************************************************************.

    $form->elementid = required_param('elementid', PARAM_INT);
    $form->optionid = required_param('optionid', PARAM_INT);

    $params = array('elementid' => $form->elementid, 'id' => $form->optionid);
    $option = $DB->get_record('tracker_elementitem', $params);
    $element = tracker_getelement($form->elementid);
    $form->type = $element->type;
    $option->id = $form->optionid;
    $params = array('elementid' => $form->elementid, 'id' => $form->optionid);
    $sortorder = $DB->get_field('tracker_elementitem', 'sortorder', $params);
    if ($sortorder > 1) {
        $option->sortorder = $sortorder - 1;
        $params = array('elementid' => $form->elementid, 'sortorder' => $sortorder - 1);
        $previousoption->id = $DB->get_field('tracker_elementitem', 'id', $params);
        $previousoption->sortorder = $sortorder;

        // Swap options in database.
        $DB->update_record('tracker_elementitem', $option);
        $DB->update_record('tracker_elementitem', $previousoption);
    }
    echo $OUTPUT->heading(get_string('editoptions', 'tracker'));
    $element = tracker_getelement($form->elementid);
    $element->optionlistview($cm);
    $caption = get_string('addanoption', 'tracker');
    echo $OUTPUT->heading($caption . $OUTPUT->help_icon('options', 'tracker', false));
    include($CFG->dirroot.'/mod/tracker/classes/trackercategorytype/editoptionform.html');
    return -1;
}

if ($action == 'moveelementoptiondown') {

    // Move an option down in list ****************************************************************.

    $form->elementid = required_param('elementid', PARAM_INT);
    $form->optionid = required_param('optionid', PARAM_INT);

    $option = $DB->get_record('tracker_elementitem', array('elementid' => $form->elementid, 'id' => $form->optionid));
    $element = tracker_getelement($form->elementid);
    $form->type = $element->type;
    $option->id = $form->optionid;
    $sortorder = $DB->get_field('tracker_elementitem', 'sortorder', array('elementid' => $form->elementid, 'id' => $form->optionid));
    if ($sortorder < $element->maxorder) {
        $option->sortorder = $sortorder + 1;
        $nextoption->id = $DB->get_field('tracker_elementitem', 'id', array('elementid' => $form->elementid, 'sortorder' => $sortorder + 1));
        $nextoption->sortorder = $sortorder;
        // swap options in database
        $DB->update_record('tracker_elementitem', $option);
        $DB->update_record('tracker_elementitem', $nextoption);
    }
    echo $OUTPUT->heading(get_string('editoptions', 'tracker'));
    $element = tracker_getelement($form->elementid);
    $element->optionlistview($cm);
    $caption = get_string('addanoption', 'tracker');
    echo $OUTPUT->heading($caption . $OUTPUT->help_icon('options', 'tracker', false));
    include($CFG->dirroot.'/mod/tracker/classes/trackercategorytype/editoptionform.html');
    return -1;
}

if ($action == 'addelement') {

    // Add an element to be used ***************************************************************************.

    $elementid = required_param('elementid', PARAM_INT);

    if (!tracker_iselementused($tracker->id, $elementid)) {
        // Add element to element used table.
        $used->elementid = $elementid;
        $used->trackerid = $tracker->id;
        $used->canbemodifiedby = $USER->id;
        // Get last sort order.
        $select = "trackerid = ? GROUP BY trackerid";
        $sortorder = 0 + $DB->get_field_select('tracker_elementused', 'MAX(sortorder)', $select, array($tracker->id));
        $used->sortorder = $sortorder + 1;
        $DB->insert_record('tracker_elementused', $used);
    } else {
        // Feedback message that element is already in use.
        $returnurl = new moodle_url('/mod/tracker/view.php', array('id' => $cm->id, 'what' => 'manageelements'));
        print_error('errorelementinuse', 'tracker', '', $returnurl);
    }
}

if ($action == 'removeelement') {

    // Remove an element from usable list *****************************************************************.

    $usedid = required_param('usedid', PARAM_INT);
    $DB->delete_records('tracker_elementused', 'elementid', $usedid, 'trackerid', $tracker->id);
}
