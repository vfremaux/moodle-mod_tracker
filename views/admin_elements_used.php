<?php

/**
 * @package mod-tracker
 * @category mod
 * @author Clifford Tham, Valery Fremaux > 1.8
 * @date 02/12/2007
 *
 * From for showing used element list
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    // It must be included from view.php in mod/tracker
}

$OUTPUT->box_start('center', '100%', '', '', 'generalbox', 'description');
$OUTPUT->box_end();
$OUTPUT->box_start('center', '100%', '', '', 'generalbox', 'description');

tracker_loadelementsused($tracker, $used);

echo $OUTPUT->heading(get_string('elementsused', 'tracker'));

$orderstr = get_string('order', 'tracker');
$namestr = get_string('name');
$typestr = get_string('type', 'tracker');
$cmdstr = get_string('action', 'tracker');

$table = new html_table();
$table->head = array("<b>$orderstr</b>", "<b>$namestr</b>", "<b>$typestr</b>", "<b>$cmdstr</b>");
$table->width = '100%';
$table->size = array(20, 250, 50, 100);
$table->align = array('left', 'center', 'center', 'center');

if (!empty($used)) {
    foreach ($used as $element) {
        $icontype = "<img src=\"".$OUTPUT->pix_url("/types/{$element->type}", 'mod_tracker')."\" />";
        if ($element->sortorder > 1) {
            $params = array('id' => $cm->id, 'view' => 'admin', 'what' => 'raiseelement', 'elementid' => $element->id);
            $url = new moodle_url('/mod/tracker/view.php', $params);
            $actions = '&nbsp;<a href="'.$url.'"><img src="'.$OUTPUT->pix_url('/t/up', 'core').'" /></a>';
        } else {
            $actions = '&nbsp;<img src="'.$OUTPUT->pix_url('up_shadow', 'mod_tracker').'" />';
        }
        if ($element->sortorder < count($used)) {
            $params = array('id' => $cm->id, 'view' => 'admin', 'what' => 'lowerelement', 'elementid' => $element->id);
            $url = new moodle_url('/mod/tracker/view.php', $params);
            $actions .= '&nbsp;<a href="'.$url.'"><img src="'.$OUTPUT->pix_url('/t/down', 'core').'" /></a>';
        } else {
            $actions .= '&nbsp;<img src="'.$OUTPUT->pix_url('down_shadow', 'mod_tracker').'" />';
        }

        $params = array('id' => $cm->id, 'view' => 'admin', 'what' => 'editelement', 'elementid' => $element->id, 'used' => 1, 'type' => $element->type);
        $url = new moodle_url('/mod/tracker/view.php', $params);
        $actions .= '&nbsp;<a href="'.$url.'"><img src="'.$OUTPUT->pix_url('/t/edit', 'core').'" /></a>';

        if ($element->type_has_options()) {
            $params = array('id' => $cm->id, 'view' => 'admin', 'what' => 'viewelementoptions', 'elementid' => $element->id);
            $url = new moodle_url('/mod/tracker/view.php', $params);
            $actions .= '&nbsp;<a href="'.$url.'" title="'.get_string('editoptions', 'mod_tracker').'"><img src="'.$OUTPUT->pix_url('editoptions', 'mod_tracker').'" /></a>';
        }

        $params = array('id' => $cm->id, 'view' => 'admin', 'what' => 'removeelement', 'usedid' => $element->id);
        $url = new moodle_url('/mod/tracker/view.php', $params);
        $actions .= '&nbsp;<a href="'.$url.'"><img src="'.$OUTPUT->pix_url('/t/right', 'core').'" /></a>';

        if ($element->active) {
            if (!$element->mandatory) {
                $params = array('id' => $cm->id, 'view' => 'admin', 'what' => 'setinactive', 'usedid' => $element->id);
                $url = new moodle_url('/mod/tracker/view.php', $params);
                $actions .= '&nbsp;<a href="'.$url.'" title="'.get_string('setinactive', 'tracker').'"><img src="'.$OUTPUT->pix_url('/t/hide', 'core').'" /></a>';
            } else {
                $actions .= '&nbsp;<img class="dimmed" src="'.$OUTPUT->pix_url('/t/hide', 'core').'" />';
            }
        } else {
            $params = array('id' => $cm->id, 'view' => 'admin', 'what' => 'setactive', 'usedid' => $element->id);
            $url = new moodle_url('/mod/tracker/view.php', $params);
            $actions .= '&nbsp;<a href="'.$url.'" title="'.get_string('setactive', 'tracker').'"><img src="'.$OUTPUT->pix_url('/t/show', 'core').'" /></a>';
        }

        if ($element->has_mandatory_option()) {
            if ($element->mandatory) {
                $params = array('id' => $cm->id, 'view' => 'admin', 'what' => 'setnotmandatory', 'usedid' => $element->id);
                $url = new moodle_url('/mod/tracker/view.php', $params);
                $actions .= '&nbsp;<a href="'.$url.'" title="'.get_string('setnotmandatory', 'tracker').'"><img src="'.$OUTPUT->pix_url('notempty', 'tracker').'" /></a>';
            } else {
                $params = array('id' => $cm->id, 'view' => 'admin', 'what' => 'setmandatory', 'usedid' => $element->id);
                $url = new moodle_url('/mod/tracker/view.php', $params);
                $actions .= '&nbsp;<a href="'.$url.'" title="'.get_string('setmandatory', 'tracker').'"><img src="'.$OUTPUT->pix_url('empty', 'tracker').'" /></a>';
            }
        } else {
            if ($element->mandatory) {
                $actions .= '&nbsp;<img src="'.$OUTPUT->pix_url('notempty', 'tracker').'" />';
            } else {
                $actions .= '&nbsp;<img src="'.$OUTPUT->pix_url('empty', 'tracker').'" />';
            }
        }

        if ($element->has_private_option()) {
            if ($element->private) {
                $params = array('id' => $cm->id, 'view' => 'admin', 'what' => 'setpublic', 'usedid' => $element->id);
                $url = new moodle_url('/mod/tracker/view.php', $params);
                $actions .= '&nbsp;<a href="'.$url.'" title="'.get_string('setpublic', 'tracker').'"><img src="'.$OUTPUT->pix_url('t/locked', 'core').'" /></a>';
            } else {
                if (!$element->mandatory) {
                    $params = array('id' => $cm->id, 'view' => 'admin', 'what' => 'setprivate', 'usedid' => $element->id);
                    $url = new moodle_url('/mod/tracker/view.php', $params);
                    $actions .= '&nbsp;<a href="'.$url.'" title="'.get_string('setprivate', 'tracker').'"><img src="'.$OUTPUT->pix_url('t/lock', 'core').'" /></a>';
                } else {
                    $actions .= '&nbsp;<img class="dimmed" src="'.$OUTPUT->pix_url('t/lock', 'core').'" />';
                }
            }
        } else {
            if ($element->private) {
                $actions .= '&nbsp;<img src="'.$OUTPUT->pix_url('t/locked', 'core').'" />';
            } else {
                $actions .= '&nbsp;<img src="'.$OUTPUT->pix_url('t/lock', 'core').'" />';
            }
        }

        $description = ($element->private) ? '<span class="dimmed">'.format_string($element->description).'</span>' : format_string($element->description) ;

        $table->data[] = array($element->sortorder, $description, $icontype, $actions);
    }
    echo html_writer::table($table);
} else {
    echo '<center>';
    print_string('noelements', 'tracker');
    echo '<br/></center>';
}

$OUTPUT->box_end();

