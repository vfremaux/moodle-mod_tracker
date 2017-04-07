<?php
// This file is part of the learningtimecheck plugin for Moodle - http://moodle.org/
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
 * @package mod_learningtimecheck
 * @category mod
 * @author  David Smith <moodle@davosmith.co.uk> as checklist
 * @author Valery Fremaux
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/tracker/locallib.php');

/*
 * Global settings for the learningtimecheck
 */

if ($ADMIN->fulltree) {

    $options = array('open' => get_string('open', 'mod_tracker'),
                     'closed' => get_string('closed', 'mod_tracker'));

    $key = 'mod_tracker/initialviewccs';
    $label = get_string('configinitialviewccs', 'mod_tracker');
    $desc = get_string('configinitialviewccs_desc', 'mod_tracker');
    $settings->add(new admin_setting_configselect($key, $label, $desc, 'open', $options));

    $key = 'mod_tracker/initialviewcomments';
    $label = get_string('configinitialviewcomments', 'mod_tracker');
    $desc = get_string('configinitialviewcomments_desc', 'mod_tracker');
    $settings->add(new admin_setting_configselect($key, $label, $desc, 'open', $options));

    $key = 'mod_tracker/initialviewhistory';
    $label = get_string('configinitialviewhistory', 'mod_tracker');
    $desc = get_string('configinitialviewhistory_desc', 'mod_tracker');
    $settings->add(new admin_setting_configselect($key, $label, $desc, 'open', $options));

    $key = 'mod_tracker/initialviewdeps';
    $label = get_string('configinitialviewdeps', 'mod_tracker');
    $desc = get_string('configinitialviewdeps_desc', 'mod_tracker');
    $settings->add(new admin_setting_configselect($key, $label, $desc, 'open', $options));

    $settings->add(new admin_setting_heading('formadminhdr', get_string('formelementsadministration', 'tracker'), ''));

    $yesnoopts = array('0' => get_string('no'), '1' => get_string('yes'));

    $key = 'mod_tracker/initiallyactive';
    $label = get_string('configinitiallyactive', 'mod_tracker');
    $desc = get_string('configinitiallyactive_desc', 'mod_tracker');
    $settings->add(new admin_setting_configselect($key, $label, $desc, 1, $yesnoopts));

    $key = 'mod_tracker/initiallymandatory';
    $label = get_string('configinitiallymandatory', 'mod_tracker');
    $desc = get_string('configinitiallymandatory_desc', 'mod_tracker');
    $settings->add(new admin_setting_configselect($key, $label, $desc, 0, $yesnoopts));

    $key = 'mod_tracker/initiallyprivate';
    $label = get_string('configinitiallyprivate', 'mod_tracker');
    $desc = get_string('configinitiallyprivate_desc', 'mod_tracker');
    $settings->add(new admin_setting_configselect($key, $label, $desc, 0, $yesnoopts));


    if (tracker_supports_feature('emulate/community')) {
        // This will accept any.
        $settings->add(new admin_setting_heading('plugindisthdr', get_string('plugindist', 'tracker'), ''));

        $key = 'mod_tracker/emulatecommunity';
        $label = get_string('emulatecommunity', 'tracker');
        $desc = get_string('emulatecommunity_desc', 'tracker');
        $settings->add(new admin_setting_configcheckbox($key, $label, $desc, 0));
    } else {
        $label = get_string('plugindist', 'tracker');
        $desc = get_string('plugindist_desc', 'tracker');
        $settings->add(new admin_setting_heading('plugindisthdr', $label, $desc));
    }

    $settings->add(new admin_setting_heading('usersettingshdr', get_string('userdefaultpreferences', 'tracker'), ''));

    $states = array('open', 'resolving', 'waiting', 'testing', 'published', 'resolved', 'abandonned', 'oncomment');

    foreach ($states as $st) {
        $key = 'mod_tracker/user'.$st.'default';
        $label = get_string('configuser'.$st.'default', 'mod_tracker');
        $desc = get_string('configuserdefault_desc', 'mod_tracker');
        $settings->add(new admin_setting_configcheckbox($key, $label, $desc, 1));
    }

}