<?php

require_once $CFG->libdir.'/formslib.php';

class AddCommentForm extends moodleform{

    var $editoroptions;

    function definition() {
        global $COURSE;

        $mform = $this->_form;

        $this->context = context_module::instance($this->_customdata['cmid']);
        $maxfiles = 99;                // TODO: add some setting
        $maxbytes = $COURSE->maxbytes; // TODO: add some setting
        $this->editoroptions = array('trusttext' => true, 'subdirs' => false, 'maxfiles' => $maxfiles, 'maxbytes' => $maxbytes, 'context' => $this->context);

        $mform->addElement('hidden', 'id', $this->_customdata['cmid']); // issue id
        $mform->addElement('hidden', 'issueid', $this->_customdata['issueid']); // issue id
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'summary', get_string('summary', 'tracker'), '', array('size' => 80));
        $mform->setType('summary', PARAM_TEXT);
        $mform->addRule('summary', null, 'required', null, 'client');

        $mform->addElement('editor', 'comment_editor', get_string('comment', 'tracker'), $this->editoroptions);

        $this->add_action_buttons(true);

    }

    function validation($data, $files = array()) {
    }

    function set_data($defaults) {

        $defaults->comment_editor['text'] = $defaults->comment;
        $defaults->comment_editor['format'] = $defaults->commentformat;
        $defaults = file_prepare_standard_editor($defaults, 'comment', $this->editoroptions, $this->context, 'mod_tracker', 'issuecomment', $defaults->id);

        parent::set_data($defaults);
    }
}
