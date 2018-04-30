<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package mod_tracker
 * @category mod
 * @author Clifford Tham, Valery Fremaux > 1.8
 * @date 02/12/2007
 */
defined('MOODLE_INTERNAL') || die();

/**
 * The master renderer
 */
class mod_tracker_renderer extends plugin_renderer_base {

    public function core_issue(&$issue, &$tracker) {
        global $CFG, $COURSE, $DB;

        $statuskeys = tracker_get_statuskeys($tracker);
        $statuscodes = tracker_get_statuscodes();

        $template = new Stdclass;

        $template->summary = format_string($issue->summary);

        if ($issue->downlink) {
            $access = true;
            list($hostid, $instanceid, $issueid) = explode(':', $issue->downlink);
            if (!$hostid || $hostid == $CFG->mnet_localhost_id) {
                $cm = get_coursemodule_from_instance('tracker', $instanceid);
                if ($cm && $DB->record_exists('tracker_issue', array('id' => $issueid))) {
                    $params = array('id' => $cm->id, 'view' => 'view', 'screen' => 'viewanissue', 'issueid' => $issueid);
                    $url = new moodle_url('/mod/tracker/view.php', $params);
                    $context = context_module::instance($cm->id);
                    if (has_capability('mod/tracker:seeissues', $context)) {
                        $template->downlink = html_writer::link($url, get_string('gotooriginal', 'tracker'));
                    } else {
                        $template->downlink = get_string('originalticketnoaccess', 'tracker');
                    }
                } else {
                    // Local downstream tracker is gone, or downstream issue was deleted.
                    // Revert to an unbound situation.
                    $DB->set_field('tracker_issue', 'downlink', '', array('id' => $issue->id));
                }
            } else {
                $template->downlink = $this->remote_link($hostid, $instanceid, $issueid);
            }
        }

        if ($issue->uplink) {
            list($hostid, $instanceid, $issueid) = explode(':', $issue->uplink);

            $access = true;
            $link = '';
            if (!$hostid || $hostid == $CFG->mnet_localhost_id) {
                // Local parent tracker.
                $cm = get_coursemodule_from_instance('tracker', $instanceid);
                if ($cm && $DB->record_exists('tracker_issue', array('id' => $issueid))) {
                    $params = array('id' => $cm->id, 'view' => 'view', 'screen' => 'viewanissue', 'issueid' => $issueid);
                    $url = new moodle_url('/mod/tracker/view.php', $params);

                    $context = context_module::instance($cm->id);
                    if (has_capability('mod/tracker:seeissues', $context)) {
                        $template->uplink = html_writer::link($url, get_string('gototransfered', 'tracker'));
                    } else {
                        $template->uplink = get_string('transferedticketnoaccess', 'tracker');
                    }
                } else {
                    /*
                     * Either upstream tracker has been deleted or upstream issue has been deleted.
                     * then revert uplink and go back to open state.
                     */
                    $issue->uplink = '';
                    $issue->status = OPEN;
                    $DB->update_record('tracker_issue', $issue);
                }
            } else {
                $template->uplink = $this->remote_link($hostid, $instanceid, $issueid);
            }
        }

        $template->strissuenumber = get_string('issuenumber', 'tracker');
        $template->fullid = $tracker->ticketprefix.$issue->id;
        $template->strstzatus = get_string('status', 'tracker');
        $template->statuscode = $statuscodes[$issue->status];
        $template->status = $statuskeys[$issue->status];

        $template->strreportedby = get_string('reportedby', 'tracker');
        $template->reporterpicture = $this->output->user_picture($issue->reporter);
        $template->reportername = fullname($issue->reporter);

        $template->strdatereported = get_string('datereported', 'tracker');
        $template->datereported = userdate($issue->datereported);

        $template->strassignedto = get_string('assignedto', 'tracker');
        if (!$issue->owner) {
            $template->assignedto = get_string('unassigned', 'tracker');
        } else {
            $str = $this->output->user_picture($issue->owner, array('courseid' => $COURSE->id, 'size' => 35));
            $str .= '&nbsp;'.fullname($issue->owner);
            $template->assignedto = $str;
        }
        $template->strcced = get_string('cced', 'tracker');
        $template->ccscount = (empty($ccs) || count(array_keys($ccs)) == 0) ? 0 : count($ccs);

        $template->strdescription = get_string('description');
        $template->description = format_text($issue->description);

        return $this->render_from_template('mod_tracker/coreissue', $template);
    }

    public function edit_link($issue, $cm) {


        $issueurl = new moodle_url('/mod/tracker/view.php');

        $template = new stdClass;

        $template->id= $cm->id;
        $template->view = 'view';
        $template->screen = 'editanissue';
        $template->issueid = $issue->id;

        $template->issueurl = $issueurl;
        $template->strturneditingon = get_string('turneditingon', 'tracker');

        return $this->render_from_template('mod_tracker/editlink', $template);
    }

