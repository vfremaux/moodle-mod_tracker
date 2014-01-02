<?php

/** 
* This view allows checking deck states
* 
* @package mod-tracker
* @category mod
* @author Valery Fremaux
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
*/

/**
* Requires and includes 
*/

require_once ($CFG->dirroot.'/course/moodleform_mod.php');

/**
* overrides moodleform for test setup
*/
class mod_tracker_mod_form extends moodleform_mod {

	function definition() {
	 	global $CFG, $COURSE, $DB;

	 	$mform    =& $this->_form;
	  	//-------------------------------------------------------------------------------
	  	$mform->addElement('header', 'general', get_string('general', 'form'));
	  	$mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
	  	$mform->setType('name', PARAM_CLEANHTML);
	  	$mform->addRule('name', null, 'required', null, 'client');

        $this->add_intro_editor(true, get_string('intro', 'tracker'));

	  	// $mform->addRule('summary', get_string('required'), 'required', null, 'client');
      	$modeoptions['bugtracker'] = get_string('mode_bugtracker', 'tracker');
      	$modeoptions['ticketting'] = get_string('mode_ticketting', 'tracker');
      	$modeoptions['taskspread'] = get_string('mode_taskspread', 'tracker');
      	$modeoptions['customized'] = get_string('mode_customized', 'tracker');
	  	$mform->addElement('select', 'supportmode', get_string('supportmode', 'tracker'), $modeoptions);
	  	$mform->addHelpButton('supportmode', 'supportmode', 'tracker');

	  	$mform->addElement('text', 'ticketprefix', get_string('ticketprefix', 'tracker'), array('size' => 5));
	  	$mform->setType('ticketprefix', PARAM_TEXT);
      	$mform->setAdvanced('ticketprefix');

		$stateprofileopts = array(
			ENABLED_OPEN => get_string('open', 'tracker'),
			ENABLED_RESOLVING => get_string('resolving', 'tracker'),
			ENABLED_WAITING => get_string('waiting', 'tracker'),
			ENABLED_RESOLVED => get_string('resolved', 'tracker'),
			ENABLED_ABANDONNED => get_string('abandonned', 'tracker'),
			ENABLED_TESTING => get_string('testing', 'tracker'),
			ENABLED_PUBLISHED => get_string('published', 'tracker'),
			ENABLED_VALIDATED => get_string('validated', 'tracker'),
		);
      	$select = &$mform->addElement('select', 'stateprofile', get_string('stateprofile', 'tracker'), $stateprofileopts);
      	$mform->setType('stateprofile', PARAM_INT);
      	$mform->disabledIf('stateprofile', 'supportmode', 'neq', 'customized');
      	$select->setMultiple(true);
      	$mform->setAdvanced('stateprofile');

      	$mform->addElement('textarea', 'thanksmessage', get_string('thanksmessage', 'tracker'), array('cols' => 60, 'rows' => 10));
      	$mform->disabledIf('thanksmessage', 'supportmode', 'neq', 'customized');
      	$mform->setType('thanksmessage', PARAM_TEXT);
      	$mform->setAdvanced('thanksmessage');

	  	$mform->addElement('checkbox', 'enablecomments', get_string('enablecomments', 'tracker'));
	  	$mform->addHelpButton('enablecomments', 'enablecomments', 'tracker');

	  	$mform->addElement('checkbox', 'allownotifications', get_string('notifications', 'tracker'));
	  	$mform->addHelpButton('allownotifications', 'notifications', 'tracker');

	  	$mform->addElement('checkbox', 'strictworkflow', get_string('strictworkflow', 'tracker'));
	  	$mform->addHelpButton('strictworkflow', 'strictworkflow', 'tracker');

      	if (isset($this->_cm->id) && $assignableusers = get_users_by_capability(context_module::instance($this->_cm->id), 'mod/tracker:resolve', 'u.id, firstname,lastname', 'lastname,firstname')){
      	    $useropts[0] = get_string('none');
      	    foreach($assignableusers as $assignable){
      	          $useropts[$assignable->id] = fullname($assignable);
      	    }
		    $mform->addElement('select', 'defaultassignee', get_string('defaultassignee', 'tracker'), $useropts);
		    $mform->addHelpButton('defaultassignee', 'defaultassignee', 'tracker');
      		$mform->disabledIf('defaultassignee', 'supportmode', 'eq', 'taskspread');
      		$mform->setAdvanced('defaultassignee');
      	} else {
    		$mform->addElement('hidden', 'defaultassignee', 0);
      	}
	    $mform->setType('defaultassignee', PARAM_INT);

	  	if ($subtrackers = $DB->get_records_select('tracker', " id != 0 " )){
			$trackermoduleid = $DB->get_field('modules', 'id', array('name' => 'tracker'));
	  		$subtrackersopts = array();
	  		foreach($subtrackers as $st){
				if ($targetcm = $DB->get_record('course_modules', array('instance' => $st->id, 'module' => $trackermoduleid))){
					$targetcontext = context_module::instance($targetcm->id);
					if (has_any_capability(array('mod/tracker:manage', 'mod/tracker:develop', 'mod/tracker:resolve'), $targetcontext)){
						$trackercourseshort = $DB->get_field('course', 'shortname', array('id' => $st->course));
						$subtrackersopts[$st->id] = $trackercourseshort.' - '.$st->name;	  			
					}
				}
	  		}
			if (!empty($subtrackersopts)){
		      	$select = &$mform->addElement('select', 'subtrackers', get_string('subtrackers', 'tracker'), $subtrackersopts);
		      	$mform->setType('subtrackers', PARAM_INT);
		      	$mform->setAdvanced('subtrackers');
		      	$select->setMultiple(true);
		    }
	  	}
      	$options['idnumber'] = true;
      	$options['groups'] = false;
      	$options['groupings'] = false;
      	$options['gradecat'] = false;
	  	$this->standard_coursemodule_elements($options);	  
	  	$this->add_action_buttons();
	}
	
