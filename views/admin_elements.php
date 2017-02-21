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
 *
 * From for showing element list
 */
defined('MOODLE_INTERNAL') || die();

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or.
$a = optional_param('a', 0, PARAM_INT);  // course ID.

$OUTPUT->box_start('center', '100%', '', '', 'generalbox', 'description');
echo $renderer->edmin_elements_form($cm);
$OUTPUT->box_end();

$OUTPUT->box_start('center', '100%', '', '', 'generalbox', 'description');

tracker_loadelements($tracker, $elements);

echo $OUTPUT->heading(get_string('elements', 'tracker'));

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
        if (in_array($element->id, array_keys($used))) {
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
        if ($element->hasoptions() && empty($element->options)) {
            $name .= ' <span class="error">('.get_string('nooptions', 'tracker').')</span>';
        }

        $params = array('id' => $cm->id, 'view' => 'admin', 'what' => 'addelement', 'elementid' => $element->id);
        $url = new moodle_url('/mod/tracker/view.php', $params);
        $pix = '<img src="'.$OUTPUT->pix_url('t/moveleft', 'core') .'" />';
        $actions = '&nbsp;<a href="'.$url.'" title="'.get_string('addtothetracker', 'tracker').'" >'.$pix.'</a>';

        if ($element->type_has_options()) {
            $params = array('id' => $cm->id, 'view' => 'admin', 'what' => 'viewelementoptions', 'elementid' => $element->id);
            $url = new moodle_url('/mod/tracker/view.php', $params);
            $pix = '<img src="'.$OUTPUT->pix_url('editoptions', 'mod_tracker').'" />';
            $actions .= '&nbsp;<a href="'.$url.'" title="'.get_string('editoptions', 'tracker').'">'.$pix.'</a>';
        }

        $params = array('id' => $cm->id,
                        'view' => 'admin',
                        'what' => 'editelement',
                        'elementid' => $element->id,
                        'type' => $element->type);
        $url = new moodle_url('/mod/tracker/view.php', $params);
        $pix = '<img src="'.$OUTPUT->pix_url('t/edit', 'core') .'" />';
        $actions .= '&nbsp;<a href="'.$url.'" title="'.get_string('editproperties', 'tracker').'">'.$pix.'</a>';

        $params = array('id' => $cm->id, 'view' => 'admin', 'what' => 'deleteelement', 'elementid' => $element->id);
        $url = new moodle_url('/mod/tracker/view.php', $params);
        $pix = '<img src="'.$OUTPUT->pix_url('t/delete', 'core') .'" />';
        $actions .= '&nbsp;<a href="'.$url.'" title="'.get_string('delete').'">'.$pix.'</a>';

        $local = '';
        if ($element->course == $COURSE->id) {
            $local = '<img src="'.$OUTPUT->pix_url('i/course', 'core') .'" />';
        }
        $type = "<img src=\"".$OUTPUT->pix_url("types/{$element->type}", 'mod_tracker')."\" />";
        $table->data[] = array($actions, $name, $local, $type);
    }
    echo html_writer::table($table);
} else {
    echo '<center>';
    print_string('noelements', 'tracker');
    echo '<br /></center>';
}
$OUTPUT->box_end();
