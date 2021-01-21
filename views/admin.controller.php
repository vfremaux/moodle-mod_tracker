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
 * Controller for all "element management" related views. Basic commands.
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
 * @usecase 'setinactive'
 * @usecase 'setactive'
 */
namespace mod_tracker;

use StdClass;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/tracker/classes/controller.class.php');

class admin_controller extends base_controller {

    public function receive($cmd, $data = null) {

        if (parent::receive($cmd, $data)) {
            return true;
        }

        switch ($cmd) {
            case 'createelement': {
                $this->data->type = required_param('type', PARAM_TEXT);
                break;
            }

            case 'editelement': {
                $this->data->elementid = required_param('elementid', PARAM_INT);
                $this->data->type = required_param('type', PARAM_TEXT);
                break;
            }

            case 'doupdateelement': {
                $this->data->elementid = required_param('elementid', PARAM_INT);
                $this->data->name = required_param('name', PARAM_ALPHANUM);
                $this->data->description = required_param('description', PARAM_CLEANHTML);
                $this->data->format = optional_param('format', '', PARAM_INT);
                $this->data->type = required_param('type', PARAM_ALPHA);
                $this->data->shared = optional_param('shared', 0, PARAM_INT);
                break;
            }

            case 'deletelement': {
                $this->data->elementid = required_param('elementid', PARAM_INT);
                break;
            }

            case 'submitelementoption': {
                $this->data->elementid = required_param('elementid', PARAM_INT);
                $this->data->name = required_param('name', PARAM_ALPHANUM);
                $this->data->description = required_param('description', PARAM_CLEANHTML);
                $this->data->type = required_param('type', PARAM_ALPHA);
                break;
            }

            case 'viewelementoptions': {
                $this->data->elementid = optional_param('elementid', @$bounceelementid, PARAM_INT);
                break;
            }

            case 'deleteelementoption':
            case 'editelementoption': {
                $this->data->elementid = optional_param('elementid', null, PARAM_INT);
                $this->data->optionid = required_param('optionid', PARAM_INT);
                break;
            }

            case 'updatelementoption': {
                $this->data->elementid = required_param('elementid', PARAM_INT);
                $this->data->optionid = required_param('optionid', PARAM_INT);
                $this->data->name = required_param('name', PARAM_ALPHANUM);
                $this->data->description = required_param('description', PARAM_CLEANHTML);
                $this->data->format = optional_param('format', 0, PARAM_INT);
                break;
            }

            case 'moveelementoptionup' :
            case 'moveelementoptiondown': {
                $this->data->elementid = required_param('elementid', PARAM_INT);
                $this->data->optionid = required_param('optionid', PARAM_INT);
                break;
            }

            case 'addelement': {
                $this->data->elementid = required_param('elementid', PARAM_INT);
                break;
            }

            case 'removeelement': {
                $this->data->usedid = required_param('usedid', PARAM_INT);
                break;
            }

            case 'raiseelement':
            case 'lowerelement': {
                $this->data->usedid = required_param('elementid', PARAM_INT);
                break;
            }

            case 'setinactive':
            case 'setactive': {
                $this->data->usedid = required_param('usedid', PARAM_INT);
                break;
            }
        }

        $this->received = true;
    }

