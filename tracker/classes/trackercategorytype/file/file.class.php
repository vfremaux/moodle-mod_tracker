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
	    global $CFG, $COURSE;
        $this->getvalue($issueid);
	    if ($editable){
	        upload_print_form_fragment(1, array('element'.$this->name));
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
            	} else {
            	    print_string('nofile', 'tracker');
            	}
            }
	    }   
	}
}
?>
