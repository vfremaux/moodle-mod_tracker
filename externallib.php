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

	public static function get_instances_parameters(){
	
		return new external_function_parameters(
            array(
            	'username' => new external_value(
	                    PARAM_ALPHANUMEXT,
	                    'primary identifier'),
            	'remotehostroot' => new external_value(
	                    PARAM_RAW,
	                    'remote calling root')
        	)
        );
	}

    public static function get_instances_returns() {
        return new external_value(PARAM_RAW, 'serialized array of tracker records');
    }
    
    /**
    *
    *
    */

	function get_infos($trackerid){
		return json_decode(tracker_rpc_get_infos($trackerid, false));
	}

	public static function get_infos_parameters(){
	
		return new external_function_parameters(
            array(
            	'tracker' => new external_value(
	                    PARAM_ALPHANUMEXT,
	                    'primary identifier')
        	)
        );
	}

    public static function get_infos_returns() {
        return new external_value(PARAM_RAW, 'a serialized array of tracker short infos');
    }

	/**
	*
	*
	*/
	function post_issue($username, $remoteuserhostroot, $trackerid, $remote_issue){
		return tracker_rpc_post_issue($username, $remoteuserhostroot, $trackerid, $remote_issue);
	}

	public static function post_issue_parameters(){
	
		return new external_function_parameters(
            array(
            	'username' => new external_value(
	                    PARAM_ALPHANUMEXT,
	                    'primary identifier'),
            	'remoteuserhostroot' => new external_value(
	                    PARAM_TEXT,
	                    'remote host root'),
            	'trackerid' => new external_value(
	                    PARAM_INT,
	                    'remote tracker id where to post'),
            	'remoteissue' => new external_value(
	                    PARAM_RAW,
	                    'issue structure'),
        	)
        );
	}

    public static function post_issue_returns() {
        return new external_value(PARAM_RAW, 'response object with status, errors or message');
    }
}