<?php

/**
* This library is a third-party proposal for standardizing mail
* message constitution for third party modules. It is actually used
* by all ethnoinformatique.fr module. It relies on mail and message content
* templates that should reside in a mail/{$lang} directory within the
* module space.
*
* @package extralibs
* @category third-party libs
* @author Valery Fremaux (France) (valery@valeisti.fr)
* @date 2008/03/03
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
*/

if (!function_exists('compile_mail_template')) {

    /**
    * useful templating functions from an older project of mine, hacked for Moodle
    * @param template the template's file name from $CFG->sitedir
    * @param infomap a hash containing pairs of parm => data to replace in template
    * @return a fully resolved template where all data has been injected
    */
    function tracker_compile_mail_template($template, $infomap, $module, $lang = '') {
        global $USER;

        if (empty($lang)) $lang = $USER->lang;
        $lang = substr($lang, 0, 2); // be sure we are in moodle 2

        $notification = implode('', tracker_get_mail_template($template, $module, $lang));
        foreach ($infomap as $aKey => $aValue) {
            $notification = str_replace("<%%$aKey%%>", $aValue, $notification);
        }
        return $notification;
    }
}

if (!function_exists('get_mail_template')) {
    /*
    * resolves and get the content of a Mail template, acoording to the user's current language.
    * @param virtual the virtual mail template name
    * @param module the current module
    * @param lang if default language must be overriden
    * @return string the template's content or false if no template file is available
    */
    function tracker_get_mail_template($virtual, $modulename, $lang = '') {
        global $CFG;

        if ($lang == '') {
            $lang = $CFG->lang;
        }
        if (preg_match('/^auth_/', $modulename)) {
            $location = 'auth';
            $modulename = str_replace('auth_', '', $modulename);
        } elseif (preg_match('/^enrol_/', $modulename)) {
            $location = 'enrol';
            $modulename = str_replace('enrol_', '', $modulename);
        } elseif (preg_match('/^block_/', $modulename)) {
            $location = 'blocks';
            $modulename = str_replace('block_', '', $modulename);
        } else {
            $location = 'mod';
        }
        $templateName = "{$CFG->dirroot}/{$location}/{$modulename}/mails/{$lang}/{$virtual}.tpl";
        if (file_exists($templateName))
            return file($templateName);

        debugging("template $templateName not found");
        return array();
    }
}
