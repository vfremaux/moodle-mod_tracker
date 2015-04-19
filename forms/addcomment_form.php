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

require_once($CFG->libdir.'/formslib.php');

class AddCommentForm extends moodleform {

    var $editoroptions;

    function definition() {
        global $COURSE;

        $mform = $this->_form;

        $this->context = context_module::instance($this->_customdata['cmid']);
        $maxfiles = 99;                // TODO: add some setting.
        $maxbytes = $COURSE->maxbytes; // TODO: add some setting.
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
