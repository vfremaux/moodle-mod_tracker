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
 * @package    mod_tracker
 * @category   mod
 * @author     Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class mod_tracker_admin_renderer extends \plugin_renderer_base {

    protected $tracker;
    protected $cm;
    protected $context;
    protected $used;

    /**
     * Initializes internal objects
     * @param objectref &$tracker
     * @param objectref &$cm
     */
    public function init(&$tracker, &$cm) {
        $this->tracker = $tracker;
        $this->cm = $cm;
        $this->context = context_module::instance($cm->id);
    }

    /**
     * Print admin table container
     */
    public function admin_table() {

        $str = '';

        $str .= '<div class="container-fluid">'; // Table.
        $str .= '<div class="row-fluid">'; // Row.
        $str .= '<div class="span6 col-6">'; // Cell.
        $str .= $this->admin_elements_used();
        $str .= '</div>'; // Cell.
        $str .= '<div class="span6 col-6">'; // Cell.
        $str .= $this->admin_elements();
        $str .= '</div>'; // Cell.
        $str .= '</div>'; // Row.
        $str .= '</div>'; // Table.

        return $str;
    }

    /**
     * Prints used element list
     */
    public function admin_elements_used() {
        global $DB;

        if (empty($this->tracker)) {
            throw new moodle_exception('Admin renderer not initialized');
        }

        $str = $this->output->box_start('center', '100%', '', '', 'generalbox', 'description');

        tracker_loadelementsused($this->tracker, $this->used);

        $str .= $this->output->heading(get_string('elementsused', 'tracker'));

        $orderstr = get_string('order', 'tracker');
        $namestr = get_string('name');
        $typestr = get_string('type', 'tracker');
        $cmdstr = get_string('action', 'tracker');

        $table = new html_table();
        $table->head = array("<b>$orderstr</b>", "<b>$namestr</b>", "<b>$typestr</b>", "<b>$cmdstr</b>");
        $table->width = '100%';
        $table->size = array(20, 250, 50, 100);
        $table->align = array('left', 'center', 'center', 'center');

        if (!empty($this->used)) {
            foreach ($this->used as $element) {
                $icontype = $this->output->pix_icon('/types/'.$element->type, '', 'mod_tracker');

                if ($element->sortorder > 1) {
                    $params = array('id' => $this->cm->id,
                                    'view' => 'admin',
                                    'what' => 'raiseelement',
                                    'elementid' => $element->id);
                    $url = new moodle_url('/mod/tracker/view.php', $params);
                    $actions = '&nbsp;<a href="'.$url.'">'.$this->output->pix_icon('/t/up', '', 'core').'</a>';
                } else {
                    $actions = '&nbsp;'.$this->output->pix_icon('up_shadow', '', 'mod_tracker');
                }

                if ($element->sortorder < count($this->used)) {
                    $params = array('id' => $this->cm->id,
                                    'view' => 'admin',
                                    'what' => 'lowerelement',
                                    'elementid' => $element->id);
                    $url = new moodle_url('/mod/tracker/view.php', $params);
                    $pix = $this->output->pix_icon('/t/down', '', 'core');
                    $actions .= '&nbsp;<a href="'.$url.'">'.$pix.'</a>';
                } else {
                    $actions .= '&nbsp;'.$this->output->pix_icon('down_shadow', '', 'mod_tracker');
                }

                $params = array('id' => $this->cm->id,
                                'view' => 'admin',
                                'what' => 'editelement',
                                'elementid' => $element->id,
                                'used' => 1,
                                'type' => $element->type);
                $url = new moodle_url('/mod/tracker/view.php', $params);
                $actions .= '&nbsp;<a href="'.$url.'">'.$this->output->pix_icon('/t/edit', '', 'core').'</a>';

                if ($element->type_has_options()) {
                    $params = array('id' => $this->cm->id,
                                    'view' => 'admin',
                                    'what' => 'viewelementoptions',
                                    'elementid' => $element->id);
                    $url = new moodle_url('/mod/tracker/view.php', $params);
                    $pix = $this->output->pix_icon('editoptions', '', 'mod_tracker');
                    $actions .= '&nbsp;<a href="'.$url.'" title="'.get_string('editoptions', 'mod_tracker').'">'.$pix.'</a>';
                }

                $params = array('id' => $this->cm->id,
                                'view' => 'admin',
                                'what' => 'removeelement',
                                'usedid' => $element->id);
                $url = new moodle_url('/mod/tracker/view.php', $params);
                $actions .= '&nbsp;<a href="'.$url.'">'.$this->output->pix_icon('/t/right', '', 'core').'</a>';

                if (!$element->mandatory) {
                    if ($element->active) {
                        $params = array('id' => $this->cm->id,
                                        'view' => 'admin',
                                        'what' => 'setinactive',
                                        'usedid' => $element->id);
                        $url = new moodle_url('/mod/tracker/view.php', $params);
                        $pix = $this->output->pix_icon('/t/hide', '', 'core');
                        $actions .= '&nbsp;<a href="'.$url.'" title="'.get_string('setinactive', 'tracker').'">'.$pix.'</a>';
                    } else {
                        $params = array('id' => $this->cm->id,
                                        'view' => 'admin',
                                        'what' => 'setactive',
                                        'usedid' => $element->id);
                        $url = new moodle_url('/mod/tracker/view.php', $params);
                        $pix = $this->output->pix_icon('/t/show', '', 'core');
                        $actions .= '&nbsp;<a href="'.$url.'" title="'.get_string('setactive', 'tracker').'">'.$pix.'</a>';
                    }
                } else {

                    $attrs = array('class' => 'dimmed');
                    if ($element->active) {
                        $activestr = get_string('isactive', 'tracker');
                        $actions .= '&nbsp;'.$this->output->pix_icon('/t/hide', $activestr, 'core', $attrs);
                    } else {
                        $activestr = get_string('isinactive', 'tracker');
                        $actions .= '&nbsp;'.$this->output->pix_icon('/t/show', $activestr, 'core', $attrs);
                    }
                }

                if ($element->has_mandatory_option()) {
                    if ($element->mandatory) {
                        $params = array('id' => $this->cm->id,
                                        'view' => 'admin',
                                        'what' => 'setnotmandatory',
                                        'usedid' => $element->id);
                        $url = new moodle_url('/mod/tracker/view.php', $params);
                        $alt = get_string('setnotmandatory', 'tracker');
                        $pix = $this->output->pix_icon('notempty', $alt, 'tracker');
                        $actions .= '&nbsp;<a href="'.$url.'" title="'.$alt.'">'.$pix.'</a>';
                    } else {
                        $params = array('id' => $this->cm->id,
                                        'view' => 'admin',
                                        'what' => 'setmandatory',
                                        'usedid' => $element->id);
                        $url = new moodle_url('/mod/tracker/view.php', $params);
                        $alt = get_string('setmandatory', 'tracker');
                        $pix = $this->output->pix_icon('empty', $alt, 'tracker');
                        $actions .= '&nbsp;<a href="'.$url.'" title="'.$alt.'">'.$pix.'</a>';
                    }
                } else {
                    if ($element->mandatory) {
                        $mandatorystr = get_string('ismandatory', 'tracker');
                        $actions .= $this->output->pix_icon('notempty', $mandatorystr, 'tracker');
                    } else {
                        $mandatorystr = get_string('isoptional', 'tracker');
                        $actions .= '&nbsp;'.$this->output->pix_icon('empty', $mandatorystr, 'tracker');
                    }
                }

                if ($element->has_private_option() && !$element->mandatory) {
                    if ($element->private) {
                        $params = array('id' => $this->cm->id,
                                        'view' => 'admin',
                                        'what' => 'setpublic',
                                        'usedid' => $element->id);
                        $url = new moodle_url('/mod/tracker/view.php', $params);
                        $alt = get_string('setpublic', 'tracker');
                        $actions .= '&nbsp;'.$this->output->pix_icon('t/locked', $alt, 'core');
                    } else {
                        $params = array('id' => $this->cm->id,
                                        'view' => 'admin',
                                        'what' => 'setprivate',
                                        'usedid' => $element->id);
                        $url = new moodle_url('/mod/tracker/view.php', $params);
                        $alt = get_string('setprivate', 'tracker');
                        $pix = $this->output->pix_icon('t/lock', $alt, 'core');
                        $actions .= '&nbsp;<a href="'.$url.'" title="'..'">'.$pix.'</a>';
                    }
                } else {
                    if ($element->has_private_option()) {
                        // Is can choose privac but mandatory, needs to be forced visible.
                        $DB->set_field('tracker_elementused', 'private', 0, array('id' => $element->id));
                    }
                    if ($element->private) {
                        $privatestr = get_string('isprivate', 'tracker');
                        $actions .= '&nbsp;'.$this->output->pix_icon('t/locked', $privatestr, 'core');
                    } else {
                        $privatestr = get_string('ispublic', 'tracker');
                        $actions .= '&nbsp;'.$this->output->pix_icon('t/lock', $privatestr, 'core');
                    }
                }

                $dimmed = '<span class="dimmed">'.format_string($element->description).'</span>';
                $description = ($element->private) ? $dimmed : format_string($element->description);

                $table->data[] = array($element->sortorder, $description, $icontype, $actions);
            }
            $str .= html_writer::table($table);
        } else {
            $str .= '<center>';
            $str .= get_string('noelements', 'tracker');
            $str .= '<br/></center>';
        }

        $str .= $this->output->box_end();

        return $str;
    }

    public function admin_elements() {
        global $COURSE;

        if (empty($this->tracker)) {
            throw new moodle_exception('Admin renderer not initialized');
        }

        $str = '';

        $str .= $this->output->box_start('center', '100%', '', '', 'generalbox', 'description');
        $str .= $this->admin_elements_form();
        $str .= $this->output->box_end();

        $str .= $this->output->box_start('center', '100%', '', '', 'generalbox', 'description');
        tracker_loadelements($this->tracker, $elements);

        $str .= $this->output->heading(get_string('elements', 'tracker'));

        $localstr = get_string('local', 'tracker');
        $namestr = get_string('name');
        $typestr = get_string('type', 'tracker');
        $cmdstr = get_string('action', 'tracker');

        unset($table);
        $table = new html_table();
        $table->head = array("<b>$cmdstr</b>", "<b>$namestr</b>", "<b>$localstr</b>", "<b>$typestr</b>");
        $table->width = '100%';
        $table->size = array(100, 250, 50, 50);
        $table->align = array('left', 'center', 'center', 'center');

        if (!empty($elements)) {
            // Clean list from used elements.
            foreach ($elements as $id => $element) {
                if (in_array($element->id, array_keys($this->used))) {
                    unset($elements[$id]);
                }
            }
            // Make list.
            foreach ($elements as $element) {

                $name = format_string($element->description);
                $name .= '<br />';
                $name .= '<span style="font-size:70%">';
                $name .= $element->name;
                $name .= '</span>';
                if ($element->has_options()) {
                    // Take care of the __get empty() issue.
                    $options = $element->options;
                    if (empty($options)) {
                        $name .= ' <span class="error">('.get_string('nooptions', 'tracker').')</span>';
                    }
                }

                $params = array('id' => $this->cm->id,
                                'view' => 'admin',
                                'what' => 'addelement',
                                'elementid' => $element->id);
                $url = new moodle_url('/mod/tracker/view.php', $params);
                $alt = get_string('addtothetracker', 'tracker');
                $pix = $this->output->pix_icon('t/moveleft', $alt, 'core');
                $actions = '&nbsp;<a href="'.$url.'" title="'.$alt.'" >'.$pix.'</a>';

                if ($element->type_has_options()) {
                    $params = array('id' => $this->cm->id,
                                    'view' => 'admin',
                                    'what' => 'viewelementoptions',
                                    'elementid' => $element->id);
                    $url = new moodle_url('/mod/tracker/view.php', $params);
                    $alt = get_string('editoptions', 'tracker');
                    $pix = $this->output->pix_icon('editoptions', $alt, 'mod_tracker');
                    $actions .= '&nbsp;<a href="'.$url.'" title="'.$alt.'">'.$pix.'</a>';
                }

                $params = array('id' => $this->cm->id,
                                'view' => 'admin',
                                'what' => 'editelement',
                                'elementid' => $element->id,
                                'type' => $element->type);
                $url = new moodle_url('/mod/tracker/view.php', $params);
                $alt = get_string('editproperties', 'tracker');
                $pix = $this->output->pix_icon('t/edit', $alt, 'core');
                $actions .= '&nbsp;<a href="'.$url.'" title="'.$alt.'">'.$pix.'</a>';

                $params = array('id' => $this->cm->id,
                                'view' => 'admin',
                                'what' => 'deleteelement',
                                'elementid' => $element->id);
                $url = new moodle_url('/mod/tracker/view.php', $params);
                $alt = get_string('delete');
                $pix = $this->output->pix_icon('t/delete', $alt, 'core');
                $actions .= '&nbsp;<a href="'.$url.'" title="'.$alt.'">'.$pix.'</a>';

                $local = '';
                if ($element->course == $COURSE->id) {
                    $local = $this->output->pix_icon('i/course', '', 'core');
                }
                $type = $this->output->pix_icon("types/{$element->type}", '', 'mod_tracker');
                $table->data[] = array($actions, $name, $local, $type);
            }
            $str .= html_writer::table($table);
        } else {
            $str .= '<center>';
            $str .= get_string('noelements', 'tracker');
            $str .= '<br /></center>';
        }
        $str .= $this->output->box_end();

        return $str;
    }

    public function admin_elements_form() {
        $str = '';

        $formurl = new moodle_url('/mod/tracker/view.php');
        $str .= '<form name="addelement" method="post" action="'.$formurl.'">';
        $str .= '<input type="hidden" name="view" value="admin" />';
        $str .= '<input type="hidden" name="id" value="'.$this->cm->id.'" />';
        $str .= '<input type="hidden" name="what" value="createelement" />';
        $str .= '<table width="100%">';
        $str .= '<tr>';
        $str .= '<td valign="top">';
        $str .= '<b>'.get_string('createnewelement', 'tracker').':</b>';
        $str .= '</td>';
        $str .= '<td valign="top">';
        $types = tracker_getelementtypes();
        foreach ($types as $type) {
            $elementtypesmenu[$type] = get_string($type, 'tracker');
        }
        $attrs = array('onchange' => 'document.forms[\'addelement\'].submit();');
        $str .= html_writer::select($elementtypesmenu, 'type', '', array('' => 'choose'), $attrs);
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '</table>';
        $str .= '</form>';

        return $str;
    }

    public function summary() {

        $str = $this->output->box_start('center', '100%', '', '', 'generalbox', 'bugreport');

        $summarytable = new html_table();
        $summarytable->head = array('', '');
        $summarytable->width = '90%';
        $summarytable->size = array('30%', '70%');
        $summarytable->align = array('right', 'left');

        $row = array(get_string('trackername', 'tracker'), format_string($this->tracker->name));
        $summarytable->data[] = $row;

        $row = array(get_string('description'), format_string($this->tracker->intro));
        $summarytable->data[] = $row;

        $tmp = get_string('sum_reported', 'tracker').': '.tracker_getnumissuesreported($this->tracker->id).'<br />';
        $tmp .= get_string('sum_posted', 'tracker').': '.tracker_getnumissuesreported($this->tracker->id, POSTED).'<br />';
        $tmp .= get_string('sum_opened', 'tracker').': '.tracker_getnumissuesreported($this->tracker->id, OPEN).'<br />';
        $tmp .= get_string('sum_resolved', 'tracker').': '.tracker_getnumissuesreported($this->tracker->id, RESOLVED);

        $row = array(get_string('numberofissues', 'tracker'), $tmp);
        $summarytable->data[] = $row;

        tracker_loadelements($this->tracker, $elements);
        $tmp = '';
        if (!empty($elements)) {
            $keys = array_keys($elements);
            for ($i = 0; $i < count($keys); $i++) {
                $element = $elements[$keys[$i]];
                $params = array('id' => $this->cm->id,
                                'what' => 'editelement',
                                'elementid' => $element->id,
                                'type' => $element->type);
                $url = new moodle_url('/mod/tracker/view.php', $params);
                $tmp .= '<a href="'.$url.'">';
                $tmp .= format_string($element->description);
                $tmp .= '</a>';
                if ($i < count($keys) - 1) {
                    $tmp .= ', ';
                }
            }
        } else {
            $tmp .= $this->output->notification(get_string('noelementscreated', 'tracker'));
            $tmp .= '<br/>';
        }

        $row = array(get_string('elements', 'tracker'), $tmp);
        $summarytable->data[] = $row;

        $admins = tracker_getadministrators($this->context);
        $tmp = '';
        if (!empty($admins)) {
            $keys = array_keys($admins);
            for ($j = 0; $j < count($keys); $j++) {
                $admin = $admins[$keys[$j]];
                $tmp .= fullname($admin);
                if ($j < count($keys) - 1) {
                    $tmp .= ', ';
                }
            }
        } else {
            $tmp .= get_string('notrackeradmins', 'tracker');
            $tmp .= '<br/>';
        }

        $row = array(get_string('administrators', 'tracker'), $tmp);
        $summarytable->data[] = $row;

        $resolvers = tracker_getresolvers($this->context);
        $tmp = '';
        if (!empty($resolvers)) {
            $keys = array_keys($resolvers);
            for ($j = 0; $j < count($keys); $j++) {
                $resolver = $resolvers[$keys[$j]];
                $tmp .= fullname($resolver);
                if ($j < count($keys) - 1) {
                    $tmp .= ', ';
                }
            }
        } else {
            $tmp .= get_string('noresolvers', 'tracker');
            $tmp .= '<br/>';
        }

        $row = array(get_string('resolvers', 'tracker'), $tmp);
        $summarytable->data[] = $row;

        $developers = tracker_getdevelopers($this->context);
        $tmp = '';
        if (!empty($developers)) {
            $keys = array_keys($developers);
            for ($j = 0; $j < count($keys); $j++) {
                $developer = $developers[$keys[$j]];
                $tmp .= fullname($developer);
                if ($j < count($keys) - 1) {
                    $tmp .= ', ';
                }
            }
        } else {
            $tmp .= get_string('nodevelopers', 'tracker');
            $tmp .= '<br/>';
        }

        $row = array(get_string('potentialresolvers', 'tracker'), $tmp);
        $summarytable->data[] = $row;

        $str .= html_writer::table($summarytable);

        $str .= $this->output->box_end();

        return $str;
    }
}