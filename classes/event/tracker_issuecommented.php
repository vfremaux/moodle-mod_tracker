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
 * This file contains an event for when a feedback activity is viewed.
 *
 * @package     mod_tracker
 * @author      Valery Fremaux
 * @copyright   2014 onwards Valery Fremaux (http://www.mylearningfactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_tracker\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event for when a tracker activity is commented.
 */
class tracker_issuecommented extends tracker_baseevent {

    protected $issueid;

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'tracker_issuecomment';
    }

    public static function get_name() {
        return get_string('event_tracker_issue_commented', 'tracker');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        $desc = 'The ' . $this->other['modulename'] . ' instance ' . $this->other['instanceid'];
        $desc .= ' had the issue '.$this->other['issueid'].' commented by ' . $this->userid;
        return $desc;
    }

    /**
     * Returns relevant URL.
     * @return \moodle_url
     */
    public function get_url() {
        $params = array('id' => $this->objectid,
                        'view' => 'view',
                        'screen' => 'viewanissue',
                        'issueid' => $this->other['issueid']);
        return new \moodle_url('/mod/' . $this->other['modulename'] . '/view.php', $params);
    }

    /**
     * Legacy event name.
     *
     * @return string legacy event name
     */
    public static function get_legacy_eventname() {
        return 'commentissue';
    }

    /**
     * replace add_to_log() statement.
     *
     * @return array of parameters to be passed to legacy add_to_log() function.
     */
    protected function get_legacy_logdata() {
        $logurl = '../mod/'.$this->other['modulename'].'/view.php?id=';
        $logurl .= $this->objectid.'&amp;view=view&amp;screen=viewanissue&amp;issueid='.$this->other['issueid'];
        $info = $this->other['modulename'] . ' '.$this->other['instanceid'];
        $log1 = array($this->courseid, 'course', 'commentissue', $logurl, $info);
        return array($log1);
    }

}

