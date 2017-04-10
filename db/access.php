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
 * @package     mod_tracker
 * @category    mod
 * @author      Clifford Tham, Valery Fremaux > 1.8
 */
defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    'mod/tracker:addinstance' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetype' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'mod/tracker:seeissues' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetype' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'mod/tracker:viewallissues' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetype' => array(
            'manager' => CAP_ALLOW
        )
    ),

    'mod/tracker:manage' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetype' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'mod/tracker:managepriority' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetype' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'mod/tracker:viewpriority' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetype' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'mod/tracker:managewatches' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetype' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'mod/tracker:canbecced' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetype' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'mod/tracker:resolve' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetype' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'mod/tracker:report' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetype' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'mod/tracker:develop' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetype' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'mod/tracker:comment' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetype' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'mod/tracker:configure' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetype' => array(
            'manager' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
        )
    ),

    'mod/tracker:viewreports' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetype' => array(
            'manager' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
        )
    ),

    'mod/tracker:configurenetwork' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetype' => array(
            'manager' => CAP_ALLOW,
        )
    ),

    'mod/tracker:shareelements' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetype' => array(
            'manager' => CAP_ALLOW,
        )
    ),

);
