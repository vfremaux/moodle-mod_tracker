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

/**
 * A generic class for collecting all that is common to all elements
 */
defined('MOODLE_INTERNAL') || die();

abstract class trackerelement {

    protected $id;
    protected $course;
    protected $usedid;
    protected $name;
    protected $description;
    protected $format;
    protected $type;
    protected $sortorder;
    protected $maxorder;
    protected $value;
    protected $options;
    protected $tracker;
    protected $active;
    protected $private;
    protected $mandatory;
    protected $canbemodifiedby;
    protected $context;
    protected $paramint1;
    protected $paramint2;
    protected $paramchar1;
    protected $paramchar2;

    /**
     * Loads all data about a traker element.
     * If the element is a used element, will pull as master id the tracker element record and adds the used attributes to it.
     * @param objectref $tracker
     * @param int $elementid if $used is true, points to the tracker_elementused table. If false, points directly to the tracker_element table.
     * @param bool $used
     * @return an object that represents a pure tracker_element or a tracker_usedelement as an element with additional attributes and a
     * usedid additional id.
     */
    public function __construct(&$tracker, $elementid = null, $used = false) {
        global $DB;

        $this->id = $elementid;
        $cm = get_coursemodule_from_instance('tracker', $tracker->id);

        if ($elementid && $used) {
            $elmusedrec = $DB->get_record('tracker_elementused', array('id' => $elementid));
            $this->usedid = $elementid;
            $elementid = $elmusedrec->elementid;
            $this->active = $elmusedrec->active;
            $this->mandatory = $elmusedrec->mandatory;
            $this->private = $elmusedrec->private;
            $this->sortorder = $elmusedrec->sortorder;
            $this->canbemodifiedby = $elmusedrec->canbemodifiedby;
        }

        if ($elementid) {
            $elmrec = $DB->get_record('tracker_element', array('id' => $elementid));
            $this->id = $elmrec->id;
            $this->name = $elmrec->name;
            $this->description = $elmrec->description;
            $this->course = $elmrec->course;
            $this->type = $elmrec->type;
            $this->paramint1 = $elmrec->paramint1;
            $this->paramint2 = $elmrec->paramint2;
            $this->paramchar1 = $elmrec->paramchar1;
            $this->paramchar2 = $elmrec->paramchar2;
        }

        $this->context = context_module::instance($cm->id);
        $this->options = null;
        $this->value = null;
        $this->tracker = $tracker;
    }

    /**
     * Magic Get function for php object attribute read control.
     */
    public function __get($key) {
        $method = 'magic_get_'.$key;
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        if (!isset($this->$key)) {
            throw new moodle_exception('No such field '.$key.' in tracker element');
        }
        return $this->$key;
    }

    /**
     * Magic Set function for php object change control.
     */
    public function __set($key, $value) {
        $method = 'magic_set_'.$key;
        if (method_exists($this, $method)) {
            $this->$method($value);
        }
        if (!isset($this->$key)) {
            throw new moodle_exception('No such field '.$key.' in tracker element');
        }
        $this->$key = $value;
    }

    /**
     * If true, element is like a select or a radio box array
     * and has suboptions to define
     */
    public function type_has_options() {
        return false;
    }

    /**
     * Tells if options are defined for this instance
     */
    public function has_options() {
        return $this->options !== null;
    }

    /**
     * Get an option value
     */
    public function get_option($optionid) {
        return $this->options[$optionid];
    }

    /**
     * If true, this element can be told to be mandatory.
     */
    public function has_mandatory_option() {
        return true;
    }

    /**
     * If true, this element can be told to be private.
     * A private element can be edited by the ticket operators,
     * but is not seen by ticket owners.
     */
    public function has_private_option() {
        return true;
    }

    /**
     * in case we have options (such as checkboxes or radio lists, get options from db.
     * this is backcalled by specific type constructors after core construction.
     */
    public function set_options_from_db() {
        global $DB;

        if (isset($this->id)) {
            $this->options = $DB->get_records_select('tracker_elementitem', " elementid = ? AND active = 1 ORDER BY sortorder", array($this->id));
            if ($this->options) {
                foreach ($this->options as $option) {
                    $this->maxorder = max($option->sortorder, $this->maxorder);
                }
            } else {
                $this->maxorder = 0;
            }
        } else {
            print_error('errorinvalidelementID', 'tracker');
        }
    }

