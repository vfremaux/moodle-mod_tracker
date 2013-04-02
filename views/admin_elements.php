<?php 

/**
* @package mod-tracker
* @category mod
* @author Clifford Tham, Valery Fremaux > 1.8
* @date 02/12/2007
*
* From for showing element list
*/

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from view.php in mod/tracker
}

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
$a  = optional_param('a', 0, PARAM_INT);  // course ID	
$OUTPUT->box_start('center', '100%', '', '', 'generalbox', 'description');
?>
<form name="addelement" method="post" action="view.php">
<table border="0" width="100%">
	<tr>
		<td valign="top">
			<b><?php print_string('createnewelement', 'tracker') ?>:</b>
		</td>
		<td valign="top">
				<?php
					echo "<input type=\"hidden\" name=\"id\" value=\"{$cm->id}\" />";
					echo "<input type=\"hidden\" name=\"what\" value=\"createelement\" />";
		            $types = tracker_getelementtypes();
		            foreach($types as $type){
		                $elementtypesmenu[$type] = get_string($type, 'tracker');
		            }

		            echo html_writer::select($elementtypesmenu, 'type', '', array('' => 'choose'), array('onchange' => 'document.forms[\'addelement\'].submit();'));
				?>
		</td>
	</tr>
</table>
</form>

<?php
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

if (!empty($elements)){
    /// clean list from used elements
    foreach($elements as $id => $element){
        if (in_array($element->id, array_keys($used))){
            unset($elements[$id]);
        }
    }
    /// make list
	foreach ($elements as $element){

		$name = format_string($element->description);
		$name .= '<br />';
		$name .= '<span style="font-size:70%">';
		$name .= $element->name;
		$name .= '</span>';
		if ($element->hasoptions() && empty($element->options)){
		    $name .= ' <span class="error">('.get_string('nooptions', 'tracker').')</span>';
		}
		$actions = "&nbsp;<a href=\"view.php?id={$cm->id}&amp;what=addelement&amp;elementid={$element->id}\" title=\"".get_string('addtothetracker', 'tracker')."\" ><img src=\"".$OUTPUT->pix_url('t/moveleft', 'core') ."\" /></a>";
        $actions .= "&nbsp;<a href=\"view.php?id={$cm->id}&amp;what=viewelementoptions&amp;elementid={$element->id}\" title=\"".get_string('editoptions', 'tracker')."\"><img src=\"".$OUTPUT->pix_url('editoptions', 'mod_tracker')."\" /></a>";
        $actions .= "&nbsp;<a href=\"view.php?id={$cm->id}&amp;what=editelement&amp;elementid={$element->id}\" title=\"".get_string('editproperties', 'tracker')."\"><img src=\"".$OUTPUT->pix_url('t/edit', 'core') ."\" /></a>";
        $actions .= "&nbsp;<a href=\"view.php?id={$cm->id}&amp;what=deleteelement&amp;elementid={$element->id}\" title=\"".get_string('delete')."\"><img src=\"".$OUTPUT->pix_url('t/delete', 'core') ."\" /></a>";

        $local = '';
        if ($element->course == $COURSE->id){
    	    $local = "<img src=\"".$OUTPUT->pix_url('i/course', 'core') ."\" />";
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
?>