    public function remote_link($hostid, $instanceid, $issueid) {
        global $CFG;

        $host = $DB->get_record('mnet_host', array('id' => $hostid));

        if (file_exists($CFG->dirroot.'/blocks/user_mnet_hosts/xlib.php')) {
            include_once($CFG->dirroot.'/blocks/user_mnet_hosts/xlib.php');
            $access = user_mnet_hosts_read_access($USER, $host->wwwroot);
        }
        if ($access) {
            $params = array('t' => $instanceid, 'view' => 'view', 'screen' => 'viewanissue', 'issueid' => $issueid);
            $remoteurl = new moodle_url('/mod/tracker/view.php', $params);
            $mnetauth = is_enabled_auth('multimnet') ? 'multimnet' : 'mnet';
            $url = new moodle_url('/auth/'.$mnetauth.'/jump.php', array('hostwwwroot' => $host->wwwroot, 'wantsurl' => $remoteurl));
            return html_writer::link($url, get_string('gototransfered', 'tracker'));
        } else {
            return get_string('transferedticketnoaccess', 'tracker');
        }
    }

    public function issue_attributes($issue, $elementsused) {

        $str = '';

        $cm = get_coursemodule_from_instance('tracker', $issue->trackerid);
        $context = context_module::instance($cm->id);

        $keys = array_keys($elementsused);
        if (!empty($keys)) {
            for ($i = 0; $i < count($keys);) {
                $key = $keys[$i];

                // Hide private fields if not power user.
                $capabilities = array('mod/tracker:manage', 'mod/tracker:develop', 'mod/tracker:resolve');
                if ($elementsused[$key]->private && !has_any_capability($capabilities, $context)) {
                    continue;
                }

                // Print first category in one column.
                $str .= '<tr valign="top">';
                $str .= '<td colspan="1" class="tracker-issue-description">';
                $str .= '<b>';
                $str .= format_string($elementsused[$key]->description);
                $str .= ':</b><br />';
                $str .= '</td>';

                $str .= '<td colspan="3" class="tracker-issue-value">';
                $str .= $elementsused[$key]->view($issue->id);
                $str .= '</td>';
                $str .= '</tr>';
                $i++;
            }
        }

        return $str;
    }

    public function resolution($issue) {

        $str = '';

        $str .= '<tr valign="top">';
        $str .= '<td align="right" height="25%" class="tracker-issue-param">';
        $str .= '<b>'.get_string('resolution', 'tracker').':</b>';
        $str .= '</td>';
        $str .= '<td align="left" colspan="3" width="75%">';
        $str .= format_text($issue->resolution, $issue->resolutionformat);
        $str .= '</td>';
        $str .= '</tr>';

        return $str;
    }

    public function distribution_form($tracker, $issue, $cm) {
        global $DB;

        $str = '';

        $choosetargetstr = get_string('choosetarget', 'tracker');
        $str .= ' <form name="distribute" style="display:inline">';
        $str .= '<input type="hidden" name="view" value="view" >';
        $str .= '<input type="hidden" name="what" value="distribute" >';
        $str .= '<input type="hidden" name="issueid" value="'.$issue->id.'" >';
        $str .= '<input type="hidden" name="id" value="'.$cm->id.'" >';
        $str .= '<select name="target">';
        $str .= '<option value="0">'.$choosetargetstr.'</option>';
        $trackermoduleid = $DB->get_field('modules', 'id', array('name' => 'tracker'));
        if ($subtrackers = $DB->get_records('tracker', array('id' => $tracker->subtrackers), 'name', 'id,name,course')) {
            foreach ($subtrackers as $st) {
                if ($targetcm = $DB->get_record('course_modules', array('instance' => $st->id, 'module' => $trackermoduleid))) {
                    $courseshort = $DB->get_field('course', 'shortname', array('id' => $st->course));
                    $targetcontext = context_module::instance($targetcm->id);
                    $caps = array('mod/tracker:manage', 'mod/tracker:develop', 'mod/tracker:resolve');
                    if (has_any_capability($caps, $targetcontext)) {
                        $str .= '<option value="'.$st->id.'">'.$courseshort.' - '.$st->name.'</option>';
                    }
                }
            }
        }
        $str .= '</select>';
        $str .= '</form>';
        $str .= " <a href=\"Javascript:document.forms['distribute'].submit();\">".get_string('distribute', 'tracker').'</a>';

        return $str;
    }

    /**
     * prints comments for the given issue
     * @param int $issueid
     */
    public function comments($issueid) {
        global $DB;

        $str = '';

        $comments = $DB->get_records('tracker_issuecomment', array('issueid' => $issueid), 'datecreated');
        if ($comments) {
            foreach ($comments as $comment) {
                $user = $DB->get_record('user', array('id' => $comment->userid));
                $str .= '<tr>';
                $str .= '<td valign="top" class="commenter" width="30%">';
                $str .= $this->user($user);
                $str .= '<br/>';
                $str .= '<span class="timelabel">'.userdate($comment->datecreated).'</span>';
                $str .= '</td>';
                $str .= '<td colspan="3" valign="top" align="left" class="comment">';
                $str .= $comment->comment;
                $str .= '</td>';
                $str .= '</tr>';
            }
        }

        return $str;
    }

