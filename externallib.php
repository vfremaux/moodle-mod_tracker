<?php

/**
* This file extends MNET internal web service definitions by wrapping functions
* on a compliant consistant naming space for Rest or other protocol invocation
* methods
*
*/

require_once 'rpclib.php';

class mod_tracker_external{

	function get_instances($username, $remotehostroot){
		return tracker_rpc_get_instances($username, $remotehostroot);
	}

	function get_infos($trackerid){
		return json_decode(tracker_rpc_get_infos($trackerid, false));
	}
		
	function post_issue($username, $remoteuserhostroot, $trackerid, $remote_issue){
		return tracker_rpc_post_issue($username, $remoteuserhostroot, $trackerid, $remote_issue);
	}
}