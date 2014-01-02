<?php

function xmldb_tracker_upgrade($oldversion=0) {
/// This function does anything necessary to upgrade 
/// older versions to match current functionality 

    global $CFG, $DB;

	$dbman = $DB->get_manager();

    $result = true;
    
    if ($oldversion < 2008091900) {
    
    /// Define field parent to be added to tracker
        $table = new xmldb_table('tracker');
        $field = new xmldb_field('parent');
        $field->set_attributes(XMLDB_TYPE_CHAR, '80', null, null, null, null, 'timemodified');

    /// Launch add field parent
        $dbman->add_field($table, $field);

        /// tracker savepoint reached
        upgrade_mod_savepoint(true, 2008091900, 'tracker');
    }

    if ($result && $oldversion < 2008092400) {

        // setup XML-RPC services for tracker
        
        if (!$DB->get_record('mnet_service', array('name' => 'tracker_cascade'))){
            $service->name = 'tracker_cascade';
            $service->description = get_string('transferservice', 'tracker');
            $service->apiversion = 1;
            $service->offer = 1;
            if (!$serviceid = $DB->insert_record('mnet_service', $service)){
                echo $OUTPUT->notification('Error installing tracker_cascade service.');
                $result = false;
            }
            
            $rpc->function_name = 'tracker_rpc_get_instances';
            $rpc->xmlrpc_path = 'mod/tracker/rpclib.php/tracker_rpc_get_instances';
            $rpc->parent_type = 'mod';  
            $rpc->parent = 'tracker';
            $rpc->enabled = 0; 
            $rpc->help = 'Get instances of available trackers for cascading.';
            $rpc->profile = '';
            if (!$rpcid = $DB->insert_record('mnet_rpc', $rpc)){
                echo $OUTPUT->notification('Error installing tracker_cascade RPC calls.');
                $result = false;
            }
            $rpcmap->serviceid = $serviceid;
            $rpcmap->rpcid = $rpcid;
            $DB->insert_record('mnet_service2rpc', $rpcmap);
            
            $rpc->function_name = 'tracker_rpc_get_infos';
            $rpc->xmlrpc_path = 'mod/tracker/rpclib.php/tracker_rpc_get_infos';
            $rpc->parent_type = 'mod';  
            $rpc->parent = 'tracker';
            $rpc->enabled = 0; 
            $rpc->help = 'Get information about one tracker.';
            $rpc->profile = '';
            if (!$rpcid = $DB->insert_record('mnet_rpc', $rpc)){
                echo $OUTPUT->notification('Error installing tracker_cascade RPC calls.');
                $result = false;
            }
            $rpcmap->rpcid = $rpcid;
            $DB->insert_record('mnet_service2rpc', $rpcmap);

            $rpc->function_name = 'tracker_rpc_post_issue';
            $rpc->xmlrpc_path = 'mod/tracker/rpclib.php/tracker_rpc_post_issue';
            $rpc->parent_type = 'mod';  
            $rpc->parent = 'tracker';
            $rpc->enabled = 0; 
            $rpc->help = 'Cascades an issue.';
            $rpc->profile = '';
            if (!$rpcid = $DB->insert_record('mnet_rpc', $rpc)){
                echo $OUTPUT->notification('Error installing tracker_cascade RPC calls.');
                $result = false;
            }
            $rpcmap->rpcid = $rpcid;
            $DB->insert_record('mnet_service2rpc', $rpcmap);
        }

        /// tracker savepoint reached
        upgrade_mod_savepoint(true, 2008092400, 'tracker');
    }

    if ($result && $oldversion < 2008092602) {

    /// Define field supportmode to be added to tracker
        $table = new xmldb_table('tracker');
        $field = new xmldb_field('supportmode');
        $field->set_attributes(XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'bugtracker', 'parent');

    /// Launch add field supportmode
        $dbman->add_field($table, $field);

        /// tracker savepoint reached
        upgrade_mod_savepoint(true, 2008092602, 'tracker');
    }

    if ($result && $oldversion < 2009042500) {

    /// Define field supportmode to be added to tracker
        $table = new xmldb_table('tracker_issue');
        $field = new xmldb_field('resolutionpriority');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, 'resolutionformat');

    /// Launch add field supportmode
        $dbman->add_field($table, $field);

        /// tracker savepoint reached
        upgrade_mod_savepoint(true, 2009042500, 'tracker');
    }

    if ($result && $oldversion < 2009042503) {

        /// Reassign all priorities
        require_once($CFG->dirroot.'/mod/tracker/locallib.php');
        $trackers = $DB->get_records('tracker', array());
        if ($trackers){
            foreach($trackers as $tracker){
                $issues = $DB->get_records('tracker_issue', array('trackerid' => $tracker->id));
                if ($issues){
                    $priority = 1;
                    foreach($issues as $issue){
                        // set once at upgrade and never again.
                        if ($issue->status < RESOLVED){
                            $issue->resolutionpriority = $priority;
                            $priority++;
                            $DB->update_record('tracker_issue', $issue);
                        }
                    }
                }
            }
        }

        /// Add comment enabling to all events
        $ccs = $DB->get_records('tracker_issuecc');
        if ($ccs){
            foreach($ccs as $cc){
                $cc->events |= ON_COMMENT;
                $DB->update_record('tracker_issuecc', $cc);
            }
        }

        /// tracker savepoint reached
        upgrade_mod_savepoint(true, 2009042503, 'tracker');
    }

    // fix field size for parent encoding in remote cascade. (long wwwroots) 
    if ($result && $oldversion < 2009090800) {

    /// Changing precision of field parent on table tracker to (80)
        $table = new xmldb_table('tracker');
        $field = new xmldb_field('parent');
        $field->set_attributes(XMLDB_TYPE_CHAR, '80', null, null, null, null, 'timemodified');

    /// Launch change of precision for field parent
        $dbman->change_field_precision($table, $field);

        /// tracker savepoint reached
        upgrade_mod_savepoint(true, 2009090800, 'tracker');
    }

 if ($result && $oldversion < 2010061000) {

    /// Define field defaultassignee to be added to tracker
        $table = new xmldb_table('tracker');
        $field = new xmldb_field('defaultassignee');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'supportmode');

    /// Launch add field defaultassignee
        $dbman->add_field($table, $field);

        /// tracker savepoint reached
        upgrade_mod_savepoint(true, 2010061000, 'tracker');
    }

	if ($result && $oldversion < 2011070400) {

    /// Define field subtrackers to be added to tracker
        $table = new xmldb_table('tracker');
        $field = new xmldb_field('subtrackers');
        $field->set_attributes(XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, '0', 'defaultassignee');

    /// Launch add field subtrackers
        $dbman->add_field($table, $field);

        /// tracker savepoint reached
        upgrade_mod_savepoint(true, 2011070400, 'tracker');
    }

	// Moodle 2.x break line : All new changes to Moodle 1.9 version should remain under this timestamp.     
	
	// Unconditionnally perform Moodle 1.9 => Moodle 2 if necessary for every upgrade.

    // Rename description field to intro, and define field introformat to be added to tracker
    $table = new xmldb_table('tracker');
    $introfield = new xmldb_field('description', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'name');
    if ($dbman->field_exists($table, $introfield)){
        $dbman->rename_field($table, $introfield, 'intro', false);
        
        $formatfield = new xmldb_field('format', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'intro');
        $dbman->rename_field($table, $formatfield, 'introformat', false);
    }
	
    // conditionally migrate to html format in intro
    /*
    // weird column text compare error....
    if ($CFG->texteditors !== 'textarea') {
        if ($trackers = $DB->get_records('tracker', array('introformat' => FORMAT_MOODLE),'', 'id, intro, introformat')){
	        foreach ($trackers as $t) {
	            $t->intro       = text_to_html($t->intro, false, false, true);
	            $t->introformat = FORMAT_HTML;
	            $DB->update_record('tracker', $t);
	            upgrade_set_timeout();
	        }
	    }
    }
    */

	// Moodle 2.x      

	if ($result && $oldversion < 2013092200) {

    /// Define field subtrackers to be added to tracker
		$table = new xmldb_table('tracker_issue');
		$field = new xmldb_field('format', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'description');
		if ($dbman->field_exists($table, $field)){
			$dbman->rename_field($table, $field, 'descriptionformat', false);
		}
        /// tracker savepoint reached
        upgrade_mod_savepoint(true, 2013092200, 'tracker');
	}

	if ($result && $oldversion < 2013092300) {

    /// Define field subtrackers to be added to tracker
		$table = new xmldb_table('tracker');
		$field = new xmldb_field('enabledstates', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '511', 'subtrackers');
		if (!$dbman->field_exists($table, $field)){
			$dbman->add_field($table, $field);
		}
        /// tracker savepoint reached
        upgrade_mod_savepoint(true, 2013092300, 'tracker');
	}

	if ($result && $oldversion < 2013092400) {

    /// Define field subtrackers to be added to tracker
		$table = new xmldb_table('tracker');
		$field = new xmldb_field('thanksmessage', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'enabledstates');
		if (!$dbman->field_exists($table, $field)){
			$dbman->add_field($table, $field);
		}
        /// tracker savepoint reached
        upgrade_mod_savepoint(true, 2013092400, 'tracker');
	}
    
	if ($result && $oldversion < 2014010100) {

    /// Define field strictworkflow to be added to tracker
		$table = new xmldb_table('tracker');
		$field = new xmldb_field('strictworkflow', XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'thanksmessage');
		if (!$dbman->field_exists($table, $field)){
			$dbman->add_field($table, $field);
		}

		// We shifted mask values one factor above.        
        $sql = "
        	UPDATE {tracker_preferences} SET value = value * 2 WHERE name = 'eventmask'
        ";
        $DB->execute($sql);

        /// tracker savepoint reached
        upgrade_mod_savepoint(true, 2014010100, 'tracker');
	}
    
    return $result;
}

