<?PHP // $Id: version.php,v 1.2 2012-08-12 21:43:55 vf Exp $

/////////////////////////////////////////////////////////////////////////////////
///  Code fragment to define the version of tracker
///  This fragment is called by moodle_needs_upgrading() and /admin/index.php
/////////////////////////////////////////////////////////////////////////////////

$module->version  = 2014010100;  // The current module version (Date: YYYYMMDDXX)
$module->requires = 2012062501;
$module->component = 'mod_tracker';   // Full name of the plugin (used for diagnostics)
$module->cron     = 0;           // Period for cron to check this module (secs)
$module->maturity = MATURITY_RC;
$module->release = '2.4.0 (Build 2013050901)';