    /**
     * a local version of the print user command that fits  better to the tracker situation
     * @uses $COURSE
     * @uses $CFG
     * @param object $user the user record
     */
    public function user($user) {
        global $COURSE, $CFG;

        $str = '';

        if ($user) {
            $str .= $this->output->user_picture($user, array('courseid' => $COURSE->id, 'size' => 24));
            $userurl = new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $COURSE->id));
            if ($CFG->messaging) {
                $str .= '&nbsp;<a href="'.$userurl.'">'.fullname($user).'</a>';
                $jshandler = 'this.target=\'message\';';
                $jshandler .= 'return openpopup(\'/message/discussion.php?id={$user->id}\', \'message\',';
                $jshandler .= '\'menubar=0,location=0,scrollbars,status,resizable,width=400,height=500\', 0);';
                $pix = $this->output->pix_icon('t/message', '', 'core');
                $str .= '&nbsp;<a href="" onclick="'.$jshandler.'" >'.$pix.'</a>';
            } else if (!$user->emailstop && $user->maildisplay) {
                $str .= '&nbsp;<a href="'.$userurl.'">'.fullname($user).'</a>';
                $str .= '&nbsp;<a href="mailto:'.$user->email.'">'.$this->output->pix_icon('t/mail', '', 'core').'</a>';
            } else {
                $str .= '&nbsp;'.fullname($user);
            }
        }

        return $str;
    }

    public function ccs(&$ccs, &$issue, &$cm, &$cced, $initialviewmode) {
        global $DB;

        $str = '';

        $str .= '<tr>';
        $str .= '<td colspan="4" width="100%" class="tracker-ccs">';
        $str .= '<table id="tracker-issueccs" class="'.$initialviewmode.'" width="100%">';
        $str .= '<tr valign="top">';
        $str .= '<td colspan="3">';
        $str .= $this->output->heading(get_string('cced', 'tracker'));
        $str .= '</td>';
        $str .= '</tr>';

        foreach ($ccs as $cc) {
            $str .= '<tr valign="top">';
            $str .= '<td width="20%" valign="top">&nbsp;</td>';
            $str .= '<td align="left" style="white-space : nowrap" valign="top">';
            $user = $DB->get_record('user', array('id' => $cc->userid));
            $str .= $this->user($user);
            $cced[] = $cc->userid;
            $str .= '</td>';
            $str .= '<td align="right">';
            if (has_capability('mod/tracker:managewatches', context_module::instance($cm->id))) {
                $params = array('id' => $cm->id,
                                'view' => 'view',
                                'what' => 'unregister',
                                'issueid' => $issue->id,
                                'ccid' => $cc->userid);
                $deleteurl = new moodle_url('/mod/tracker/view.php', $params);
                $pix = $this->output->pix_icon('t/delete', '', 'core');
                $str .= '&nbsp;<a href="'.$deleteurl.'" title="'.get_string('delete').'">'.$pix.'</a>';
            }
            $str .= '</td>';
            $str .= '</tr>';
        }
        $str .= '</table>';
        $str .= '</td>';
        $str .= '</tr>';

        return $str;
    }

    public function watches_form(&$issue, &$cm, &$cced) {

        $str = '';

        $str .= '<tr>';
        $str .= '<td>&nbsp;</td>';
        $str .= '<td colspan="3" align="right">';
        $issueurl = new moodle_url('/mod/tracker/view.php');
        $str .= '<form name="addccform" method="get" action="'.$issueurl.'">';
        $str .= '<input type="hidden" name="id" value="'.$cm->id.'" />';
        $str .= '<input type="hidden" name="what" value="register" />';
        $str .= '<input type="hidden" name="view" value="view" />';
        $str .= '<input type="hidden" name="issueid" value="'.$issue->id.'" />';
        $str .= get_string('addawatcher', 'tracker').':&nbsp;';
        $contextmodule = context_module::instance($cm->id);
        $fields = 'u.id,'.get_all_user_name_fields(true, 'u');
        $potentials = get_users_by_capability($contextmodule, 'mod/tracker:canbecced', $fields.',picture,imagealt');
        $potentialsmenu = array();
        if ($potentials) {
            foreach ($potentials as $potential) {
                if (in_array($potential->id, $cced)) {
                    continue;
                }
                $potentialsmenu[$potential->id] = fullname($potential);
            }
        }
        $str .= html_writer::select($potentialsmenu, 'ccid');
        $str .= '<input type="submit" name="go_btn" value="'.get_string('add').'" />';
        $str .= '</form>';
        $str .= '</td>';
        $str .= '</tr>';

        return $str;
    }

    public function history(&$tracker, $history, $statehistory, $initialviewmode) {
        global $DB;

        $statuskeys = tracker_get_statuskeys($tracker);
        $statuscodes = tracker_get_statuscodes();

        $str = '';

        $str .= '<tr>';
        $str .= '<td colspan="4" width="100%">';
        $str .= '<div id="tracker-issuehistory" class="'.$initialviewmode.'">';
        $str .= $this->output->heading(get_string('history', 'tracker'), 2);
        $str .= '<div class="row-fluid">'; // Row
        $str .= '<div class="span6 col-6">'; // Cell

        $str .= $this->output->heading(get_string('assignees', 'tracker'), 3);

        $str .= '<table width="100%">';
        if (!empty($history)) {
            foreach ($history as $owner) {
                $user = $DB->get_record('user', array('id' => $owner->userid));
                $bywhom = $DB->get_record('user', array('id' => $owner->bywhomid));

                $str .= '<tr>';
                $str .= '<td align="left">';
                $str .= userdate($owner->timeassigned);
                $str .= '</td>';
                $str .= '<td align="left">';
                $str .= $this->user($user);
                $str .= '</td>';
                $str .= '<td align="left">';
                $str .= get_string('by', 'tracker') . ' ' . fullname($bywhom);
                $str .= '</td>';
                $str .= '</tr>';
            }
        }
        $str .= '</table>';

        $str .= '</div>'; // Cell
        $str .= '<div class="span6 col-6">'; // Cell

        $str .= $this->output->heading(get_string('statehistory', 'tracker'), 3);

        $str .= '<table width="100%">';
        if (!empty($statehistory)) {
            foreach ($statehistory as $state) {
                $bywhom = $DB->get_record('user', array('id' => $state->userid));
                $str .= '<tr valign="top">';
                $str .= '<td align="left">';
                $str .= userdate($state->timechange);
                $str .= '</td>';
                $str .= '<td align="left">';
                $str .= $this->user($bywhom);
                $str .= '</td>';
                $str .= '<td align="left">';
                $str .= '<span class="status-'.$statuscodes[$state->statusfrom].'">'.$statuskeys[$state->statusfrom].'</span>';
                $str .= '</td>';
                $str .= '<td align="left">';
                $str .= '</td>';
                $str .= '<td align="left">';
                $str .= '<span class="status-'.$statuscodes[$state->statusto].'">'.$statuskeys[$state->statusto].'</span>';
                $str .= '</td>';
                $str .= '</tr>';
            }
        }
        $str .= '</table>';
        $str .= '</div>'; // Cell.
        $str .= '</div>'; // Row.
        $str .= '</div>'; // Div.
        $str .= '</tr>';
        $str .= '</table>';

        return $str;
    }

    public function tabs($view, $screen, &$tracker, &$cm) {
        global $DB, $USER;

        $str = '';
        $context = context_module::instance($cm->id);

        if ($screen == 'mytickets') {
            $select = "trackerid = ? AND status <> ".RESOLVED." AND status <> ".ABANDONNED." AND reportedby = ? ";
            $totalissues = $DB->count_records_select('tracker_issue', $select, array($tracker->id, $USER->id));

            $select = "trackerid = ? AND (status = ".RESOLVED." OR status = ".ABANDONNED.") AND reportedby = ? ";
            $totalresolvedissues = $DB->count_records_select('tracker_issue', $select, array($tracker->id, $USER->id));
        } else if ($screen == 'mywork') {
            $select = "trackerid = ? AND status <> ".RESOLVED." AND status <> ".ABANDONNED." AND assignedto = ? ";
            $totalissues = $DB->count_records_select('tracker_issue', $select, array($tracker->id, $USER->id));

            $select = "trackerid = ? AND (status = ".RESOLVED." OR status = ".ABANDONNED.") AND assignedto = ? ";
            $totalresolvedissues = $DB->count_records_select('tracker_issue', $select, array($tracker->id, $USER->id));
        } else {
            $select = "trackerid = ? AND status <> ".RESOLVED." AND status <> ".ABANDONNED." AND status <> ".PUBLISHED;
            $totalissues = $DB->count_records_select('tracker_issue', $select, array($tracker->id));

            $select = "trackerid = ? AND (status = ".RESOLVED." OR status = ".ABANDONNED." OR status = ".PUBLISHED.")";
            $totalresolvedissues = $DB->count_records_select('tracker_issue', $select, array($tracker->id));
        }

        // Print tabs with options for user.
        if (has_capability('mod/tracker:report', $context)) {
            $taburl = new moodle_url('/mod/tracker/reportissue.php', array('id' => $cm->id));
            $rows[0][] = new tabobject('reportanissue', $taburl, get_string('newissue', 'tracker'));
        }

        $label = get_string('view', 'tracker').' ('.$totalissues.' '.get_string('issues', 'tracker').')';
        $params = array('id' => $cm->id, 'view' => 'view');
        $taburl = new moodle_url('/mod/tracker/view.php', $params);
        $rows[0][] = new tabobject('view', $taburl, $label);

        $label = get_string('resolvedplural', 'tracker').' ('.$totalresolvedissues.' '.get_string('issues', 'tracker').')';
        $params = array('id' => $cm->id, 'view' => 'resolved');
        $taburl = new moodle_url('/mod/tracker/view.php', $params);
        $rows[0][] = new tabobject('resolved', $taburl, $label);

        $label = get_string('profile', 'tracker');
        $params = array('id' => $cm->id, 'view' => 'profile');
        $taburl = new moodle_url('/mod/tracker/view.php', $params);
        $rows[0][] = new tabobject('profile', $taburl, $label);

        if (has_capability('mod/tracker:viewreports', $context)) {
            $label = get_string('reports', 'tracker');
            $params = array('id' => $cm->id, 'view' => 'reports');
            $taburl = new moodle_url('/mod/tracker/view.php', $params);
            $rows[0][] = new tabobject('reports', $taburl, $label);
        }

        if (has_capability('mod/tracker:configure', $context)) {
            $label = get_string('administration', 'tracker');
            $params = array('id' => $cm->id, 'view' => 'admin');
            $taburl = new moodle_url('/mod/tracker/view.php', $params);
            $rows[0][] = new tabobject('admin', $taburl, $label);
        }

        if ($tracker->supportmode != 'taskspread') {
            $myticketsstr = get_string('mytickets', 'tracker');
        } else {
            $myticketsstr = get_string('mytasks', 'tracker');
        }

        // Submenus.
        $selected = null;
        $activated = null;
        switch ($view) {
            case 'view': {
                if (!preg_match("/mytickets|mywork|browse|search|viewanissue|editanissue/", $screen)) {
                    $screen = 'mytickets';
                }

                if (has_capability('mod/tracker:report', $context)) {
                    $params = array('id' => $cm->id, 'view' => 'view', 'screen' => 'mytickets');
                    $taburl = new moodle_url('/mod/tracker/view.php', $params);
                    $rows[1][] = new tabobject('mytickets', $taburl, $myticketsstr);
                }

                if (tracker_has_assigned($tracker, false)) {
                    $params = array('id' => $cm->id, 'view' => 'view', 'screen' => 'mywork');
                    $taburl = new moodle_url('/mod/tracker/view.php', $params);
                    $rows[1][] = new tabobject('mywork', $taburl, get_string('mywork', 'tracker'));
                }

                if (has_capability('mod/tracker:viewallissues', $context) || $tracker->supportmode == 'bugtracker') {
                    $params = array('id' => $cm->id, 'view' => 'view', 'screen' => 'browse');
                    $taburl = new moodle_url('/mod/tracker/view.php', $params);
                    $rows[1][] = new tabobject('browse', $taburl, get_string('browse', 'tracker'));
                }

                /*
                if ($tracker->supportmode == 'bugtracker') {
                    $params = array('id' => $cm->id, 'view' => 'view', 'screen' => 'search');
                    $taburl = new moodle_url('/mod/tracker/view.php', $params);
                    $rows[1][] = new tabobject('search', $taburl, get_string('search', 'tracker'));
                }
                */
                break;
            }

            case 'resolved': {
                if (!preg_match("/mytickets|browse|mywork/", $screen)) {
                    $screen = 'mytickets';
                }

                if (has_capability('mod/tracker:report', $context)) {
                    $params = array('id' => $cm->id, 'view' => 'resolved', 'screen' => 'mytickets');
                    $taburl = new moodle_url('/mod/tracker/view.php', $params);
                    $rows[1][] = new tabobject('mytickets', $taburl, $myticketsstr);
                }

                if (tracker_has_assigned($tracker, true)) {
                    $params = array('id' => $cm->id, 'view' => 'view', 'screen' => 'mywork');
                    $taburl = new moodle_url('/mod/tracker/view.php', $params);
                    $rows[1][] = new tabobject('mywork', $taburl, get_string('mywork', 'tracker'));
                }

                if (has_capability('mod/tracker:viewallissues', $context) || $tracker->supportmode == 'bugtracker') {
                    $params = array('id' => $cm->id, 'view' => 'resolved', 'screen' => 'browse');
                    $taburl = new moodle_url('/mod/tracker/view.php', $params);
                    $rows[1][] = new tabobject('browse', $taburl, get_string('browse', 'tracker'));
                }
                break;
            }

            case 'profile': {
                if (!preg_match("/myprofile|mypreferences|mywatches|myqueries/", $screen)) {
                    $screen = 'myprofile';
                }

                $params = array('id' => $cm->id, 'view' => 'profile', 'screen' => 'myprofile');
                $taburl = new moodle_url('/mod/tracker/view.php', $params);
                $rows[1][] = new tabobject('myprofile', $taburl, get_string('myprofile', 'tracker'));

                $params = array('id' => $cm->id, 'view' => 'profile', 'screen' => 'mypreferences');
                $taburl = new moodle_url('/mod/tracker/view.php', $params);
                $rows[1][] = new tabobject('mypreferences', $taburl, get_string('mypreferences', 'tracker'));

                $params = array('id' => $cm->id, 'view' => 'profile', 'screen' => 'mywatches');
                $taburl = new moodle_url('/mod/tracker/view.php', $params);
                $rows[1][] = new tabobject('mywatches', $taburl, get_string('mywatches', 'tracker'));

                if ($tracker->supportmode == 'bugtracker') {
                    $params = array('id' => $cm->id, 'view' => 'profile', 'screen' => 'myqueries');
                    $taburl = new moodle_url('/mod/tracker/view.php', $params);
                    $rows[1][] = new tabobject('myqueries', $taburl, get_string('myqueries', 'tracker'));
                }
                break;
            }

            case 'reports': {
                if (!preg_match("/status|evolution|print/", $screen)) {
                    $screen = 'status';
                }

                if (tracker_supports_feature('reports/status')) {
                    $params = array('id' => $cm->id, 'view' => 'reports', 'screen' => 'status');
                    $taburl = new moodle_url('/mod/tracker/view.php', $params);
                    $rows[1][] = new tabobject('status', $taburl, get_string('status', 'tracker'));
                }

                if (tracker_supports_feature('reports/evolution')) {
                    $params = array('id' => $cm->id, 'view' => 'reports', 'screen' => 'evolution');
                    $taburl = new moodle_url('/mod/tracker/view.php', $params);
                    $rows[1][] = new tabobject('evolution', $taburl, get_string('evolution', 'tracker'));
                }

                if (tracker_supports_feature('reports/print')) {
                    $params = array('id' => $cm->id, 'view' => 'reports', 'screen' => 'print');
                    $taburl = new moodle_url('/mod/tracker/view.php', $params);
                    $rows[1][] = new tabobject('print', $taburl, get_string('print', 'tracker'));
                }
                break;
            }

            case 'admin': {
                if (!preg_match("/summary|manageelements|managenetwork/", $screen)) {
                    $screen = 'summary';
                }

                $params = array('id' => $cm->id, 'view' => 'admin', 'screen' => 'summary');
                $taburl = new moodle_url('/mod/tracker/view.php', $params);
                $rows[1][] = new tabobject('summary', $taburl, get_string('summary', 'tracker'));

                $params = array('id' => $cm->id, 'view' => 'admin', 'screen' => 'manageelements');
                $taburl = new moodle_url('/mod/tracker/view.php', $params);
                $rows[1][] = new tabobject('manageelements', $taburl, get_string('manageelements', 'tracker'));

                if (tracker_supports_feature('cascade/mnet')) {
                    if (has_capability('mod/tracker:configurenetwork', $context)) {
                        $params = array('id' => $cm->id, 'view' => 'admin', 'screen' => 'managenetwork');
                        $taburl = new moodle_url('/mod/tracker/view.php', $params);
                        $rows[1][] = new tabobject('managenetwork', $taburl, get_string('managenetwork', 'tracker'));
                    }
                }
                break;
            }

            default:
        }

        if (!empty($screen)) {
            $selected = $screen;
            $activated = array($view);
        } else {
            $selected = $view;
        }

        $str .= $this->output->container_start('mod-header tracker-tabs');
        $str .= print_tabs($rows, $selected, '', $activated, true);
        $str .= $this->output->container_end();

        return $str;
    }

    public function edit_element_obsolete(&$cm, $form) {

        $context = context_module::instance($cm->id);

        $str = '';

        $str .= $this->output->heading(get_string("{$form->action}{$form->type}", 'tracker'));

        $str .= '<center>';
        $formurl = new moodle_url('/mod/tracker/view.php');
        $str .= '<form name="editelementform" method="post" action="'.$formurl.'">';
        $str .= '<input type="hidden" name="id" value="'.$cm->id.'" />';
        $str .= '<input type="hidden" name="view" value="admin" />';
        $str .= '<input type="hidden" name="what" value="'.s($form->action).'" />';
        $str .= '<input type="hidden" name="type" value="'.s($form->type).'" />';

        if ($form->action == 'editelement') {
            $str .= '<input type="hidden" name="elementid" value="'.$form->elementid.'" />';
        }
        if (!has_capability('mod/tracker:shareelements', $context)) {
            $str .= '<input type="hidden" name="shared" value="0" />';
        }

        $str .= '<table width="100%" class="tracker-edit-element" cellpadding="5">';
        $str .= '<tr valign="top" >';
        $str .= '<td align="right"><b>'.get_string('name').':</b></td>';
        $str .= '<td align="left">';
        $str .= '<input type="text" name="name" value="'.@$form->name.'" size="32" maxlength="32" />';
        $str .= $this->output->help_icon('elements', 'tracker');
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '<tr>';
        $str .= '<td valign="top" align="right"><b>'.get_string('description').':</b></td>';
        $str .= '<td colspan="3" align="left">';
        $str .= '<input type="text"
                        name="description"
                        value="'.htmlspecialchars(stripslashes(@$form->description)).'"
                        size="80"
                        maxlength="255" />';
        $str .= $this->output->help_icon('elements', 'tracker');
        $str .= '</td>';
        $str .= '</tr>';

        if (has_capability('mod/tracker:shareelements', $context)) {

            $str .= '<tr>';
            $str .= '<td valign="top" align="right">';
            $str .= '<b>'.get_string('sharing', 'tracker').':</b>';
            $str .= '</td>';
            $str .= '<td align="left">';
            $checked = (@$form->shared) ? 'checked="checked"' : '';
            $str .= '<input type="checkbox" name="shared" value="1" '.$checked.' /> '.get_string('sharethiselement', 'tracker');
            $str .= '</td>';
            $str .= '</tr>';

        }

        $str .= '<tr>';
        $str .= '<td colspan="2" align="center">';
        $str .= '<input type="submit" name="go_btn" value="'.get_string('submit').'" />&nbsp;';
        $jshandler = 'document.forms[\'editelementform\'].what.value = \'\';document.forms[\'editelementform\'].submit();';
        $str .= '<input type="button" name="cancel_btn" value="'.get_string('cancel').'" onclick="'.$jshandler.'" /><br/>';
        $str .= '<br/>';
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '</table>';
        $str .= '</form>';
        $str .= '</center>';

        return $str;
    }

    public function search_queries(&$cm) {

        $str = '';

        $str .= '<center>';
        $str .= '<table class="tracker-search-queries" width="100%">';
        if (isset($searchqueries)) {
            $str .= '<tr>';
            $str .= '<td>';
            $str .= get_string('searchresults', 'tracker').': '.$numrecords.' <br/>';
            $str .= '</td>';
            $str .= '<td align="right">';
            $params = array('id' => $cm->id, 'what' => 'clearsearch');
            $clearsearchurl = new moodle_url('/mod/tracker/view.php', $params);
            $str .= '<a href="'.$clearsearchurl.'">'.get_string('clearsearch', 'tracker').'</a>';
            $str .= '</td>';
            $str .= '</tr>';
        }
        $str .= '</table>';
        $str .= '</center>';

        return $str;
    }

    public function edit_option_form(&$cm, &$form, $action, $errors = null) {

        $str = '';

        $strname = get_string('opcode', 'tracker');
        $strdescription = get_string('visiblename', 'tracker');
        $straction = get_string('action');

        $formurl = new moodle_url('/mod/tracker/view.php');
        $str .= '<form name="editoptionform" method="post" action="'.$formurl.'">';
        $str .= '<input type="hidden" name="id" value="'.$cm->id.'" />';
        $str .= '<input type="hidden" name="what" value="'.$action.'elementoption" />';
        $str .= '<input type="hidden" name="view" value="admin" />';
        $str .= '<input type="hidden" name="type" value="'.$form->type.'" />';
        $str .= '<input type="hidden" name="elementid" value="'.$form->elementid.'" />';
        $str .= '<input type="hidden" name="optionid" value="'.@$form->optionid.'" />';
        $str .= '<table width="90%">';

        $str .= '<tr>';
        $str .= '<td width="100">&nbsp;</td>';
        $str .= '<td width="110" align="center">';
        $str .= '<b>'.$strname.'</b>';
        $str .= '</td>';
        $str .= '<td width="240" align="center">';
        $str .= '<b>'.$strdescription.'</b></td>';
        $str .= '<td width="75" align="center"><b>'.$straction.'</b></td>';
        $str .= '</tr>';

        $str .= '<tr>';
        $str .= '<td>&nbsp;</td>';
        $str .= '<td align="center" '.print_error_class($errors, 'name').' >';
        $str .= '<input type="text" name="name" value="'.@$form->name.'" size="20" maxlength="32" />';
        $str .= '</td>';
        $str .= '<td align="center" '.print_error_class($errors, 'description').' >';
        $filtereddesc = htmlspecialchars(stripslashes(@$form->description));
        $str .= '<input type="text" name="description" value="'.$filtereddesc.'" size="60" maxlength="255" />';
        $str .= '</td>';
        $str .= '<td align="center">';
        $str .= '<input type="submit" name="add_btn" value="'.get_string('add').'" />';
        $str .= '</td>';
        $str .= '</tr>';

        $str .= '</table>';

        $str .= '<br/>';
        $jshandler = 'document.forms[\'editoptionform\'].what.value = \'\';';
        $jshandler .= 'document.forms[\'editoptionform\'].submit();';
        $str .= '<input type="button" name="cancel_btn" value="'.get_string('continue').'" onclick="'.$jshandler.'" />';
        $str .= '<br/>';
        $str .= '</form>';
        $str .= '<br/>';

        return $str;
    }

    public function option_list_view(&$cm, &$element) {
        global $COURSE;

        $strname = get_string('name');
        $strdescription = get_string('description');
        $strsortorder = get_string('sortorder', 'tracker');
        $straction = get_string('action');

        $table = new html_table();
        $table->width = "90%";
        $table->size = array('15%', '15%', '50%', '30%');
        $table->head = array('', "<b>$strname</b>", "<b>$strdescription</b>", "<b>$straction</b>");

        $options = $element->options;
        if (!empty($options)) {
            foreach ($options as $option) {
                $params = array('id' => $cm->id,
                                'view' => 'admin',
                                'what' => 'editelementoption',
                                'optionid' => $option->id,
                                'elementid' => $option->elementid);
                $editoptionurl = new moodle_url('/mod/tracker/view.php', $params);
                $pix = $this->output->pix_icon('/t/edit', '', 'core');
                $actions  = '<a href="'.$editoptionurl.'" title="'.get_string('edit').'">'.$pix.'</a>&nbsp;';

                $img = ($option->sortorder > 1) ? 'up' : 'up_shadow';
                $params = array('id' => $cm->id,
                                'view' => 'admin',
                                'what' => 'moveelementoptionup',
                                'optionid' => $option->id,
                                'elementid' => $option->elementid);
                $moveurl = new moodle_url('/mod/tracker/view.php', $params);
                $pix = $this->output->pix_icon("{$img}", '', 'mod_tracker');
                $actions .= '<a href="'.$moveurl.'" title="'.get_string('up').'">'.$pix.'</a>&nbsp;';

                $img = ($option->sortorder < $element->maxorder) ? 'down' : 'down_shadow';
                $params = array('id' => $cm->id,
                                'view' => 'admin',
                                'what' => 'moveelementoptiondown',
                                'optionid' => $option->id,
                                'elementid' => $option->elementid);
                $moveurl = new moodle_url('/mod/tracker/view.php', $params);
                $pix = $this->output->pix_icon("{$img}", '', 'mod_tracker');
                $actions .= '<a href="'.$moveurl.'" title="'.get_string('down').'">'.$pix.'</a>&nbsp;';

                $params = array('id' => $cm->id,
                                'view' => 'admin',
                                'what' => 'deleteelementoption',
                                'optionid' => $option->id,
                                'elementid' => $option->elementid);
                $deleteurl = new moodle_url('/mod/tracker/view.php', $params);
                $pix = $this->output->pix_icon('/t/delete', '', 'core');
                $actions .= '<a href="'.$deleteurl.'" title="'.get_string('delete').'">'.$pix.'</a>';

                $rowlabel = '<b> '.get_string('option', 'tracker').' '.$option->sortorder.':</b>';
                $table->data[] = array($rowlabel, $option->name, format_string($option->description, true, $COURSE->id), $actions);
            }
        }
        return html_writer::table($table);
    }

    public function add_query_form(&$cm, $form) {

        $str = '';

        $str .= $this->output->heading(get_string('addaquerytomemo', 'tracker'));
        $str .= $this->output->box_start('center', '100%', '', '', 'generalbox', 'bugreport');

        $str .= '<center>';

        $formurl = new moodle_url('/mod/tracker/view.php');
        $str .= '<form name="addqueryform" action="'.$formurl.'" method="post">';
        $str .= '<input type="hidden" name="what" value="'.$form->action.'">';
        $str .= '<input type="hidden" name="view" value="profile">';
        $str .= '<input type="hidden" name="screen" value="myqueries">';
        $str .= '<input type="hidden" name="fields" value="'.$form->fields.'">';
        $str .= '<input type="hidden" name="id" value="'.$cm->id.'">';

        $str .= '<table border="0" cellpadding="5" width="100%">';
        $str .= '<tr>';
        $str .= '<td align="right" width="200">';
        $str .= '<b>'.get_string('name').':</b>';
        $str .= '</td>';
        $str .= '<td align="left">';
        $str .= '<input type="text" name="name" value="" style="width:100%" />';
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '<tr>';
        $str .= '<td align="right" width="200" valign="top">';
        $str .= '<b>'.get_string('description').':</b>';
        $str .= '</td>';
        $str .= '<td valign="top" align="left">';

        print_textarea($usehtmleditor, 20, 60, 680, 400, 'description', $form->description);
        if ($usehtmleditor) {
            $strr .= '<input type="hidden" name="format" value="'.FORMAT_HTML.'" />';
        } else {
            $str .= '<p align="right">';
            $str .= $this->output->help_icon('textformat', 'tracker');
            $str .= get_string('formattexttype');
            $str .= ':&nbsp;';
            if (empty($form->format)) {
                $form->format = FORMAT_MOODLE;
            }
            $str .= html_writer::select(format_text_menu(), 'format', $form->format);
            $str .= '</p>';
        }
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '<tr>';
        $str .= '<td colspan="2">';
        $onsubmitcall = ($usehtmleditor) ? "document.forms['addqueryform'].onsubmit();" : '';
        $str .= '<input type="submit" name="save" value="'.get_string('continue').'" />';
        $jshandler = 'document.forms[\'addqueryform\'].what.value = \'\';';
        $jshandler .= 'document.forms[\'addqueryform\'].screen.value = \'search\';';
        $jshandler .= $onsubmitcall;
        $jshandler .= 'document.forms[\'addqueryform\'].submit();';
        $str .= '<input type="button" name="cancel_btn" value="'.get_string('cancel').'" onclick="'.$jshandler.'" />';
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '</table>';
        $str .= '</form>';

        $str .= $this->output->box_end();
        $str .= '</center>';

        return $str;
    }

    public function issue_js_init() {
        $str = '<script type="text/javascript">';
        $str .= '    var showhistory = "'.get_string('showhistory', 'tracker').'";';
        $str .= '    var hidehistory = "'.get_string('hidehistory', 'tracker').'";';
        $str .= '    var showccs = "'.get_string('showccs', 'tracker').'";';
        $str .= '    var hideccs = "'.get_string('hideccs', 'tracker').'";';
        $str .= '    var showdependancies = "'.get_string('showdependancies', 'tracker').'";';
        $str .= '    var hidedependancies = "'.get_string('hidedependancies', 'tracker').'";';
        $str .= '    var showcomments = "'.get_string('showcomments', 'tracker').'";';
        $str .= '    var hidecomments = "'.get_string('hidecomments', 'tracker').'";';
        $str .= '</script>';

        return $str;
    }
}