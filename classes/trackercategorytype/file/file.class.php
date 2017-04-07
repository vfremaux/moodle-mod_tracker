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
 * @package tracker
 * @author Clifford Tham
 * @review Valery Fremaux / 1.8
 *
 * A class implementing a filepicker element
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/tracker/classes/trackercategorytype/trackerelement.class.php');

class fileelement extends trackerelement {

    public $filemanageroptions;

    public function __construct($tracker, $id = null, $used = false) {
        global $COURSE;

        parent::__construct($tracker, $id, $used);
        $this->filemanageroptions = array('subdirs' => 0,
                                          'maxfiles' => 1,
                                          'maxbytes' => $COURSE->maxbytes,
                                          'accepted_types' => array('*'));
    }

    /*
     * No care of real value of element.
     * There is some file stored into the file area or not.
     */
    public function view($issueid = 0) {
        global $CFG, $DB;

        $attribute = $DB->get_record('tracker_issueattribute', array('issueid' => $issueid, 'elementid' => $this->id));

        if ($attribute) {
            $fs = get_file_storage();

            $imagefiles = $fs->get_area_files($this->context->id, 'mod_tracker', 'issueattribute', $attribute->id);

            if (empty($imagefiles)) {
                $html = html_writer::start_tag('span', array('class' => 'tracker-file-item-notice'));
                $html .= get_string('nofileloaded', 'tracker');
                $html .= html_writer::end_tag('span');
                return $html;
            }

            $imagefile = array_pop($imagefiles);
            $filename = $imagefile->get_filename();
            $filearea = $imagefile->get_filearea();
            $itemid = $imagefile->get_itemid();

            $fileurl = $CFG->wwwroot."/pluginfile.php/{$this->context->id}/mod_tracker/{$filearea}/{$itemid}/{$filename}";

            if (preg_match("/\.(jpg|gif|png|jpeg)$/i", $filename)) {
                return "<img style=\"max-width:600px\" src=\"{$fileurl}\" class=\"tracker_image_attachment\" />";
            } else {
                return html_writer::link($fileurl, $filename);
            }
        } else {
            $html = html_writer::start_tag('span', array('class' => 'tracker-file-item-notice'));
            $html .= get_string('nofileloaded', 'tracker');
            $html .= html_writer::end_tag('span');
            return $html;
        }
    }

    public function edit($issueid = 0) {
        global $OUTPUT, $DB, $PAGE;

        if ($attribute = $DB->get_record('tracker_issueattribute', array('elementid' => $this->id, 'issueid' => $issueid))) {
            $itemid = $attribute->id;
        } else {
            $itemid = 0;
        }

        $draftitemid = 0; // Drafitemid will be filled when preparing new area.
        file_prepare_draft_area($draftitemid, $this->context->id, 'mod_tracker', 'issueattribute',
                                $itemid, $this->filemanageroptions);

        $options = new StdClass();
        $options->accepted_types = $this->filemanageroptions['accepted_types'];
        $options->itemid = $draftitemid;
        $options->maxbytes = $this->filemanageroptions['maxbytes'];
        $options->maxfiles = $this->filemanageroptions['maxfiles'];
        $options->elementname = 'element'.$this->name;

        $fp = new file_picker($options);

        $html = $OUTPUT->render($fp);
        $html .= '<input type="hidden"
                         name="element'.$this->name.'"
                         id="id_'.$this->name.'"
                         value="'.$draftitemid.'"
                         class="filepickerhidden"/>';

        $module = array('name' => 'form_filepicker',
                        'fullpath' => '/lib/form/filepicker.js',
                        'requires' => array('core_filepicker', 'node', 'node-event-simulate', 'core_dndupload'));
        $PAGE->requires->js_init_call('M.form_filepicker.init', array($fp->options), true, $module);

        $nonjsfilepicker = new moodle_url('/repository/draftfiles_manager.php', array(
            'env' => 'filepicker',
            'action' => 'browse',
            'itemid' => $draftitemid,
            'subdirs' => 0,
            'maxbytes' => $this->filemanageroptions['maxbytes'],
            'maxfiles' => 1,
            'ctx_id' => $this->context->id,
            'course' => $PAGE->course->id,
            'sesskey' => sesskey(),
            ));

        // Non js file picker.
        $html .= '<noscript>';
        $html .= '<div>';
        $html .= '<object type="text/html" data="'.$nonjsfilepicker.'" class="tracker-filepicker"></object></div>';
        $html .= '</noscript>';

        echo $html;
    }

    public function add_form_element(&$mform) {

        $mform->addElement('filepicker', 'element'.$this->name, format_string($this->description), null, $this->options);
        if (!empty($this->mandatory)) {
            $mform->addRule('element'.$this->name, null, 'required', null, 'client');
        }
    }

    public function set_data(&$defaults, $issueid = 0) {
        global $COURSE;

        $elmname = 'element'.$this->name;
        $draftitemid = file_get_submitted_draft_itemid($elmname);
        file_prepare_draft_area($draftitemid, $this->context->id, 'mod_tracker', 'issueattribute',
                                $this->id, $this->filemanageroptions);
        $defaults->$elmname = $draftitemid;
    }

    /**
     * used for post processing form values, or attached files management
     */
    public function form_process(&$data) {
        global $DB;

        $params = array('elementid' => $this->id, 'trackerid' => $data->trackerid, 'issueid' => $data->issueid);
        if (!$attribute = $DB->get_record('tracker_issueattribute', $params)) {
            $attribute = new StdClass();
            $attribute->trackerid = $data->trackerid;
            $attribute->issueid = $data->issueid;
            $attribute->elementid = $this->id;
        }

        $attribute->elementitemid = ''; // Value is meaningless. we just need the attribute record as storage itemid.
        $attribute->timemodified = time();

        if (!isset($attribute->id)) {
            $attribute->id = $DB->insert_record('tracker_issueattribute', $attribute);
            if (empty($attribute->id)) {
                print_error('erroraddissueattribute', 'tracker', '', 2);
            }
        } else {
            $DB->update_record('tracker_issueattribute', $attribute);
        }

        $elmname = 'element'.$this->name;
        $data->$elmname = optional_param($elmname, 0, PARAM_INT);

        if ($data->$elmname) {
            file_save_draft_area_files($data->$elmname, $this->context->id, 'mod_tracker', 'issueattribute',
                                       0 + $attribute->id, $this->filemanageroptions);
        }
    }
}

