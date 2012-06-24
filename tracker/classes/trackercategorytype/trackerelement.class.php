<?php

/**
* @package tracker
* @review Valery Fremaux
* @version Moodle > 2.2
* @date 02/12/2012
*
* A generic class for collecting all that is common to all elements
*/

class trackerelement{
	var $id;
	var $course;
	var $usedid;
	var $name;
	var $description;
	var $format;
	var $type;
	var $sortorder;
	var $maxorder;
	var $value;
	var $options;
	var $tracker;
	function trackerelement(&$tracker, $elementid=null){
	    $this->id = $elementid;
	    $this->options = null;
		$this->value = null;
		$this->tracker = $tracker;
	}
	function hasoptions(){
		return $this->options !== null;
	}

	function getoption($optionid){
		return $this->options[$optionid];
	}
	function setoptions($options){
		$this->options = $options;
	}

    /**
    *
    *
    */
	function setoptionsfromdb(){
		global $DB;
		
		if (isset($this->id)){
			$this->options = $DB->get_records_select('tracker_elementitem', "elementid={$this->id} AND active = 1 ORDER BY sortorder");
			if ($this->options){
                foreach($this->options as $option){
                    $this->maxorder = max($option->sortorder, $this->maxorder);
                }			
            } else {
                $this->maxorder = 0;
            }
		} else {
			print_error ('errorinvalidelementID', 'tracker');
		}
	}
	/**
	*
	*
	*/
    function getvalue($issueid){
        global $CFG, $DB;
        
        if (!$issueid) return '';
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
    /**
    *
    *
    */
	function addview(){
	}

	function optionlistview($cm){
	    global $CFG, $COURSE, $OUTPUT;	    

        $strname = get_string('name');
        $strdescription = get_string('description');
        $strsortorder = get_string('sortorder', 'tracker');
        $straction = get_string('action');
        $table = new html_table();
        $table->width = "800";
        $table->size = array(100,110,240,75,75);
        $table->head = array('', "<b>$strname</b>","<b>$strdescription</b>","<b>$straction</b>");
        if (!empty($this->options)){
        	foreach ($this->options as $option){
                $actions  = "<a href=\"view.php?id={$cm->id}&amp;what=editelementoption&amp;optionid={$option->id}&amp;elementid={$option->elementid}\" title=\"".get_string('edit')."\"><img src=\"".$OUTPUT->pix_url('/t/edit', 'core')."\" /></a>&nbsp;" ;
                $img = ($option->sortorder > 1) ? 'up' : 'up_shadow' ;
                $actions .= "<a href=\"view.php?id={$cm->id}&amp;what=moveelementoptionup&amp;optionid={$option->id}&amp;elementid={$option->elementid}\" title=\"".get_string('up')."\"><img src=\"".$OUTPUT->pix_url("{$img}", 'mod_tracker')."\"></a>&nbsp;";
                $img = ($option->sortorder < $this->maxorder) ? 'down' : 'down_shadow' ;
                $actions .= "<a href=\"view.php?id={$cm->id}&amp;what=moveelementoptiondown&amp;optionid={$option->id}&amp;elementid={$option->elementid}\" title=\"".get_string('down')."\"><img src=\"".$OUTPUT->pix_url("{$img}", 'mod_tracker')."\"></a>&nbsp;";

                $actions .= "<a href=\"view.php?id={$cm->id}&amp;what=deleteelementoption&amp;optionid={$option->id}&amp;elementid={$option->elementid}\" title=\"".get_string('delete')."\"><img src=\"".$OUTPUT->pix_url('/t/delete', 'core')."\"></a>";
        	    $table->data[] = array('<b> '.get_string('option', 'tracker').' '.$option->sortorder.':</b>',$option->name, format_string($option->description, true, $COURSE->id), $actions);
        	}
        }
        echo html_writer::table($table);
	}

	function editview(){
	    if ($this->type != ''){
    		include_once $CFG->dirroot."/mod/tracker/classes/trackercategorytype/" . $this->type . "/edit" . $this->type . ".html";
    	}
	}

	function viewsearch(){
	    $this->view(true);
	}	

	function viewquery(){
	    $this->view(true);
	}	
}
?>