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
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

/**
 * @package mod_tracker
 * @category mod
 * @author Valery Fremaux / 1.8
 * @date 06/08/2015
 *
 * A class implementing a hidden/labelled element that captures the referer url
 */
require_once($CFG->dirroot.'/mod/tracker/classes/trackercategorytype/tracker_element_form.php');

class tracker_element_autourl_form extends tracker_moodle_form {

    function definition() {
        $this->start_form();
        $this->end_form();
    }

    function validation($data, $files) {
        return parent::validation($data, $files);
    }
}