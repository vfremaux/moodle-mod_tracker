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
 * A class implementing a radio button (exclusive choice) element horizontally displayed
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/tracker/classes/trackercategorytype/radio/radio.class.php');

class radiohorizelement extends radioelement {

    public function add_form_element(&$mform) {

        if (isset($this->options)) {
            $group = array();

            foreach ($this->options as $option) {
                $label = ' '.format_string($option->description);
                $group[] = &$mform->createElement('radio', 'element'.$this->name, '', $label, $option->id);
            }

            $mform->addGroup($group, 'element'.$this->name.'_set', format_string($this->description), array(' '), false);

            if (!empty($this->mandatory)) {
                $mform->addRule('element'.$this->name.'_set', null, 'required', null, 'client');
            }
        }
    }

    public function options_sep() {
        return ', ';
    }

}
