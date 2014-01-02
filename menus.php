<?php

	if ($screen == 'mytickets'){
		$totalissues = $DB->count_records_select('tracker_issue', "trackerid = ? AND status <> ".RESOLVED." AND status <> ".ABANDONNED." AND reportedby = ? ", array($tracker->id, $USER->id));
		$totalresolvedissues = $DB->count_records_select('tracker_issue', "trackerid = ? AND (status = ".RESOLVED." OR status = ".ABANDONNED.") AND reportedby = ? ", array($tracker->id, $USER->id));
	} elseif ($screen == 'mywork'){
		$totalissues = $DB->count_records_select('tracker_issue', "trackerid = ? AND status <> ".RESOLVED." AND status <> ".ABANDONNED." AND assignedto = ? ", array($tracker->id, $USER->id));
		$totalresolvedissues = $DB->count_records_select('tracker_issue', "trackerid = ? AND (status = ".RESOLVED." OR status = ".ABANDONNED.") AND assignedto = ? ", array($tracker->id, $USER->id));
	} else {
		$totalissues = $DB->count_records_select('tracker_issue', "trackerid = ? AND status <> ".RESOLVED." AND status <> ".ABANDONNED, array($tracker->id));
		$totalresolvedissues = $DB->count_records_select('tracker_issue', "trackerid = ? AND (status = ".RESOLVED." OR status = ".ABANDONNED.")", array($tracker->id));
	}

	/// Print tabs with options for user
	if (has_capability('mod/tracker:report', $context)){
		$rows[0][] = new tabobject('reportanissue', "reportissue.php?id={$cm->id}", get_string('newissue', 'tracker'));
	}

	$rows[0][] = new tabobject('view', "view.php?id={$cm->id}&amp;view=view", get_string('view', 'tracker').' ('.$totalissues.' '.get_string('issues','tracker').')');

	$rows[0][] = new tabobject('resolved', "view.php?id={$cm->id}&amp;view=resolved", get_string('resolvedplural', 'tracker').' ('.$totalresolvedissues.' '.get_string('issues','tracker').')');

	$rows[0][] = new tabobject('profile', "view.php?id={$cm->id}&amp;view=profile", get_string('profile', 'tracker'));

	if (has_capability('mod/tracker:viewreports', $context)){
		$rows[0][] = new tabobject('reports', "view.php?id={$cm->id}&amp;view=reports", get_string('reports', 'tracker'));
	}

	if (has_capability('mod/tracker:configure', $context)){
	    $rows[0][] = new tabobject('admin', "view.php?id={$cm->id}&amp;view=admin", get_string('administration', 'tracker'));
	}
	
	/// 
	$myticketsstr = ($tracker->supportmode != 'taskspread') ? get_string('mytickets', 'tracker') : get_string('mytasks', 'tracker');
		
	
	/// submenus
	$selected = null;
	$activated = null;
	switch ($view){
	    case 'view' :
	        if (!preg_match("/mytickets|mywork|browse|search|viewanissue|editanissue/", $screen)) $screen = 'mytickets';
			if (has_capability('mod/tracker:report', $context)){
		        $rows[1][] = new tabobject('mytickets', "view.php?id={$cm->id}&amp;view=view&amp;screen=mytickets", $myticketsstr);
		    }
			if (tracker_has_assigned($tracker, false)){
		        $rows[1][] = new tabobject('mywork', "view.php?id={$cm->id}&amp;view=view&amp;screen=mywork", get_string('mywork', 'tracker'));
		    }
	        if (has_capability('mod/tracker:viewallissues', $context) || $tracker->supportmode == 'bugtracker'){
	            $rows[1][] = new tabobject('browse', "view.php?id={$cm->id}&amp;view=view&amp;screen=browse", get_string('browse', 'tracker'));
	        }
	        if ($tracker->supportmode == 'bugtracker'){
		        $rows[1][] = new tabobject('search', "view.php?id={$cm->id}&amp;view=view&amp;screen=search", get_string('search', 'tracker'));
		    }
	        break;
	    case 'resolved' :
	        if (!preg_match("/mytickets|browse|mywork/", $screen)) $screen = 'mytickets';
			if (has_capability('mod/tracker:report', $context)){
		        $rows[1][] = new tabobject('mytickets', "view.php?id={$cm->id}&amp;view=resolved&amp;screen=mytickets", $myticketsstr);
		    }
			if (tracker_has_assigned($tracker, true)){
		        $rows[1][] = new tabobject('mywork', "view.php?id={$cm->id}&amp;view=view&amp;screen=mywork", get_string('mywork', 'tracker'));
		    }
	        if (has_capability('mod/tracker:viewallissues', $context) || $tracker->supportmode == 'bugtracker'){
	            $rows[1][] = new tabobject('browse', "view.php?id={$cm->id}&amp;view=resolved&amp;screen=browse", get_string('browse', 'tracker'));
	        }
	    break;
	    case 'profile':
	        if (!preg_match("/myprofile|mypreferences|mywatches|myqueries/", $screen)) $screen = 'myprofile';
	        $rows[1][] = new tabobject('myprofile', "view.php?id={$cm->id}&amp;view=profile&amp;screen=myprofile", get_string('myprofile', 'tracker'));
	        $rows[1][] = new tabobject('mypreferences', "view.php?id={$cm->id}&amp;view=profile&amp;screen=mypreferences", get_string('mypreferences', 'tracker'));
	        $rows[1][] = new tabobject('mywatches', "view.php?id={$cm->id}&amp;view=profile&amp;screen=mywatches", get_string('mywatches', 'tracker'));
	        if ($tracker->supportmode == 'bugtracker'){
		        $rows[1][] = new tabobject('myqueries', "view.php?id={$cm->id}&amp;view=profile&amp;screen=myqueries", get_string('myqueries', 'tracker'));
		    }
	    break;
	    case 'reports':
	        if (!preg_match("/status|evolution|print/", $screen)) $screen = 'status';
	        $rows[1][] = new tabobject('status', "view.php?id={$cm->id}&amp;view=reports&amp;screen=status", get_string('status', 'tracker'));
	        $rows[1][] = new tabobject('evolution', "view.php?id={$cm->id}&amp;view=reports&amp;screen=evolution", get_string('evolution', 'tracker'));
	        $rows[1][] = new tabobject('print', "view.php?id={$cm->id}&amp;view=reports&amp;screen=print", get_string('print', 'tracker'));
	    break;
	    case 'admin':
	        if (!preg_match("/summary|manageelements|managenetwork/", $screen)) $screen = 'summary';
	        $rows[1][] = new tabobject('summary', "view.php?id={$cm->id}&amp;view=admin&amp;screen=summary", get_string('summary', 'tracker'));
	        $rows[1][] = new tabobject('manageelements', "view.php?id={$cm->id}&amp;view=admin&amp;screen=manageelements", get_string('manageelements', 'tracker'));
			if (has_capability('mod/tracker:configurenetwork', $context)){
	        	$rows[1][] = new tabobject('managenetwork', "view.php?id={$cm->id}&amp;view=admin&amp;screen=managenetwork", get_string('managenetwork', 'tracker'));
	        }
	        break;
	    default:
	}
	if (!empty($screen)){
	    $selected = $screen;
	    $activated = array($view);
	} else {
	    $selected = $view;
	}
	echo $OUTPUT->container_start('mod-header');
	print_tabs($rows, $selected, '', $activated);
	echo '<br/>';
	echo $OUTPUT->container_end();


