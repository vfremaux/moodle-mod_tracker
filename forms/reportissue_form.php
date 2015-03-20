<?php

require_once $CFG->libdir.'/formslib.php';

class TrackerIssueForm extends moodleform{

    var $elements;
    var $editoroptions;
    var $context;

    /**
    * Dynamically defines the form using elements setup in tracker instance
    *
    *
    */
    function definition() {
        global $DB, $COURSE;

        $trackerid = $this->_customdata['trackerid'];

        $tracker = $DB->get_record('tracker', array('id' => $trackerid));

        $this->context = context_module::instance($this->_customdata['cmid']);
        $maxfiles = 99;                // TODO: add some setting
        $maxbytes = $COURSE->maxbytes; // TODO: add some setting
        $this->editoroptions = array('trusttext' => true, 'subdirs' => false, 'maxfiles' => $maxfiles, 'maxbytes' => $maxbytes, 'context' => $this->context);

        $mform = $this->_form;

        $mform->addElement('hidden', 'id', $this->_customdata['cmid']);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'trackerid', $trackerid);
        $mform->setType('trackerid', PARAM_INT);

        $mform->addElement('text', 'summary', get_string('summary', 'tracker'), array('size' => 80));
        $mform->setType('summary', PARAM_TEXT);
        $mform->addRule('summary', null, 'required', null, 'client');

        $mform->addElement('editor', 'description_editor', get_string('description'), $this->editoroptions);

        if( has_capability('mod/tracker:reportissue', $this->context) )
        {

            global $STATUSCODES;
            $choices = array();
            foreach ($STATUSCODES as $key => $value) {
                $choices[ $key ] = get_string( $value, 'tracker' );
            }

            $mform->addElement('select', 'status', get_string('status', 'tracker'), $choices);
            $mform->setDefault('status', POSTED);

            // 
            if (has_capability('mod/tracker:manage', $this->context)) { // managers can assign bugs
                // $asigners = tracker_getdevelopers($context);
                $asigners = get_users_by_capability($this->context, 'mod/tracker:develop', '', 'lastname');
            } elseif (has_capability('mod/tracker:resolve', $this->context)) { // resolvers can give a bug back to managers
                $asigners = tracker_getadministrators($this->context);
                $asigners = get_users_by_capability($this->context, 'mod/tracker:manage', '', 'lastname');
            } else {
                $asigners = array( $user->id, $user );
            }

            $asignedto = array();
            foreach ($asigners as $userid => $register) {
                $asignedto[ $userid ] = fullname( $register );
            }

            $mform->addElement('select', 'assignedto', get_string('assignedto', 'tracker'), $asignedto);
            $mform->setDefault('assignedto', $tracker->defaultassignee );
        }
        else
        {
            $mform->addElement('hidden', 'status', POSTED );
            $mform->setType('status', PARAM_INT);

            $mform->addElement('hidden', 'assignedto', $tracker->defaultassignee );
            $mform->setType('assignedto', PARAM_INT);
        }

        tracker_loadelementsused($tracker, $this->elements);

        if (!empty($this->elements) AND !is_null($this->elements)) {
            foreach ($this->elements as $element) {
                $element->add_form_element($mform);
            }
        }

        $this->add_action_buttons();
    }

    function validation($data, $files = null) {

    }

    function set_data($defaults) {
        global $COURSE;

        $defaults->description_editor['text'] = $defaults->description;
        $defaults->description_editor['format'] = $defaults->descriptionformat;
        $defaults = file_prepare_standard_editor($defaults, 'description', $this->editoroptions, $this->context, 'mod_tracker', 'issuedescription', $defaults->issueid);

        // something to prepare for each element ?
        if (!empty($this->elements)) {
            foreach ($this->elements as $element) {
                $element->set_data($defaults);
            }
        }

        parent::set_data($defaults);
    }
}