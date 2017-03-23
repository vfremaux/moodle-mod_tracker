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

require_once($CFG->dirroot.'/mod/tracker/classes/trackercategorytype/trackerelement.class.php');

class checkboxhorizelement extends trackerelement {

    public function __construct(&$tracker, $id = null, $used = false) {

        parent::__construct($tracker, $id, $used);
        $this->set_options_from_db();
    }

    public function edit($issueid = 0) {
        global $OUTPUT;

        $this->get_value($issueid);
        $values = explode(',', $this->value); // Whatever the form ... revert to an array.
        if (isset($this->options)) {
            $optionsstrs = array();
            foreach ($this->options as $option) {
                if (in_array($option->id, $values)) {
                    $attrs = array('type' => 'checkbox',
                                   'name' => 'element'.$this->name.$option->id,
                                   'value' => 1,
                                   'checked' => 'checked');
                    echo html_writer::empty_tag('input', $attrs);
                } else {
                    $attrs = array('type' => 'checkbox', 'name' => 'element'.$this->name.$option->id, 'value' => 1);
                    echo html_writer::empty_tag('input', $attrs);
                }
                echo format_string($option->description);
                echo html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('spacer'), 'width' => 30, 'hight' => 1));
            }
        }
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
            $mform->addElement('header', "head{$this->name}", format_string($this->description));
            $mform->setExpanded("head{$this->name}");
            foreach ($this->options as $option) {
                $key = "element{$this->name}{$option->id}";
                $group[] = &$mform->createElement('checkbox', $key, '', format_string($option->description));
                $mform->setType("element{$this->name}{$option->id}", PARAM_INT);
            }

            $mform->addGroup($group, 'element' . $this->name.'_set');
        }
    }

    public function set_data(&$defaults, $issueid = 0) {
        if ($issueid) {
            if (!empty($this->options)) {
                $values = $this->get_value($issueid);
                if (is_array($values)) {
                    foreach ($values as $v) {
                        if (array_key_exists($v, $this->options)) {
                            // Check option still exists.
                            $elementname = "element{$this->name}{$option->id}";
                            $defaults->$elementname = 1;
                        }
                    }
                } else {
                    $v = $values; // Single value.
                    if (array_key_exists($v, $this->options)) {
                        // Check option still exists.
                        $elementname = "element{$this->name}{$option->id}";
                        $defaults->$elementname = 1;
                    }
                }
            }
        }
    }

    public function form_process(&$data) {
        global $DB;

        $params = array('elementid' => $this->id, 'trackerid' => $data->trackerid, 'issueid' => $data->issueid);
        if (!$attribute = $DB->get_record('tracker_issueattribute', $params)) {
            $attribute = new StdClass();
            $attribute->trackerid = $data->trackerid;
            $attribute->issueid = $data->issueid;
            $attribute->elementid = $this->id;
        }

        $elmvalues = array();
        if (!empty($this->options)) {
            foreach ($this->options as $optid => $opt) {
                $elmname = 'element'.$this->name.$optid;
                $data->$elmname = optional_param($elmname, '', PARAM_TEXT);
                if (!empty($data->$elmname)) {
                    $elmvalues[] = $optid;
                }
            }
        }

        $attribute->elementitemid = implode(',', $elmvalues);
        // In this case we have elementitem id or idlist.
        $attribute->timemodified = time();

        if (!isset($attribute->id)) {
            $attribute->id = $DB->insert_record('tracker_issueattribute', $attribute);
            if (empty($attribute->id)) {
                print_error('erroraddissueattribute', 'tracker', '', 2);
            }
        } else {
            $DB->update_record('tracker_issueattribute', $attribute);
        }
    }

    public function type_has_options() {
        return true;
    }
}
