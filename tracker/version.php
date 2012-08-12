<?PHP // $Id: version.php,v 1.2 2012-06-01 18:53:07 vf Exp $

/////////////////////////////////////////////////////////////////////////////////
///  Code fragment to define the version of tracker
///  This fragment is called by moodle_needs_upgrading() and /admin/index.php
/////////////////////////////////////////////////////////////////////////////////

$module->version  = 2012080900;  // The current module version (Date: YYYYMMDDXX)
$module->requires = 2012062501;
$module->component = 'mod_tracker';   // Full name of the plugin (used for diagnostics)
$module->cron     = 0;           // Period for cron to check this module (secs)

?>