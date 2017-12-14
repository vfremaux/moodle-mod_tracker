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
 * Version details.
 *
 * @package     mod_tracker
 * @category    mod
 * @author      Clifford Tham till 1.8
 * @author      Valery Fremaux (valery.fremaux@gmeil.com)
 * @copyright   2009 onwards Valery Fremaux (valery.fremaux@gmeil.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version  = 2017081700;  // The current module version (Date: YYYYMMDDXX).
$plugin->requires = 2016052300;
$plugin->component = 'mod_tracker';   // Full name of the plugin (used for diagnostics).
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '3.1.0 (Build 2017081700)';
$plugin->dependencies = array('local_vflibs' => 2016081100);

// Non Moodle attributes.
$plugin->codeincrement = '3.1.0002';