<?php

/**
* @package tracker
* @author Clifford Tham
* @review Valery Fremaux / 1.8
* @date 17/12/2007
*
* A class implementing a textfield element
*/

include_once $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/trackerelement.class.php';
require_once $CFG->libdir.'/uploadlib.php';

class fileelement extends trackerelement{

	var $filemanageroptions;
	
	function __construct($tracker, $id = null, $used = false){
		global $COURSE;

	    parent::__construct($tracker, $id, $used);
	    $this->filemanageroptions = array('subdirs' => 0, 'maxfiles' => 1, 'maxbytes' => $COURSE->maxbytes, 'accepted_types' => array('*'));
	}

	// no care of real value of element. 
	// there is some file stored into the file area or not.
	function view($issueid = 0){
	    global $CFG, $COURSE, $DB;
	    
	    $elmname = 'element'.$this->name;
	    
	    $issue = $DB->get_record('tracker_issue', array('id' => "$issueid"));
	    $attribute = $DB->get_record('tracker_issueattribute', array('issueid' => $issueid, 'elementid' => $this->id));
	    
	    if ($attribute){
			$fs = get_file_storage();
			
			$imagefiles = $fs->get_area_files($this->context->id, 'mod_tracker', 'issueattribute', $attribute->id);

			if(empty($imagefiles)){
				$html = html_writer::start_tag('span', array('class' => 'tracker-file-item-notice'));
			    $html .= get_string('nofileloaded', 'tracker');
			    $html .= html_writer::end_tag('span');
			    return $html;
			}

			$imagefile = array_pop($imagefiles);
		    $filename = $imagefile->get_filename();
		    $filearea = $imagefile->get_filearea();
		    $itemid = $imagefile->get_itemid();

		    $fileurl = $CFG->wwwroot."/pluginfile.php/{$this->context->id}/mod_tracker/{$filearea}/{$itemid}/{$filename}";
		
    	    if (preg_match("/\.(jpg|gif|png|jpeg)$/i", $filename)){
    		    return "<img style=\"max-width:600px\" src=\"{$fileurl}\" class=\"tracker_image_attachment\" />";
    	    } else {
		    	return html_writer::link($fileurl, $filename);
    	    }
        } else {
			$html = html_writer::start_tag('span', array('class' => 'tracker-file-item-notice'));
		    $html .= get_string('nofileloaded', 'tracker');
		    $html .= html_writer::end_tag('span');
		    return $html;
        }
	}

	function edit($issueid = 0){
		global $COURSE, $OUTPUT, $DB, $PAGE;
		
		if ($attribute = $DB->get_record('tracker_issueattribute', array('elementid' => $this->id, 'issueid' => $issueid))){
			$itemid = $attribute->id;
		} else {
			$itemid = 0;
		}

		$draftitemid = 0; // drafitemid will be filled when preparing new area.
	    file_prepare_draft_area($draftitemid, $this->context->id, 'mod_tracker', 'issueattribute', $itemid, $this->filemanageroptions);
		
		$options = new StdClass();
		$options->accepted_types = $this->filemanageroptions['accepted_types'];
        $options->itemid = $draftitemid;
        $options->maxbytes = $this->filemanageroptions['maxbytes'];
        $options->maxfiles = $this->filemanageroptions['maxfiles'];
        $options->elementname = 'element'.$this->name;

		$fp = new file_picker($options);
		
		$html = $OUTPUT->render($fp);
        $html .= '<input type="hidden" name="element'.$this->name.'" id="id_'.$this->name.'" value="'.$draftitemid.'" class="filepickerhidden"/>';

        $module = array('name'=>'form_filepicker', 'fullpath'=>'/lib/form/filepicker.js', 'requires' => array('core_filepicker', 'node', 'node-event-simulate', 'core_dndupload'));
        $PAGE->requires->js_init_call('M.form_filepicker.init', array($fp->options), true, $module);

        $nonjsfilepicker = new moodle_url('/repository/draftfiles_manager.php', array(
            'env' => 'filepicker',
            'action' => 'browse',
            'itemid' => $draftitemid,
            'subdirs' => 0,
            'maxbytes' => $this->filemanageroptions['maxbytes'],
            'maxfiles' => 1,
            'ctx_id' => $this->context->id,
            'course' => $PAGE->course->id,
            'sesskey' => sesskey(),
            ));

        // non js file picker
        $html .= '<noscript>';
        $html .= "<div><object type='text/html' data='$nonjsfilepicker' height='160' width='600' style='border:1px solid #000'></object></div>";
        $html .= '</noscript>';
        
        echo $html;
	}
	
	function add_form_element(&$form){
		global $COURSE;
		
		$form->addElement('header', "head{$this->name}", $this->description);
		$form->addElement('filepicker', 'element'.$this->name, '', null, $this->options);
	}
	
	function set_data($defaults){
		global $COURSE;
		
		$elmname = 'element'.$this->name;
		$draftitemid = file_get_submitted_draft_itemid($elmname);
		$maxbytes = $COURSE->maxbytes;
		file_prepare_draft_area($draftitemid, $this->context->id, 'mod_tracker', 'issueattribute', $this->id, $this->filemanageroptions);
		$defaults->$elmname = $draftitemid;
	}

	/**
	* used for post processing form values, or attached files management
	*/
	function formprocess(&$data){
		global $COURSE, $USER, $DB;
		
		if (!$attribute = $DB->get_record('tracker_issueattribute', array('elementid' => $this->id, 'trackerid' => $data->trackerid, 'issueid' => $data->issueid))){
			$attribute = new StdClass();
			$attribute->trackerid = $data->trackerid;
			$attribute->issueid = $data->issueid;
			$attribute->elementid = $this->id;
		}
				
		$attribute->elementitemid = ''; // value is meaning less. we jsut need the attribute record as storage itemid
		$attribute->timemodified = time();

		if (!isset($attribute->id)){
			$attribute->id = $DB->insert_record('tracker_issueattribute', $attribute);
			if (empty($attribute->id)){
				print_error('erroraddissueattribute', 'tracker', '', 2);
			}
		} else {
			$DB->update_record('tracker_issueattribute', $attribute);
		}
		
		$elmname = 'element'.$this->name;
		$data->$elmname = optional_param($elmname, 0, PARAM_INT);

		if ($data->$elmname){
			file_save_draft_area_files($data->$elmname, $this->context->id, 'mod_tracker', 'issueattribute', 0 + $attribute->id, $this->filemanageroptions);
		}
	}
}
?>
