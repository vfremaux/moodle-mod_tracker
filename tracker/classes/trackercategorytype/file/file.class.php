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
	function fileelement($tracker, $id=null){
	    $this->trackerelement($tracker, $id, null);
	}

	function view($editable, $issueid=0){
	    global $CFG, $COURSE, $DB;
        $this->getvalue($issueid);
	    if ($editable){
	        echo "<input type=\"file\" name=\"element{$this->name}\" />";
	        if (!empty($this->value)){
        	    $issue = $DB->get_record('tracker_issue', array('id' => "$issueid"));
        	    $unstampedvalue = preg_replace("/[^_]+_/", '', $this->value); // strip off the md5 stamp

                if ($CFG->slasharguments){
        		    $filepath = "{$CFG->wwwroot}/file.php/{$COURSE->id}/moddata/tracker/{$issue->trackerid}/{$issue->id}/{$this->value}";
        		} else {
        		    $filepath = "{$CFG->wwwroot}/file.php?file=/{$COURSE->id}/moddata/tracker/{$issue->trackerid}/{$issue->id}/{$this->value}";
        		}
        	    if (preg_match("/\.(jpg|gif|png|jpeg)$/i", $unstampedvalue)){
        		    echo "<img src=\"{$filepath}\" class=\"tracker_image_attachment\" />";
        	    } else {
        		    echo "<a href=\"{$filepath}\">{$unstampedvalue}</a>";
        	    }
    	        echo "<br/><input type=\"checkbox\" name=\"deleteelement{$this->name}\" value=\"1\" /> ";
    	        print_string('deleteattachedfile', 'tracker');
    	    }
		} else {
    	    $issue = $DB->get_record('tracker_issue', array('id' => "$issueid"));
    	    if ($issue){
    		    if (!empty($this->value)){
					$fs = get_file_storage();
					
					$context = context_module::instance($issue->trackerid);

					// Prepare file record object
					$fileinfo = array(
					    'component' => 'mod_tracker',     // usually = table name
					    'filearea' => 'attachment',     // usually = table name
					    'itemid' => $issue->id,               // usually = ID of row in table
					    'contextid' => $context->id, // ID of context
					    'filepath' => '/',           // any path beginning and ending in /
					    'filename' => $this->value); // any filename
					 
					// Get file
					$file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
					                      $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);

					$url = "{$CFG->wwwroot}/pluginfile.php/{$file->get_contextid()}/mod_tracker/attachment}";
				    $filename = $file->get_filename();
				    $fileurl = $url.$file->get_filepath().$file->get_itemid().'/'.$filename;

            	    if (preg_match("/\.(jpg|gif|png|jpeg)$/i", $this->value)){
            		    echo "<img src=\"{$fileurl}\" class=\"tracker_image_attachment\" />";
            	    } else {
				    	echo html_writer::link($fileurl, $filename);
            	    }
            	} else {
            	    print_string('nofile', 'tracker');
            	}
            }
	    }   
	}
}
?>
