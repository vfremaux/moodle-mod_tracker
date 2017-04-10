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
 * A class implementing a textarea element and all its representations
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/tracker/classes/trackercategorytype/text/text.class.php');

class textareaelement extends textelement {

    public function edit($issueid = 0) {
        $this->get_value($issueid);
        echo html_writer::start_tag('textarea', array('name' => 'element'.$this->name, 'cols' => 80, 'rows' => 15));
        echo format_string($this->value);
        echo html_writer::end_tag('textarea');
    }

    public function view_search() {
        echo '<input type="text" name="element'.$this->name.'" style="width:100%" />';
    }

    public function view_query() {
        echo '<input type="text" name="element'.$this->name.'" style="width:100%" />';
    }

    public function add_form_element(&$mform) {

        $mform->addElement('textarea', "element{$this->name}", format_string($this->description), array('cols' => 60, 'rows' => 15));
        $mform->setType("element{$this->name}", PARAM_TEXT);
        if (!empty($this->mandatory)) {
            $mform->addRule('element'.$this->name, null, 'required', null, 'client');
        }
    }

}

