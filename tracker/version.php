<?PHP // $Id: version.php,v 1.4 2012-07-14 19:25:28 vf Exp $

/////////////////////////////////////////////////////////////////////////////////
///  Code fragment to define the version of tracker
///  This fragment is called by moodle_needs_upgrading() and /admin/index.php
/////////////////////////////////////////////////////////////////////////////////

$module->version  = 2012062303;  // The current module version (Date: YYYYMMDDXX)
$module->requires = 2011120500;
$module->component = 'mod_tracker';   // Full name of the plugin (used for diagnostics)
$module->cron     = 0;           // Period for cron to check this module (secs)
$module->maturity = 'MATURITY_BETA';
$module->release = '2.2.0 (Build 2012062303)';

?>