    /**
     * Gets the current value for this element instance in an issue.
     */
    public function get_value($issueid) {
        global $DB;

        if (!$issueid) {
            return '';
        }

        $sql = "
            SELECT
                elementitemid
            FROM
                {tracker_issueattribute}
            WHERE
                elementid = {$this->id} AND
                issueid = {$issueid}
        ";
        $this->value = $DB->get_field_sql($sql);
        return($this->value);
    }

    public function view_search() {
        $this->edit();
    }

    public function view_query() {
        $this->view(true);
    }

    /**
     * given a tracker and an element form key in a static context,
     * build a suitable trackerelement object that represents it.
     */
    public static function find_instance(&$tracker, $elementkey) {
        global $DB;

        $elmname = preg_replace('/^element/', '', $elementkey);

        $sql = "
            SELECT
                e.*,
                eu.id as usedid
            FROM
                {tracker_element} e,
                {tracker_elementused} eu
            WHERE
                e.id = eu.elementid AND
                eu.trackerid = ? AND
                e.name = ?
        ";

        if ($element = $DB->get_record_sql($sql, array($tracker->id, $elmname))) {

            $eltypeconstuctor = $element->type.'element';
            $instance = new $eltypeconstuctor($tracker, $element->id);
            return $element;
        }

        return null;
    }

    /**
     * Get the element view when the ticket is being edited. The default version
     * assumes a simple text field as input.
     */
    public function edit($issueid = 0) {
        $this->get_value($issueid);
        $str = '';
        $attrs = array('type' => 'hidden', 'name' => 'element'.$this->name, 'value' => format_string($this->value));
        $str .= html_writer::empty_tag('input', $attrs);
        $attrs = array('type' => 'text',
                       'name' => 'element'.$this->name.'_disabled',
                       'value' => format_string($this->value),
                       'disabled' => 'disabled', 'size' => 120);
        $str .= html_writer::empty_tag('input', $attrs);
        return $str;
    }

    public function set_data(&$defaults, $issueid = 0) {
        $elementname = "element{$this->name}";
        if ($issueid) {
            $defaults->$elementname = $this->get_value($issueid);
        } else {
            $defaults->$elementname = '';
        }
    }

    /**
     * Get the element view when the ticket is being displayed
     */
    abstract public function view($issueid = 0);

    /**
     * Provides the form element when building a new element instance
     */
    abstract public function add_form_element(&$mform);

    abstract public function form_process(&$data);

    /**
     * given a tracker and an id of a used element in a static context,
     * build a suitable trackerelement object that represents it.
     * what we need to knwo is the type of the element to call the adequate
     * constructor.
     */
    static public function find_instance_by_usedid(&$tracker, $usedid) {
        global $DB, $CFG;

        $sql = "
            SELECT
                eu.id,
                e.type
            FROM
                {tracker_element} e,
                {tracker_elementused} eu
            WHERE
                e.id = eu.elementid AND
                eu.id = ?
        ";

        if ($element = $DB->get_record_sql($sql, array($usedid))) {

            $eltypeconstructor = $element->type.'element';
            include_once($CFG->dirroot.'/mod/tracker/classes/trackercategorytype/'.$element->type.'/'.$element->type.'.class.php');
            $instance = new $eltypeconstructor($tracker, $usedid, true);
            return $instance;
        }

        return null;
    }

    /**
     * given a tracker and an id of a used element in a static context,
     * build a suitable trackerelement object that represents it.
     * what we need to knwo is the type of the element to call the adequate
     * constructor.
     */
    static public function find_instance_by_id(&$tracker, $id) {
        global $DB, $CFG;

        if ($element = $DB->get_record('tracker_element', array('id' => $id), 'id, type', 'id')) {
            $eltypeconstructor = $element->type.'element';
            include_once($CFG->dirroot.'/mod/tracker/classes/trackercategorytype/'.$element->type.'/'.$element->type.'.class.php');
            $instance = new $eltypeconstructor($tracker, $id, false);
            return $instance;
        }

        return null;
    }
}
