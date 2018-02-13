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
 * A class implementing a checkbox element
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/tracker/classes/trackercategorytype/checkbox/checkbox.class.php');

class checkboxhorizelement extends checkboxelement {

    protected $spacer;

    public function __construct(&$tracker, $id = null, $used = false) {
        global $OUTPUT;

        parent::__construct($tracker, $id, $used);
        $this->set_options_from_db();
        $attrs = array('src' => $OUTPUT->image_url('spacer'), 'width' => 30, 'hight' => 1);
        $this->spacer = html_writer::empty_tag('img', $attrs);

    }

    public function view($issueid = 0) {
        $str = '';

        $this->get_value($issueid); // Loads $this->value with current value for this issue.
        if (!empty($this->value)) {
            $values = explode(',', $this->value);
            $choices = array();
            foreach ($values as $selected) {
                $choices[] = format_string($this->options[$selected]->description);
            }
            $str = implode(', ', $choices);
        }
        return $str;
    }

    public function add_form_element(&$mform) {
        if (isset($this->options)) {
            $group = array();

            foreach ($this->options as $option) {
                $key = "element{$this->name}{$option->id}";
                $label = ' '.format_string($option->description);
                $group[] = &$mform->createElement('checkbox', $key, '', $label);
                $mform->setType("element{$this->name}{$option->id}", PARAM_INT);
            }

            $mform->addGroup($group, 'element' . $this->name.'_set', format_string($this->description), array(' '), false);
        }
    }
}