	function set_data($defaults){

		if (!property_exists($defaults, 'enabledstates')){
			$defaults->stateprofile = array();

			$defaults->stateprofile[] = ENABLED_OPEN; // state when opened by the assigned
			$defaults->stateprofile[] = ENABLED_RESOLVING; // state when asigned tells he starts processing
			// $defaults->stateprofile[] = ENABLED_WAITING; // state when ticket is blocked by an external cause
			$defaults->stateprofile[] = ENABLED_RESOLVED; // state when issue has an identified solution provided by assignee
			$defaults->stateprofile[] = ENABLED_ABANDONNED; // state when issue is no more relevant by external cause
			// $defaults->stateprofile[] = ENABLED_TESTING; // state when assignee submits issue to requirer and needs acknowledge
			// $defaults->stateprofile[] = ENABLED_PUBLISHED; // state when solution is realy published in production (not testing)
			// $defaults->stateprofile[] = ENABLED_VALIDATED; // state when all is clear and acknowledge from requirer in production
		} else {
			$defaults->stateprofile = array();
			if ($defaults->enabledstates & ENABLED_OPEN) $defaults->stateprofile[] = ENABLED_OPEN;
			if ($defaults->enabledstates & ENABLED_RESOLVING) $defaults->stateprofile[] = ENABLED_RESOLVING;
			if ($defaults->enabledstates & ENABLED_WAITING) $defaults->stateprofile[] = ENABLED_WAITING;
			if ($defaults->enabledstates & ENABLED_RESOLVED) $defaults->stateprofile[] = ENABLED_RESOLVED;
			if ($defaults->enabledstates & ENABLED_ABANDONNED) $defaults->stateprofile[] = ENABLED_ABANDONNED;
			if ($defaults->enabledstates & ENABLED_TESTING) $defaults->stateprofile[] = ENABLED_TESTING;
			if ($defaults->enabledstates & ENABLED_PUBLISHED) $defaults->stateprofile[] = ENABLED_PUBLISHED;
			if ($defaults->enabledstates & ENABLED_VALIDATED) $defaults->stateprofile[] = ENABLED_VALIDATED;
		}
				
		parent::set_data($defaults);

	}

	function definition_after_data(){
	  $mform    =& $this->_form;
	}

	function validation($data, $files = null) {
	    $errors = array();
	    return $errors;
	}

}
