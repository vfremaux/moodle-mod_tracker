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
    die('Direct access to this script is forbidden.');    ///  It must be included from view.php in mod/tracker
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

if (!empty($used)){
	foreach ($used as $element){
	    $icontype = "<img src=\"".$OUTPUT->pix_url("/types/{$element->type}", 'mod_tracker')."\" />";
	    if ($element->sortorder > 1){
    	    $actions = "&nbsp;<a href=\"view.php?id={$cm->id}&amp;what=raiseelement&amp;elementid={$element->id}\"><img src=\"".$OUTPUT->pix_url('/t/up', 'core')."\" /></a>";
    	} else {
    	    $actions = "<img src=\"".$OUTPUT->pix_url('up_shadow', 'mod_tracker')."\" />";
    	}
    	if ($element->sortorder < count($used)){
    	    $actions .= "&nbsp;<a href=\"view.php?id={$cm->id}&amp;what=lowerelement&amp;elementid={$element->id}\"><img src=\"".$OUTPUT->pix_url('/t/down', 'core')."\" /></a>";
    	} else {
    	    $actions .= "<img src=\"".$OUTPUT->pix_url('down_shadow', 'mod_tracker')."\" />";
    	}
	    $actions .= "<a href=\"view.php?id={$cm->id}&amp;what=editelement&amp;elementid={$element->id}\"><img src=\"".$OUTPUT->pix_url('/t/edit', 'core')."\" /></a>";
		
	    $actions .= "&nbsp;<a href=\"view.php?id={$cm->id}&amp;what=viewelementoptions&amp;elementid={$element->id}\" title=\"".get_string('editoptions', 'mod_tracker')."\"><img src=\"".$OUTPUT->pix_url('editoptions', 'mod_tracker')."\" /></a>";
	    $actions .= "&nbsp;<a href=\"view.php?id={$cm->id}&amp;what=removeelement&amp;usedid={$element->id}\"><img src=\"".$OUTPUT->pix_url('/t/right', 'core')."\" /></a>";
	    if ($element->active){
		    $actions .= "&nbsp;<a href=\"view.php?id={$cm->id}&amp;what=setinactive&amp;usedid={$element->id}\"><img src=\"".$OUTPUT->pix_url('/t/hide', 'core')."\" /></a>";
	    } else {
		    $actions .= "&nbsp;<a href=\"view.php?id={$cm->id}&amp;what=setactive&amp;usedid={$element->id}\"><img src=\"".$OUTPUT->pix_url('/t/show', 'core')."\" /></a>";
	    }
        $table->data[] = array($element->sortorder, format_string($element->description), $icontype, $actions);
    }
    echo html_writer::table($table);
} else {
    echo '<center>';
    print_string('noelements', 'tracker');
    echo '<br/></center>';
}

$OUTPUT->box_end(); 

?>