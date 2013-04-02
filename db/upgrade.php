<?php

function xmldb_tracker_upgrade($oldversion=0) {
/// This function does anything necessary to upgrade 
/// older versions to match current functionality 

    global $CFG;

    $result = true;
    
    if ($oldversion < 2006062300) {
    }

    if ($result && $oldversion < 2008091900) {
    
    /// Define field parent to be added to tracker
        $table = new XMLDBTable('tracker');
        $field = new XMLDBField('parent');
        $field->setAttributes(XMLDB_TYPE_CHAR, '80', null, null, null, null, null, null, 'timemodified');

    /// Launch add field parent
        $result = $result && add_field($table, $field);
    }
    if ($result && $oldversion < 2008092400) {

        // setup XML-RPC services for tracker
        
        if (!get_record('mnet_service', 'name', 'tracker_cascade')){
            $service->name = 'tracker_cascade';
            $service->description = get_string('transferservice', 'tracker');
            $service->apiversion = 1;
            $service->offer = 1;
            if (!$serviceid = insert_record('mnet_service', $service)){
                notify('Error installing tracker_cascade service.');
                $result = false;
            }
            
            $rpc->function_name = 'tracker_rpc_get_instances';
            $rpc->xmlrpc_path = 'mod/tracker/rpclib.php/tracker_rpc_get_instances';
            $rpc->parent_type = 'mod';  
            $rpc->parent = 'tracker';
            $rpc->enabled = 0; 
            $rpc->help = 'Get instances of available trackers for cascading.';
            $rpc->profile = '';
            if (!$rpcid = insert_record('mnet_rpc', $rpc)){
                notify('Error installing tracker_cascade RPC calls.');
                $result = false;
            }
            $rpcmap->serviceid = $serviceid;
            $rpcmap->rpcid = $rpcid;
            insert_record('mnet_service2rpc', $rpcmap);
            
            $rpc->function_name = 'tracker_rpc_get_infos';
            $rpc->xmlrpc_path = 'mod/tracker/rpclib.php/tracker_rpc_get_infos';
            $rpc->parent_type = 'mod';  
            $rpc->parent = 'tracker';
            $rpc->enabled = 0; 
            $rpc->help = 'Get information about one tracker.';
            $rpc->profile = '';
            if (!$rpcid = insert_record('mnet_rpc', $rpc)){
                notify('Error installing tracker_cascade RPC calls.');
                $result = false;
            }
            $rpcmap->rpcid = $rpcid;
            insert_record('mnet_service2rpc', $rpcmap);

            $rpc->function_name = 'tracker_rpc_post_issue';
            $rpc->xmlrpc_path = 'mod/tracker/rpclib.php/tracker_rpc_post_issue';
            $rpc->parent_type = 'mod';  
            $rpc->parent = 'tracker';
            $rpc->enabled = 0; 
            $rpc->help = 'Cascades an issue.';
            $rpc->profile = '';
            if (!$rpcid = insert_record('mnet_rpc', $rpc)){
                notify('Error installing tracker_cascade RPC calls.');
                $result = false;
            }
            $rpcmap->rpcid = $rpcid;
            insert_record('mnet_service2rpc', $rpcmap);
        }
    }

    if ($result && $oldversion < 2008092602) {

    /// Define field supportmode to be added to tracker
        $table = new XMLDBTable('tracker');
        $field = new XMLDBField('supportmode');
        $field->setAttributes(XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null, null, 'bugtracker', 'parent');

    /// Launch add field supportmode
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2009042500) {

    /// Define field supportmode to be added to tracker
        $table = new XMLDBTable('tracker_issue');
        $field = new XMLDBField('resolutionpriority');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, 0, 'resolutionformat');

    /// Launch add field supportmode
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2009042503) {

        /// Reassign all priorities
        require_once($CFG->dirroot.'/mod/tracker/locallib.php');
        $trackers = get_records('tracker');
        if ($trackers){
            foreach($trackers as $tracker){
                $issues = get_records('tracker_issue', 'trackerid', $tracker->id);
                if ($issues){
                    $priority = 1;
                    foreach($issues as $issue){
                        // set once at upgrade and never again.
                        if ($issue->status < RESOLVED){
                            $issue->resolutionpriority = $priority;
                            $priority++;
                            update_record('tracker_issue', addslashes_recursive($issue));
                        }
                    }
                }
            }
        }

        /// Add comment enabling to all events
        $ccs = get_records('tracker_issuecc');
        if ($ccs){
            foreach($ccs as $cc){
                $cc->events |= ON_COMMENT;
                update_record('tracker_issuecc', $cc);
            }
        }
    }

    // fix field size for parent encoding in remote cascade. (long wwwroots) 
    if ($result && $oldversion < 2009090800) {

    /// Changing precision of field parent on table tracker to (80)
        $table = new XMLDBTable('tracker');
        $field = new XMLDBField('parent');
        $field->setAttributes(XMLDB_TYPE_CHAR, '80', null, null, null, null, null, null, 'timemodified');

    /// Launch change of precision for field parent
        $result = $result && change_field_precision($table, $field);
    }

 if ($result && $oldversion < 2010061000) {

    /// Define field defaultassignee to be added to tracker
        $table = new XMLDBTable('tracker');
        $field = new XMLDBField('defaultassignee');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'supportmode');

    /// Launch add field defaultassignee
        $result = $result && add_field($table, $field);
    }

	if ($result && $oldversion < 2011070400) {

    /// Define field subtrackers to be added to tracker
        $table = new XMLDBTable('tracker');
        $field = new XMLDBField('subtrackers');
        $field->setAttributes(XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, null, '0', 'defaultassignee');

    /// Launch add field subtrackers
        $result = $result && add_field($table, $field);
    }

	if ($result && $oldversion < 2011100800) {

    /// Define table tracker_state_change to be created
        $table = new XMLDBTable('tracker_state_change');

    /// Adding fields to table tracker_state_change
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('trackerid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('issueid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('timechange', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('statusfrom', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('statusto', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');

    /// Adding keys to table tracker_state_change
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Adding indexes to table tracker_state_change
        $table->addIndexInfo('issueid_ix', XMLDB_INDEX_NOTUNIQUE, array('issueid'));
        $table->addIndexInfo('userid_ix', XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $table->addIndexInfo('trackerid_ix', XMLDB_INDEX_NOTUNIQUE, array('trackerid'));

    /// Launch create table for tracker_state_change
        $result = $result && create_table($table);
    }
    
    return $result;
}

?>