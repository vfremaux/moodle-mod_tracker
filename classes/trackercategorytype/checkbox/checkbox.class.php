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

class checkboxelement extends trackerelement {

    protected $spacer;

    public function __construct(&$tracker, $id = null, $used = false) {
        parent::__construct($tracker, $id, $used);
        $this->set_options_from_db();
        $this->spacer = html_writer::empty_tag('br');
    }

    public function edit($issueid = 0) {

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
                echo $this->spacer;
            }
        }
    }

    public function view($issueid = 0) {
        $str = '';

        $this->get_value($issueid); // Loads $this->value with current value for this issue.
        if (!empty($this->value)) {
            $values = explode(',', $this->value);
            foreach ($values as $selected) {
                $str .= format_string($this->options[$selected]->description).$this->spacer;
            }
        }
        return $str;
    }

    public function add_form_element(&$mform) {
        if (isset($this->options)) {

            $mform->addElement('static', 'element'.$this->name.'_set', format_string($this->description));
            foreach ($this->options as $option) {
                $mform->addElement('checkbox', "element{$this->name}{$option->id}", '&ensp;'.format_string($option->description));
                $mform->setType("element{$this->name}{$option->id}", PARAM_INT);
            }
        }
    }

    public function set_data(&$defaults, $issueid = 0) {
        if ($issueid) {
            if (!empty($this->options)) {
                $values = $this->get_value($issueid);
                if (!empty($values)) {
                    $values = explode(',', $values);
                    foreach ($values as $v) {
                        if (array_key_exists($v, $this->options)) {
                            // Check option still exists.
                            $elementname = "element{$this->name}{$v}";
                            $defaults->$elementname = 1;
                        }
                    }
                }
            } else {
                mtrace("Empty options ");
            }
        }
    }

    public function form_process(&$data, $options = null) {
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

        $attribute->elementitemid = implode(',', $elmvalues); // In this case we have elementitem id or idlist.
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