    public function process($cmd) {
        global $DB, $COURSE, $PAGE, $OUTPUT, $USER;

        $result = parent::process($cmd);

        $config = get_config('mod_tracker');

        $renderer = $PAGE->get_renderer('mod_tracker');

        if ($cmd == 'createelement') {
            // Create element form *************************************************************************.
            $params = array('id' => $this->cm->id, 'type' => $this->data->type, 'elementid' => 0);
            $this->done = true;
            return new moodle_url('/mod/tracker/editelement.php', $params);

        } else if ($cmd == 'editelement') {
            // Edit an element form *************************************************************************.
            $params = array('id' => $this->cm->id, 'type' => $this->data->type, 'elementid' => $this->data->elementid);
            $this->done = true;
            return new moodle_url('/mod/tracker/editelement.php', $params);
        }

        if ($cmd == 'doupdateelement') {
            // Update an element ****************************************************************************.
            $this->out = '';
            if (empty($form->elementid)) {
                print_error('errorelementdoesnotexist', 'tracker', $this->url);
            }
            $errors = array();
            if (empty($this->data->name)) {
                $error = new StdClass;
                $error->message = get_string('namecannotbeblank', 'tracker');
                $error->on = 'name';
                $errors[] = $error;
            }

            if (!count($errors)) {
                $this->data->course = ($this->data->shared) ? 0 : $COURSE->id;
                $DB->update_record('tracker_element', $this->data);
            } else {
                $this->data->action = 'doupdateelement';
                ob_start();
                $this->out = $renderer->edit_element($this->cm, $this->data);
                $this->out .= ob_get_clean();
            }
            $this->done = true;
        }

        if ($cmd == 'deleteelement') {
            // Delete an element from available ***************************************************.
            if (!tracker_iselementused($this->tracker->id, $this->data->elementid)) {
                $DB->delete_records ('tracker_element', array('id' => $this->data->elementid));
                $DB->delete_records('tracker_elementitem', array('elementid' => $this->data->elementid));
                $this->done = true;
            } else {
                // Should not even be proposed by the GUI.
                print_error('errorcannotdeleteelement', 'tracker', $this->url);
            }
        }

        if ($cmd == 'submitelementoption') {

            // Add an element option ********************************************************************.
            $element = $DB->get_record('tracker_element', array('id' => $this->data->elementid));
            // Check validity.
            $errors = array();

            $params = array('elementid' => $this->data->elementid, 'name' => $this->data->name);
            if ($DB->count_records('tracker_elementitem', $params)) {
                $error = new StdClass;
                $error->message = get_string('optionisused', 'tracker', $this->url);
                $error->on = 'name';
                $errors[] = $error;
            }

            if ($this->data->name == '') {
                unset($error);
                $error = new StdClass;
                $error->message = get_string('optionnamecannotbeblank', 'tracker');
                $error->on = 'name';
                $errors[] = $error;
            }

            if ($this->data->description == '') {
                unset($error);
                $error = new StdClass;
                $error->message = get_string('descriptionisempty', 'tracker');
                $error->on = 'description';
                $errors[] = $error;
            }

            if (!count($errors)) {
                $countoptions = 0 + $DB->count_records('tracker_elementitem', array('elementid' => $this->data->elementid));
                $this->data->sortorder = $countoptions + 1;
                $DB->insert_record('tracker_elementitem', $this->data);
                $this->data->name = '';
                $this->data->description = '';
            } else {
                // Print errors.
                $errorstr = '';
                foreach ($errors as $error) {
                    $errorstrs[] = $error->message;
                }
                $out .= $OUTPUT->box(implode('<br/>', $errorstrs), 'center', '70%', '', 5, 'errorbox');
            }
            $this->out .= $OUTPUT->heading(get_string('editoptions', 'tracker'));
            $element = trackerelement::find_instance_by_id($this->tracker, $this->data->elementid);
            $this->out .= $renderer->option_list_view($this->cm, $element);
            $caption = get_string('addanoption', 'tracker');
            $this->out .= $OUTPUT->heading($caption . $OUTPUT->help_icon('options', 'tracker', false));

            $this->out .= $renderer->edit_option_form($this->cm, $this->data, 'submit', @$errors);
            $this->done = true;
        }

        if ($cmd == 'viewelementoptions') {
            // Edit an element option **********************************************************************.
            if ($this->data->elementid) {
                $element = trackerelement::find_instance_by_id($this->tracker, $this->data->elementid);
                $this->data->type = $element->type;
                $this->out = $OUTPUT->heading(get_string('editoptions', 'tracker'));
                $this->out .= '<center>';
                $element = trackerelement::find_instance_by_id($this->tracker, $this->data->elementid);
                $this->out .= $renderer->option_list_view($this->cm, $element);
                $this->out .= $OUTPUT->heading(get_string('addanoption', 'tracker'));
                $this->out .= $renderer->edit_option_form($this->cm, $this->data, 'submit', @$errors);
                $this->out .= '</center>';

                $this->done = true;
            } else {
                print_error('errorcannotviewelementoption', 'tracker', $this->url);
            }
            return -1;
        }

        if ($cmd == 'deleteelementoption') {

            // Delete an element option *********************************************************************.

            $element = trackerelement::find_instance_by_id($this->tracker, $this->data->elementid);
            $deletedoption = $element->get_option($this->data->optionid);
            $this->data->type = $element->type;

            if ($DB->get_records('tracker_issueattribute', array('elementitemid' => $this->data->optionid))) {
                // Cannot delete option as used.
                print_error('errorcannotdeleteoption', 'tracker', $this->url);
            }

            $list = new datalist('tracker_elementitem', 'id', 'sortorder', ['elementid' => $this->data->elementid]);
            $list->remove($this->data->optionid);

            $this->out = $OUTPUT->heading(get_string('editoptions', 'tracker'));
            $element = trackerelement::find_instance_by_id($this->tracker, $this->data->elementid);
            $this->out .= $renderer->option_list_view($this->cm, $element);
            $caption = get_string('addanoption', 'tracker');
            $this->out .= $OUTPUT->heading($caption . $OUTPUT->help_icon('options', 'tracker', false));

            $this->out .= $renderer->edit_option_form($this->cm, $this->data, @$errors);
            $this->done = true;
        }

        if ($cmd == 'editelementoption') {
            // Edit an element option *******************************************************.
            $element = trackerelement::find_instance_by_id($this->tracker, $this->data->elementid);
            $option = $element->get_option($this->data->optionid);
            $this->data->type = $element->type;
            $this->data->name = $option->name;
            $this->data->description = $option->description;
            $this->out .= $renderer->edit_option_form($this->cm, $this->data, 'update', @$errors);
            $this->done = true;
            return -1;
        }

        if ($cmd == 'updateelementoption') {
            // Edit an element option *****************************.
            $element = trackerelement::find_instance_by_id($this->tracker, $this->data->elementid);
            $this->data->type = $element->type;

            // Check validity.
            $errors = array();
            $select = " elementid = ? AND name = ? AND id != ? ";
            $params = [$this->data->elementid, $this->data->name, $this->data->optionid];
            if ($DB->count_records_select('tracker_elementitem', $select, $params)) {
                $error = new StdClass;
                $error->message = get_string('optionisused', 'tracker');
                $error->on = 'name';
                $errors[] = $error;
            }

            if ($this->data->name == '') {
                unset($error);
                $error = new StdClass;
                $error->message = get_string('optionnamecannotbeblank', 'tracker');
                $error->on = 'name';
                $errors[] = $error;
            }

            if ($this->data->description == '') {
                unset($error);
                $error = new StdClass;
                $error->message = get_string('descriptionisempty', 'tracker');
                $error->on = 'description';
                $errors[] = $error;
            }

            if (!count($errors)) {
                $DB->update_record('tracker_elementitem', $this->data);
                $this->out = $OUTPUT->heading(get_string('editoptions', 'tracker'));
                $element = trackerelement::find_instance_by_id($this->tracker, $this->data->elementid);
                $renderer = $PAGE->get_renderer('mod_tracker');
                $this->out .= $renderer->option_list_view($this->cm, $element);
                $this->out .= $OUTPUT->heading(get_string('addanoption', 'tracker'));

                $this->out .= $renderer->edit_option_form($this->cm, $this->data, 'submit', @$errors);

            } else {
                // Print errors.
                $errorstr = '';
                foreach ($errors as $error) {
                    $errorstrs[] = $error->message;
                }
                $this->out .= $OUTPUT->box(implode("<br/>", $errorstrs), 'center', '70%', '', 5, 'errorbox');

                $this->out .= $renderer->edit_option_form($this->cm, $this->data, 'update', @$errors);
            }
            $this->done = true;
        }

        if ($cmd == 'moveelementoptionup') {
            // Move an option up in list ******************************************************.
            $list = new datalist('tracker_elementitem', 'id', 'sortorder', ['elementid' => $this->data->elementid]);
            $list->up($this->data->optionid);

            $this->out = $OUTPUT->heading(get_string('editoptions', 'tracker'));
            $element = trackerelement::find_instance_by_id($this->tracker, $this->data->elementid);
            $this->out .= $renderer->option_list_view($this->cm, $element);
            $caption = get_string('addanoption', 'tracker');
            $this->out .= $OUTPUT->heading($caption . $OUTPUT->help_icon('options', 'tracker', false));

            $this->out .= $renderer->edit_option_form($this->cm, $this->data, 'submit', @$errors);
            $this->done = true;
        }

        if ($cmd == 'moveelementoptiondown') {
            // Move an option down in list *************************************************.
            $list = new datalist('tracker_elementitem', 'id', 'sortorder', ['elementid' => $this->data->elementid]);
            $list->down($this->data->optionid);

            $this->out = $OUTPUT->heading(get_string('editoptions', 'tracker'));
            $element = trackerelement::find_instance_by_id($this->tracker, $this->data->elementid);
            $this->out .= $renderer->option_list_view($this->cm, $element);
            $caption = get_string('addanoption', 'tracker');
            $this->out .= $OUTPUT->heading($caption . $OUTPUT->help_icon('options', 'tracker', false));

            $this->out .= $renderer->edit_option_form($this->cm, $this->data, 'submit', @$errors);
            $this->done = true;
        }

        if ($cmd == 'addelement') {
            // Add an element to be used **************************************************.
            if (!tracker_iselementused($this->tracker->id, $this->data->elementid)) {

                // Add element to element used table.
                $used = new StdClass;
                $used->elementid = $this->data->elementid;
                $used->trackerid = $this->tracker->id;
                $used->canbemodifiedby = $USER->id;
                $used->mandatory = $config->initiallymandatory;
                $used->private = $config->initiallyprivate;
                $used->active = $config->initiallyactive;

                // Get last sort order.
                $select = "trackerid = ? GROUP BY trackerid";
                $params = array($this->tracker->id);
                $sortorder = 0 + $DB->get_field_select('tracker_elementused', 'MAX(sortorder)', $select, $params);
                $used->sortorder = $sortorder + 1;
                $DB->insert_record ('tracker_elementused', $used);
                $this->done = true;
            } else {
                // Feedback message that element is already in uses.
                print_error('erroralreadyinuse', 'tracker', $this->url->out().'&view=admin');
            }

        } else if ($cmd == 'removeelement') {
            // Remove an element from usable list ******************************************************.
            $params = array('elementid' => $this->data->usedid, 'trackerid' => $this->tracker->id);
            $DB->delete_records ('tracker_elementused', $params);
            $this->done = true;

        } else if ($cmd == 'raiseelement') {
            // Raise element pos in usable list ********************************************************.
            $list = new datalist('tracker_elementused', 'elementid', 'sortorder', ['trackerid' => $this->tracker->id]);
            $list->up($this->data->usedid);
            $this->done = true;

        } else if ($cmd == 'lowerelement') {
            // Lower element pos in usable list ************************************************************.
            $list = new datalist('tracker_elementused', 'elementid', 'sortorder', ['trackerid' => $this->tracker->id]);
            $list->down($this->data->usedid);
            $this->done = true;

        } else if ($cmd == 'localparent') {
            // Update parent tracker binding *******************************************************************.
            $params = array('id' => $this->tracker->id);
            $DB->set_field('tracker', 'parent', $this->data->parent, $params);
            $this->tracker->parent = $this->data->parent;
            $this->done = true;

        } else if ($cmd == 'remoteparent') {
            // Update remote parent tracker binding **************************************************************.
            $step = optional_param('step', 0, PARAM_INT);
            switch ($step) {

                case 1 : {
                    // We choose the host.
                    break;
                }

                case 2 : {
                    $params = array('id' => $this->tracker->id);
                    $DB->set_field('tracker', 'parent', $this->data->remoteparent, $params);
                    $this->tracker->parent = $this->data->remoteparent;
                    $step = 0;
                    break;
                }
            }
            $this->done = true;

        } else if ($cmd == 'unbind') {
            // Unbinds any cascade  ****************************************************************.
            $DB->set_field('tracker', 'parent', '', array('id' => $this->tracker->id));
            $this->tracker->parent = '';
            $this->done = true;

        } else if ($cmd == 'setinactive') {
            // Set a used element inactive for form ****************************************************.
            $select = " elementid = ? AND trackerid = ? ";
            $params = array($this->data->usedid, $this->tracker->id);
            $DB->set_field_select('tracker_elementused', 'active', 0, $select, $params);
            $this->done = true;

        } else if ($cmd == 'setactive') {
            // Set a used element active for form ************************************************************.
            $select = " elementid = ? AND trackerid = ? ";
            $params = array($this->data->usedid, $this->tracker->id);
            $DB->set_field_select('tracker_elementused', 'active', 1, $select, $params);
            $this->done = true;
        }
    }
}
