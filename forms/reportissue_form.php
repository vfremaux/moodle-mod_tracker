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
 * @author Clifford Tham, Valery Fremaux > 1.8
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class TrackerIssueForm extends moodleform {

    /**
     * List of dynamic forms elements
     */
    protected $elements;

    /**
     * Options for editors
     */
    public $editoroptions;

    /**
     * context for file handling
     */
    protected $context;

    /**
     * Dynamically defines the form using elements setup in tracker instance
     */
    public function definition() {
        global $DB, $COURSE;

        $tracker = $this->_customdata['tracker'];

        $this->context = context_module::instance($this->_customdata['cmid']);
        $maxfiles = 99;                // TODO: add some setting.
        $maxbytes = $COURSE->maxbytes; // TODO: add some setting.
        $this->editoroptions = array('trusttext' => true,
                                     'subdirs' => false,
                                     'maxfiles' => $maxfiles,
                                     'maxbytes' => $maxbytes,
                                     'context' => $this->context);

        $mform = $this->_form;

        $mform->addElement('hidden', 'id'); // Course module id.
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'issueid'); // Issue id.
        $mform->setType('issueid', PARAM_INT);

        $mform->addElement('hidden', 'trackerid', $tracker->id);
        $mform->setType('trackerid', PARAM_INT);

        $mform->addElement('header', 'header0', get_string('description'));

        $mform->addElement('text', 'summary', get_string('summary', 'tracker'), array('size' => 80));
        $mform->setType('summary', PARAM_TEXT);
        $mform->addRule('summary', null, 'required', null, 'client');

        $mform->addElement('editor', 'description_editor', get_string('description'), $this->editoroptions);

        tracker_loadelementsused($tracker, $this->elements);

        if (!empty($this->elements)) {
            foreach ($this->elements as $element) {
                if ((get_class($element) == 'captchaelement') && ($this->_customdata['mode'] == 'update')) {
                    // Avoid captcha when updating issue data.
                    continue;
                }
                if (($element->active == true) && ($element->private == false)) {
                    $element->add_form_element($mform);
                }
            }
        }

        if ($this->_customdata['mode'] == 'update') {

            $mform->addelement('header', 'processinghdr', get_string('processing', 'tracker'), '');

            // Assignee.
            $context = context_module::instance($this->_customdata['cmid']);
            $resolvers = tracker_getresolvers($context);
            $resolversmenu[0] = '---- '. get_string('unassigned', 'tracker').' -----';
            if ($resolvers) {
                foreach ($resolvers as $resolver) {
                    $resolversmenu[$resolver->id] = fullname($resolver);
                }
                $mform->addElement('select', 'assignedto', get_string('assignedto', 'tracker'), $resolversmenu);
            } else {
                $mform->addElement('static', 'resolversshadow', get_string('assignedto', 'tracker'), get_string('noresolvers', 'tracker'));
                $mform->addElement('hidden', 'assignedto');
                $mform->setType('assignedto', PARAM_INT);
            }

            // Status.
            $statuskeys = tracker_get_statuskeys($tracker);
            $mform->addElement('select', 'status', get_string('status', 'tracker'), $statuskeys);

            // Dependencies.
            $dependencies = tracker_getpotentialdependancies($tracker->id, $this->_customdata['issueid']);
            if (!empty($dependencies)) {
                foreach ($dependencies as $dependency) {
                    $summary = shorten_text(format_string($dependency->summary));
                    $dependenciesmenu[$dependency->id] = "{$tracker->ticketprefix}{$dependency->id} - ".$summary;
                }
                $select = &$mform->addElement('select', 'dependencies', get_string('dependson', 'tracker'), $dependenciesmenu);
                $select->setMultiple(true);
            } else {
                $mform->addElement('static', 'dependenciesshadow', get_string('dependson', 'tracker'), get_string('nopotentialdeps', 'tracker'));
            }

            $mform->addelement('header', 'resolutionhdr', get_string('resolution', 'tracker'), '');
            $mform->addElement('editor', 'resolution_editor', get_string('resolution', 'tracker'), $this->editoroptions);

        }

        $this->add_action_buttons();
    }

    public function set_data($defaults) {

        $defaults->description_editor['text'] = @$defaults->description;
        $defaults->description_editor['format'] = @$defaults->descriptionformat;
        $defaults = file_prepare_standard_editor($defaults, 'description', $this->editoroptions, $this->context, 'mod_tracker',
                                                 'issuedescription', @$defaults->issueid);

        // Something to prepare for each element ?
        if (!empty($this->elements)) {
            foreach ($this->elements as $element) {
                $element->set_data($defaults, @$this->_customdata['issueid']);
            }
        }

        $defaults->resolution_editor['text'] = @$defaults->resolution;
        $defaults->resolution_editor['format'] = @$defaults->resolutionformat;
        $defaults = file_prepare_standard_editor($defaults, 'resolution', $this->editoroptions, $this->context, 'mod_tracker',
                                                 'issueresolution', @$defaults->issueid);

        parent::set_data($defaults);
    }

    public function validate($data, $files = array()) {

        $errors = array();

        // Something to prepare for each element ?
        if (!empty($this->elements)) {
            foreach ($this->elements as $element) {
                $errors = array_merge($errors, $element->validate($data, $files));
            }
        }

        return $errors;
    }
}