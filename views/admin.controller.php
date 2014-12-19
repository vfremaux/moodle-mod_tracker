<?php

/**
* @package mod-tracker
* @category mod
* @author Valery Fremaux > 1.8
* @date 02/12/2007
*
* Controller for all "element management" related views
*
* @usecase 'createelement'
* @usecase 'doaddelement'
* @usecase 'editelement'
* @usecase 'doupdateelement'
* @usecase 'deleteelement'
* @usecase 'submitelementoption'
* @usecase 'viewelementoption'
* @usecase 'editelementoption'
* @usecase 'updateelementoption'
* @usecase 'moveelementoptionup'
* @usecase 'moveelementoptiondown'
* @usecase 'addelement'
* @usecase 'removeelement'
* @usecase 'raiseelement'
* @usecase 'lowerelement'
* @usecase 'localparent'
* @usecase 'remoteparent'
* @usecase 'unbind'
* @usecase 'setinactive'
* @usecase 'setactive'
*/

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from view.php in mod/tracker
}

/************************************* Create element form *****************************/
if ($action == 'createelement'){
	$form = new StdClass;
	$form->type = required_param('type', PARAM_ALPHA);
	// $elementid = optional_param('elementid', null, PARAM_INT);
    $form->action = 'doaddelement';
	include $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/editelement.html';
	return -1;
}
/************************************* add an element *****************************/
elseif ($action == 'doaddelement'){
	$form = new StdClass;
	$form->name = required_param('name', PARAM_ALPHANUM);
	$form->description = required_param('description', PARAM_CLEANHTML);
	$form->type = required_param('type', PARAM_ALPHA);
	$form->shared = optional_param('shared', 0, PARAM_INT);
	$errors = array();
	if (empty($form->name)){
		$error = new StdClass;
	    $error->message = get_string('namecannotbeblank', 'tracker');
	    $error->on = 'name';
	    $errors[] = $error;
	}

    if(!count($errors)){
    	$element = new StdClass;
    	$element->name = $form->name;
    	$element->description = $form->description;
    	$form->type = $element->type = $form->type;
    	$element->course = ($form->shared) ? 0 : $COURSE->id;
    	if (!$form->elementid = $DB->insert_record('tracker_element', $element)){
    		print_error('errorcannotcreateelement', 'tracker', $url);
    	}

        $elementobj = trackerelement::find_instance_by_id($tracker, $form->elementid);
        if ($elementobj->hasoptions()){  // Bounces to the option editor
            $form->name = '';
            $form->description = '';
            
            // prepare use case bounce to further code (later in controller).
            $bounce_elementid = $form->elementid;
            $action = 'viewelementoptions';
    	}
	} else {
        $form->name = '';
        $form->description = '';
		include $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/editelement.html';
    }
}
/************************************* Edit an element form *****************************/
elseif ($action == 'editelement'){
	$form = new StdClass;
	$form->elementid = required_param('elementid', PARAM_INT);
	if ($form->elementid != null){
	    $element = trackerelement::find_instance_by_id($tracker, $form->elementid);
		$form->type = $element->type;
		$form->name = $element->name;
		$form->description = addslashes($element->description);
		$form->format = $element->format;
		$form->shared = ($element->course == 0) ;
		$form->action = 'doupdateelement';
		include $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/editelement.html';
	} else {
		print_error ('errorinvalidelementid', 'tracker', $url);
	}
	return -1;
}
/************************************* Update an element *****************************/
if ($action == 'doupdateelement'){
	$form = new StdClass;
	$form->elementid = required_param('elementid', PARAM_INT);
	$form->name = required_param('name', PARAM_ALPHANUM);
	$form->description = required_param('description', PARAM_CLEANHTML);
	$form->format = optional_param('format', '', PARAM_INT);
	$form->type = required_param('type', PARAM_ALPHA);
	$form->shared = optional_param('shared', 0, PARAM_INT);

	if (empty($form->elementid)){
		print_error('errorelementdoesnotexist', 'tracker', $url);
	}

	$errors = array();
	if (empty($form->name)){
		$error = new StdClass;
	    $error->message = get_string('namecannotbeblank', 'tracker');
	    $error->on = 'name';
	    $errors[] = $error;
	}

    if(!count($errors)){
    	$element = new StdClass;
    	$element->id = $form->elementid;
    	$element->name = $form->name;
    	$element->type = $form->type;
    	$element->description = $form->description;
    	$element->format = $form->format;
    	$element->course = ($form->shared) ? 0 : $COURSE->id ;
    	if (!$DB->update_record('tracker_element', $element)){
    		print_error('errorcannotupdateelement', 'tracker', $url);
    	}
    } else {
    	$form->action = 'doupdateelement';
		include $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/editelement.html';
    }
}
/************************************ delete an element from available **********************/
if ($action == 'deleteelement'){
	$elementid = required_param('elementid', PARAM_INT);
	if(!tracker_iselementused($tracker->id, $elementid)){ 
    	if (!$DB->delete_records ('tracker_element', array('id' =>  $elementid))){	
    		print_error('errorcannotdeleteelement', 'tracker', $url);
    	}
    	$DB->delete_records('tracker_elementitem', array('elementid' => $elementid));
    } else { // should not even be proposed by the GUI
       print_error('errorcannotdeleteelement', 'tracker', $url);
    }
}	
/************************************* add an element option *****************************/
if ($action == 'submitelementoption'){
	$form = new StdClass;
	$form->elementid = required_param('elementid', PARAM_INT);
	$form->name = required_param('name', PARAM_ALPHANUM);
	$form->description = required_param('description', PARAM_CLEANHTML);
	$form->type = required_param('type', PARAM_ALPHA);
	$element = $DB->get_record('tracker_element', array('id' => $form->elementid));
	// check validity
	$errors = array();
	if ($DB->count_records('tracker_elementitem', array('elementid' => $form->elementid, 'name' => $form->name))){
		$error = new StdClass;
	    $error->message = get_string('optionisused', 'tracker', $url);
	    $error->on = 'name';
	    $errors[] = $error;
	}

	if ($form->name == ''){
	    unset($error);
		$error = new StdClass;
	    $error->message = get_string('optionnamecannotbeblank', 'tracker');
	    $error->on = 'name';
	    $errors[] = $error;
	}

	if ($form->description == ''){
	    unset($error);
		$error = new StdClass;
	    $error->message = get_string('descriptionisempty', 'tracker');
	    $error->on = 'description';
	    $errors[] = $error;
	}
	if (!count($errors)){
		$option = new StdClass;
    	$option->name = strtolower($form->name);
    	$option->description = $form->description;
    	$option->elementid = $form->elementid;
        $countoptions = 0 + $DB->count_records('tracker_elementitem', array('elementid' => $form->elementid));
    	$option->sortorder = $countoptions + 1;
    	if (!$DB->insert_record('tracker_elementitem', $option)){
    		print_error('errorcannotcreateelementoption', 'tracker', $url);
    	}
    	$form->name = '';
    	$form->description = '';
    } else {
        /// print errors
        $errorstr = '';
        foreach($errors as $anError){
            $errorstrs[] = $anError->message;
        }
        echo $OUTPUT->box(implode('<br/>', $errorstrs), 'center', '70%', '', 5, 'errorbox');
    }
    echo $OUTPUT->heading(get_string('editoptions', 'tracker'));
    $element = trackerelement::find_instance_by_id($tracker, $form->elementid);
    $element->optionlistview($cm);
    $caption = get_string('addanoption', 'tracker');
    echo $OUTPUT->heading($caption . $OUTPUT->help_icon('options', 'tracker', false));
    include $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/editoptionform.html';
    return -1;
}
/************************************* edit an element option *****************************/
if ($action == 'viewelementoptions'){
	$form = new StdClass();
	$form->elementid = optional_param('elementid', @$bounce_elementid, PARAM_INT);
	if ($form->elementid){
		$element = trackerelement::find_instance_by_id($tracker, $form->elementid);
		$form->type = $element->type;
        echo $OUTPUT->heading(get_string('editoptions', 'tracker'));
        echo '<center>';
        $element = trackerelement::find_instance_by_id($tracker, $form->elementid);
        $element->optionlistview($cm);
        echo $OUTPUT->heading(get_string('addanoption', 'tracker'));
        include $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/editoptionform.html';
        echo '</center>';
	} else {
		print_error('errorcannotviewelementoption', 'tracker', $url);
	}
	return -1;
}
/************************************* delete an element option *****************************/
if ($action == 'deleteelementoption'){
	$form = new StdClass;
	$form->elementid = optional_param('elementid', null, PARAM_INT);
	$form->optionid = required_param('optionid', PARAM_INT);
	$element = trackerelement::getelement($tracker, $form->elementid);
	$deletedoption = $element->getoption($form->optionid);
	$form->type = $element->type;

	if ($DB->get_records('tracker_issueattribute', array('elementitemid' => $form->optionid))){
		print_error('errorcannotdeleteoption', 'tracker');
	}
	if (!$DB->delete_records('tracker_elementitem', array('id' => $form->optionid))){
		print_error('errorcannotdeleteoption', 'tracker');
	}				
	/// renumber higher records
	$sql = "
	    UPDATE
	        {tracker_elementitem}
	    SET
	        sortorder = sortorder - 1
	    WHERE
	        elementid = ? AND
	        sortorder > ?
	";
	$DB->execute($sql, array($form->elementid, $deletedoption->sortorder));
    echo $OUTPUT->heading(get_string('editoptions', 'tracker'));
    $element = trackerelement::find_instance_by_id($tracker, $form->elementid);
    $element->optionlistview($cm);
    $caption = get_string('addanoption', 'tracker');
    echo $OUTPUT->heading($caption . $OUTPUT->help_icon('options', 'tracker', false));
    include $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/editoptionform.html';
    return -1;
}
/************************************* edit an element option *****************************/
if ($action == 'editelementoption'){
	$form = new StdClass;
	$form->elementid = required_param('elementid', PARAM_INT);
	$form->optionid = required_param('optionid', PARAM_INT);
	$element = trackerelement::find_instance_by_id($tracker, $form->elementid);
	$option = $element->getoption($form->optionid);
	$form->type = $element->type;
	$form->name = $option->name;
	$form->description = $option->description;
	include $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/updateoptionform.html';
	return -1;
}
/************************************* edit an element option *****************************/
if ($action == 'updateelementoption'){
	$form = new Stdclass();
	$form->elementid = required_param('elementid', PARAM_INT);
	$form->optionid = required_param('optionid', PARAM_INT);
	$form->name = required_param('name', PARAM_ALPHANUM);
	$form->description = required_param('description', PARAM_CLEANHTML);
	$form->format = optional_param('format', 0, PARAM_INT);

	$element = trackerelement::find_instance_by_id($tracker, $form->elementid);
	$form->type = $element->type;
	// check validity
	$errors = array();
	if ($DB->count_records_select('tracker_elementitem', "elementid = $form->elementid AND name = '$form->name' AND id != $form->optionid ")){
		$error = new StdClass;
	    $error->message = get_string('optionisused', 'tracker');
	    $error->on = 'name';
	    $errors[] = $error;
	}

	if ($form->name == ''){
	    unset($error);
		$error = new StdClass;
	    $error->message = get_string('optionnamecannotbeblank', 'tracker');
	    $error->on = 'name';
	    $errors[] = $error;
	}

	if ($form->description == ''){
	    unset($error);
		$error = new StdClass;
	    $error->message = get_string('descriptionisempty', 'tracker');
	    $error->on = 'description';
	    $errors[] = $error;
	}

    if (!count($errors)){
    	$update = new StdClass;
    	$update->id = $form->optionid;
    	$update->name = $form->name;
    	$update->description = $form->description;
    	$update->format = $form->format;
    	if ($DB->update_record('tracker_elementitem', $update)){
            echo $OUTPUT->heading(get_string('editoptions', 'tracker'));
            $element = trackerelement::find_instance_by_id($tracker, $form->elementid);
            $element->optionlistview($cm);
            echo $OUTPUT->heading(get_string('addanoption', 'tracker'));
	        include $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/editoptionform.html';
    	} else {
    		print_error('errorcannotupdateoptionbecauseused', 'tracker', $url);
    	}
    } else {
        /// print errors
        $errorstr = '';
        foreach($errors as $anError){
            $errorstrs[] = $anError->message;
        }
        echo $OUTPUT->box(implode("<br/>", $errorstrs), 'center', '70%', '', 5, 'errorbox');
	    include $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/updateoptionform.html';
    }
	return -1;
}
/********************************** move an option up in list ***************************/
if ($action == 'moveelementoptionup'){
	$form = new StdClass;
	$form->elementid = required_param('elementid', PARAM_INT);
	$form->optionid = required_param('optionid', PARAM_INT);

    $option = $DB->get_record('tracker_elementitem', array('elementid' => $form->elementid, 'id' => $form->optionid));
    $element = trackerelement::find_instance_by_id($tracker, $form->elementid);
	$form->type = $element->type;
	$option->id = $form->optionid;
	$sortorder = $DB->get_field('tracker_elementitem', 'sortorder', array('elementid' => $form->elementid, 'id' => $form->optionid));
	if ($sortorder > 1){
	    $option->sortorder = $sortorder - 1;
	    $previousoption = new StdClass();
	    $previousoption->id = $DB->get_field('tracker_elementitem', 'id', array('elementid' => $form->elementid, 'sortorder' => $sortorder - 1));
	    $previousoption->sortorder = $sortorder;
	    // swap options in database
    	if (!$DB->update_record('tracker_elementitem', $option)){
    		print_error('errordbupdate', 'tracker', $url);
    	}
    	if (!$DB->update_record('tracker_elementitem', $previousoption)){
    		print_error('errordbupdate', 'tracker', $url);
    	}
	}	
    echo $OUTPUT->heading(get_string('editoptions', 'tracker'));
    $element = trackerelement::find_instance_by_id($tracker, $form->elementid);
    $element->optionlistview($cm);
    $caption = get_string('addanoption', 'tracker');
    echo $OUTPUT->heading($caption . $OUTPUT->help_icon('options', 'tracker', false));
    include $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/editoptionform.html';
    return -1;
}
/********************************** move an option down in list ***************************/
if ($action == 'moveelementoptiondown'){
	$form = new StdClass;
	$form->elementid = required_param('elementid', PARAM_INT);
	$form->optionid = required_param('optionid', PARAM_INT);

    $option = $DB->get_record('tracker_elementitem', array('elementid' => $form->elementid, 'id' => $form->optionid));
    $element = trackerelement::find_instance_by_id($tracker, $form->elementid);
	$form->type = $element->type;
	$option->id = $form->optionid;
	$sortorder = $DB->get_field('tracker_elementitem', 'sortorder', array('elementid' => $form->elementid, 'id' => $form->optionid));
	if ($sortorder < $element->maxorder){
	    $option->sortorder = $sortorder + 1;
	    $nextoption = new StdClass;
	    $nextoption->id = $DB->get_field('tracker_elementitem', 'id', array('elementid' => $form->elementid, 'sortorder' => $sortorder + 1));
	    $nextoption->sortorder = $sortorder;
	    // swap options in database
    	if (!$DB->update_record('tracker_elementitem', addslashes_recursive($option))){
    		print_error('errordbupdate', 'tracker', $url);
    	}
    	if (!$DB->update_record('tracker_elementitem', addslashes_recursive($nextoption))){
    		print_error('errordbupdate', 'tracker', $url);
    	}
    }
    echo $OUTPUT->heading(get_string('editoptions', 'tracker'));
    $element = trackerelement::find_instance_by_id($tracker, $form->elementid);
    $element->optionlistview($cm);
    $caption = get_string('addanoption', 'tracker');
    echo $OUTPUT->heading($caption . $OUTPUT->help_icon('options', 'tracker', false));
    include $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/editoptionform.html';
    return -1;
}
/********************************** add an element to be used ***************************/
if ($action == 'addelement'){
	$elementid = required_param('elementid', PARAM_INT);

	if(!tracker_iselementused($tracker->id, $elementid)){
		/// Add element to element used table;
		$used = new StdClass;
		$used->elementid = $elementid;
		$used->trackerid = $tracker->id;
		$used->canbemodifiedby = $USER->id;
		/// get last sort order
		$sortorder = 0 + $DB->get_field_select('tracker_elementused', 'MAX(sortorder)', "trackerid = {$tracker->id} GROUP BY trackerid");
		$used->sortorder = $sortorder + 1;
		if (!$DB->insert_record ('tracker_elementused', $used)){	
			print_error('errorcannotaddelementtouse', 'tracker', $url.'&amp;view=admin');
		}		
	} else {
		//Feedback message that element is already in uses
		print_error('erroralreadyinuse', 'tracker', $url.'&amp;view=admin');
	}		
}	
/****************************** remove an element from usable list **********************/
if ($action == 'removeelement'){
	$usedid = required_param('usedid', PARAM_INT);
	if (!$DB->delete_records ('tracker_elementused', array('elementid' => $usedid, 'trackerid' => $tracker->id))){	
		print_error('errorcannotdeleteelement', 'tracker', $url);
	}
}
/****************************** raise element pos in usable list **********************/
if ($action == 'raiseelement'){
	$usedid = required_param('elementid', PARAM_INT);
	$used = $DB->get_record('tracker_elementused', array('elementid' => $usedid, 'trackerid' => $tracker->id));
	$previous = $DB->get_record('tracker_elementused', array('sortorder' => $used->sortorder - 1, 'trackerid' => $tracker->id));
    $used->sortorder--;	
    $previous->sortorder++;
    $DB->update_record('tracker_elementused', $used);	
    $DB->update_record('tracker_elementused', $previous);	
}	
/****************************** lower element pos in usable list **********************/
if ($action == 'lowerelement'){
	$usedid = required_param('elementid', PARAM_INT);
	$used = $DB->get_record('tracker_elementused', array('elementid' => $usedid, 'trackerid' => $tracker->id));
	$next = $DB->get_record('tracker_elementused', array('sortorder' => $used->sortorder + 1, 'trackerid' => $tracker->id));
    $used->sortorder++;	
    $next->sortorder--;
    $DB->update_record('tracker_elementused', $used);	
    $DB->update_record('tracker_elementused', $next);	
}	
/*************************** Update parent tracker binding *******************************/	
if ($action == 'localparent'){
    $parent = optional_param('localtracker', null, PARAM_INT);

	if (!$DB->set_field('tracker', 'parent', $parent, array('id' => $tracker->id))){	
		print_error('errorcannotsetparent', 'tracker', $url);
	}
	$tracker->parent = $parent;
}
/*************************** Update remote parent tracker binding *******************************/	
if ($action == 'remoteparent'){
    $step = optional_param('step', 0, PARAM_INT);
    switch($step){
        case 1 : { // we choose the host
            $parenthost = optional_param('remotehost', null, PARAM_RAW);
        }
    	break;
        case 2 : { // we choose the tracker
            $remoteparent = optional_param('remotetracker', null, PARAM_RAW);

        	if (!$DB->set_field('tracker', 'parent', $remoteparent, array('id' => $tracker->id))){	
        		print_error('errorcannotsetparent', 'tracker');
        	}
	    $tracker->parent = $remoteparent;
	    $step = 0;
    	break;
        }
    }
}
/*************************** unbinds any cascade  *******************************/	
if ($action == 'unbind'){
	if (!$DB->set_field('tracker', 'parent', '', array('id' => $tracker->id))){	
		print_error('errorcannotunbindparent', 'tracker', $url);
	}
	$tracker->parent = '';
}
/****************************** set a used element inactive for form **********************/
if ($action == 'setinactive'){
	$usedid = required_param('usedid', PARAM_INT);
	if (!$DB->set_field_select('tracker_elementused', 'active', 0, " elementid = ? && trackerid = ? ", array($usedid, $tracker->id))){	
		print_error('errorcannothideelement', 'tracker', $url);
	}
}	
/****************************** set a used element active for form **********************/
if ($action == 'setactive'){
	$usedid = required_param('usedid', PARAM_INT);
	if (!$DB->set_field_select('tracker_elementused', 'active', 1, " elementid = ? && trackerid = ? ", array($usedid, $tracker->id))){	
		print_error('errorcannotshowelement', 'tracker', $url);
	}
}	

?>