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
 * @package    mod_tracker
 * @author Valery Fremaux
 * @copyright  2014 MyLearningFactory (http://www.mylearningfactory.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_tracker\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Event for when a tracker activity is commented.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      @type int anonymous if tracker is anonymous.
 *      @type int cmid course module id.
 * }
 *
 * @package    mod_tracker
 * @since      Moodle 2.7
 * @copyright  2013 Ankit Agarwal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tracker_issuereported extends \core\event\base {

    var $issueid;

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'tracker';
    }

    public static function get_name() {
        return get_string('eventtrackerissuereported', 'tracker');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return 'The ' . $this->other['modulename'] . ' instance ' . $this->other['instanceid'] .
                ' had the issue '.$this->other['issueid'].' commented by ' . $this->userid;
    }

    /**
     * Returns relevant URL.
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/' . $this->other['modulename'] . '/view.php', array('id' => $this->objectid, 'view' => 'view', 'screen' => 'viewanissue', 'issueid' => $this->other['issueid']));
    }

    /**
     * Legacy event name.
     *
     * @return string legacy event name
     */
    public static function get_legacy_eventname() {
        return 'reportissue';
    }

    /**
     * Legacy event data.
     *
     * @return \stdClass
     */
    protected function get_legacy_eventdata() {
        $eventdata = new \stdClass();
        $eventdata->modulename = $this->other['modulename'];
        $eventdata->name       = $this->other['name'];
        $eventdata->cmid       = $this->objectid;
        $eventdata->courseid   = $this->courseid;
        $eventdata->userid     = $this->userid;
        return $eventdata;
    }

    /**
     * replace add_to_log() statement.
     *
     * @return array of parameters to be passed to legacy add_to_log() function.
     */
    protected function get_legacy_logdata() {
        $log1 = array($this->courseid, "course", "commentissue", "../mod/" . $this->other['modulename'] . "/view.php?id=" .
                $this->objectid.'&amp;view=view&amp;screen=viewanissue&amp;issueid='.$this->other['issueid'], $this->other['modulename'] . " " . $this->other['instanceid']);
        return array($log1);
    }

    /**
     * custom validations
     *
     * Throw \coding_exception notice in case of any problems.
     */
    protected function validate_data() {
        parent::validate_data();
        if (!isset($this->other['modulename'])) {
            throw new \coding_exception("Field other['modulename'] cannot be empty");
        }
        if (!isset($this->other['instanceid'])) {
            throw new \coding_exception("Field other['instanceid'] cannot be empty");
        }
        if (!isset($this->other['name'])) {
            throw new \coding_exception("Field other['name'] cannot be empty");
        }
        if (!isset($this->other['issueid'])) {
            throw new \coding_exception("Field other['issueid'] cannot be empty");
        }
    }

    public final static function create_from_issue(&$tracker, $issueid) {
        global $USER;
        // If not set, get the module context.

        $cm = get_coursemodule_from_instance('tracker', $tracker->id);

        if (empty($modcontext)) {
            $modcontext = \context_module::instance($cm->id);
        }

        // Create event object for course module update action.
        $event = static::create(array(
            'context'  => $modcontext,
            'objectid' => $cm->id,
            'userid' => $USER->id,
            'other'    => array(
                'modulename' => $cm->modname,
                'instanceid' => $cm->instance,
                'name'       => $cm->name,
                'issueid'    => $issueid,
            )
        ));
        return $event;
    }
}

