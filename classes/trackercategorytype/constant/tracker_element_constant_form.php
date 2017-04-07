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
 * @package mod_tracker
 * @category mod
 * @author Valery Fremaux / 1.8
 * @date 06/08/2015
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/tracker/forms/tracker_element_form_base.php');

class tracker_element_constant_form extends tracker_moodle_form {

    public function definition() {
        $this->start_form();

        $mform = $this->_form;

        $options = array(
            0 => '-- '.get_string('customconstant', 'tracker').' --',
            1 => get_string('constantsiteshortname', 'tracker'),
            2 => get_string('constantsitefullname', 'tracker'),
            3 => get_string('constantcurrentidnumber', 'tracker'),
            4 => get_string('constantcurrentcourseidnumber', 'tracker'),
            5 => get_string('constantcurrentcourseshortname', 'tracker'),
            6 => get_string('constantcurrentcoursefullname', 'tracker'),
        );

        $mform->addElement('select', 'paramint1', get_string('constantinfosource', 'tracker'), $options);

        $mform->addElement('text', 'paramchar1', get_string('customconstant', 'tracker'));
        $mform->setType('paramchar1', PARAM_RAW);
        $mform->disabledIf('paramchar1', 'paramint1', 'neq', 0);

        $this->end_form();
    }

}