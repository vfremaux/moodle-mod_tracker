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

require_once($CFG->dirroot.'/mod/tracker/lib.php');

/**
 * The master renderer
 */
class mod_tracker_renderer extends plugin_renderer_base {

    // A local, lighly modified Mustache engine.
    private $mustache;

    /**
     * Renders the core issue display when displaying an issue content.
     */
    public function core_issue(&$issue, &$tracker) {
        global $CFG, $COURSE, $DB;

        $statuskeys = tracker_get_statuskeys($tracker);
        $statuscodes = tracker_get_statuscodes();

        $template = new Stdclass;

        $template->id = $issue->id;
        $template->summary = format_string($issue->summary);

        if (!empty(optional_param('findissueid', '', PARAM_ALPHANUMEXT))) {
            $template->notfoundissuesignal = '<i class="fa fa-wrong"></i>';
        }

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

        $template->fullid = $tracker->ticketprefix.$issue->id;
        $template->statuscode = $statuscodes[$issue->status];
        $template->status = $statuskeys[$issue->status];

        $template->reporterpicture = $this->output->user_picture($issue->reporter);
        $template->reportername = fullname($issue->reporter);

        $template->datereported = userdate($issue->datereported);

        if (!$issue->assignedto) {
            $template->assignedto = get_string('unassigned', 'tracker');
        } else {
            $owner = $DB->get_record('user', array('id' => $issue->assignedto));
            $str = $this->output->user_picture($owner, array('courseid' => $COURSE->id, 'size' => 35));
            $str .= '&nbsp;'.fullname($issue->owner);
            $template->assignedto = $str;
        }
        $template->ccscount = (empty($ccs) || count(array_keys($ccs)) == 0) ? 0 : count($ccs);

        $template->description = format_text($issue->description);

        return $this->output->render_from_template('mod_tracker/coreissue', $template);
    }

    public function edit_link($issue, $cm) {

        $issueurl = new moodle_url('/mod/tracker/view.php');

        $template = new stdClass;

        $template->id = $cm->id;
        $template->view = 'view';
        $template->screen = 'editanissue';
        $template->issueid = $issue->id;

        $template->issueurl = $issueurl;
        $template->strturneditingon = get_string('turneditingon', 'tracker');

        return $this->output->render_from_template('mod_tracker/editlink', $template);
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

        $template = new StdClass;

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

                $attributetpl = new Stdclass;
                // Print first category in one column.
                $attributetpl->name = format_string($elementsused[$key]->description);
                $attributetpl->value = $elementsused[$key]->view($issue->id);
                $attributetpl->isprivate = $elementsused[$key]->private;
                $i++;

                $template->attributes[] = $attributetpl;
            }
        }

        return $this->output->render_from_template('mod_tracker/issueattributes', $template);
    }

    public function resolution($issue) {

        $template = new StdClass;
        $template->resolution = format_text($issue->resolution, $issue->resolutionformat);

        return $this->output->render_from_template('mod_tracker/resolution', $template);
    }

    public function distribution_form($tracker, $issue, $cm) {
        global $DB;

        $template = new StdClass;

        $template->id = $issue->id;
        $template->cmid = $cm->id;
        $trackermoduleid = $DB->get_field('modules', 'id', array('name' => 'tracker'));
        if ($subtrackers = $DB->get_records('tracker', array('id' => $tracker->subtrackers), 'name', 'id,name,course')) {
            foreach ($subtrackers as $st) {
                if ($targetcm = $DB->get_record('course_modules', array('instance' => $st->id, 'module' => $trackermoduleid))) {
                    $courseshort = $DB->get_field('course', 'shortname', array('id' => $st->course));
                    $targetcontext = context_module::instance($targetcm->id);
                    $caps = array('mod/tracker:manage', 'mod/tracker:develop', 'mod/tracker:resolve');
                    if (has_any_capability($caps, $targetcontext)) {
                        $subtrackertpl = new StdClass;
                        $subtrackertpl->id = $st->id;
                        $subtrackertpl->name = $st->name;
                        $subtrackertpl->courseshort = $courseshort;
                        $template->subtrackers[] = $subtrackertpl;
                    }
                }
            }
        }

        return $this->output->render_from_template('mod_tracker/distribution_form', $template);
    }

    /**
     * prints comments for the given issue
     * @param int $issueid
     */
    public function comments($comments, $initialviewmode, $addcommentlink = '') {
        global $OUTPUT, $DB, $USER;

        $template = new StdClass;
        $template->addcommentlink = $addcommentlink;

        $template->initialcommentsviewmode = $initialviewmode;

        if ($comments) {

            $first = reset($comments);
            $cm = get_coursemodule_from_instance('tracker', $first->trackerid);
            $context = context_module::instance($cm->id);
            $template->canreport = has_capability('mod/tracker:report', $context);

            foreach ($comments as $comment) {
                $commenttpl = new StdClass;
                $user = $DB->get_record('user', array('id' => $comment->userid));
                $commenttpl->user = $this->user($user);
                $commenttpl->datecreated = userdate($comment->datecreated);
                $commenttext = file_rewrite_pluginfile_urls($comment->comment, 'pluginfile.php', $context->id,
                        'mod_tracker', 'issuecomment', $comment->id);
                $commenttpl->comment = format_text($commenttext);

                if (tracker_supports_feature('comment/branch')) {
                    $params = [
                        'what' => 'split',
                        'commentid' => $comment->id,
                        'sesskey' => sesskey(),
                        'view' => 'view',
                        'screen' => 'viewanissue',
                        't' => $comment->trackerid,
                    ];
                    $commenttpl->branchurl = new moodle_url('/mod/tracker/view.php', $params);
                }

                if (has_capability('mod/tracker:editcomment', $context) || ($comment->userid == $USER->id)) {
                    $commenttpl->canedit = true;
                    $params = [
                        'id' => $cm->id,
                        'issueid' => $comment->issueid,
                        'commentid' => $comment->id
                    ];
                    $commenttpl->editurl = new moodle_url('/mod/tracker/comment.php', $params);

                    $params = [
                        'id' => $cm->id,
                        'issueid' => $comment->issueid,
                        'view' => 'view',
                        'screen' => 'viewanissue',
                        'what' => 'deletecomment',
                        'commentid' => $comment->id,
                        'sesskey' => sesskey()
                    ];
                    $commenttpl->deleteurl = new moodle_url('/mod/tracker/view.php', $params);
                }
                $template->comments[] = $commenttpl;
            }
        }

        return $OUTPUT->render_from_template('mod_tracker/comments', $template);
    }

    public function dependencies($tracker, $issue, $initialviewmode) {

        $template = new StdClass;
        $template->initialviewmode = $initialviewmode;

        $template->parents = $this->parents($tracker, $issue->id, -20);
        $template->this = $tracker->ticketprefix.$issue->id.' - '.format_string($issue->summary);
        $template->children = $this->children($tracker, $issue->id, 20);

        return $this->output->render_from_template('mod_tracker/dependencies', $template);
    }

    protected function children(&$tracker, $issueid, $indent) {

        $statuskeys = tracker_get_statuskeys($tracker);
        $statuscodes = tracker_get_statuscodes();

        $res = tracker_get_children($tracker, $issueid);
        $children = array();
        if ($res) {
            foreach ($res as $asub) {
                $tpl = new StdClass;
                $params = array('t' => $tracker->id, 'what' => 'viewanissue', 'issueid' => $asub->id);
                $issueurl = new moodle_url('/mod/tracker/view.php', $params);
                $link = '<a href="'.$issueurl.'">'.$tracker->ticketprefix.$asub->id.' - '.format_string($asub->summary).'</a>';
                $tpl->link = $link;
                $tpl->indent = $indent;
                $tpl->status = $statuscodes[$asub->status];
                $tpl->key = $statuskeys[$asub->status];
                $children[] = $tpl;
                $indent = $indent + 20;
                $subs = $this->children($tracker, $asub->id, true, $indent);
                $children = $children + $subs;
                $indent = $indent - 20;
            }
        }
        return $children;
    }

    protected function parents(&$tracker, $issueid, $indent) {

        $statuskeys = tracker_get_statuskeys($tracker);
        $statuscodes = tracker_get_statuscodes();

        $res = tracker_get_parents($tracker, $issueid);
        $parents = array();
        if ($res) {
            foreach ($res as $asup) {
                $indent = $indent - 20;
                $parents = $parents + $this->parents($tracker, $asup->id, $indent);
                $indent = $indent + 20;
                $tpl = new StdClass;
                $params = array('t' => $tracker->id, 'what' => 'viewanissue', 'issueid' => $asup->id);
                $issueurl = new moodle_url('/mod/tracker/view.php', $params);
                $link = '<a href="'.$issueurl.'">'.$tracker->ticketprefix.$asup->id.' - '.format_string($asup->summary).'</a>';
                $tpl->link = $link;
                $tpl->indent = $indent;
                $tpl->status = $statuscodes[$asup->status];
                $tpl->key = $statuskeys[$asup->status];
                $parents[] = $tpl;
            }
        }
        return $parents;
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

        $template = new StdClass;

        $template->initialviewmode = $initialviewmode;
        $template->ccedstr = get_string('cced', 'tracker');

        foreach ($ccs as $cc) {
            $cctpl = new StdClass;
            $user = $DB->get_record('user', array('id' => $cc->userid));
            $cctpl->ccuser = $this->user($user);
            $cced[] = $cc->userid;
            if (has_capability('mod/tracker:managewatches', context_module::instance($cm->id))) {
                $params = array('id' => $cm->id,
                                'view' => 'view',
                                'what' => 'unregister',
                                'issueid' => $issue->id,
                                'ccid' => $cc->userid);
                $deleteurl = new moodle_url('/mod/tracker/view.php', $params);
                $pix = $this->output->pix_icon('t/delete', get_string('delete'), 'core');
                $cctpl->deletelink = '&nbsp;<a href="'.$deleteurl.'">'.$pix.'</a>';
            }
            $template->ccs[] = $cctpl;
        }

        $context = context_module::instance($cm->id);
        if (has_capability('mod/tracker:managewatches', $context)) {
            $template->canmanagewatchers = true;
            $issueurl = new moodle_url('/mod/tracker/view.php');
            $template->issueurl = $issueurl;
            $template->cmid = $cm->id;
            $template->issueid = $issue->id;
            $template->addwatcherstr = get_string('addawatcher', 'tracker');
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
            $template->select = html_writer::select($potentialsmenu, 'ccid');
            $template->addstr = get_string('add');
        }

        return $this->output->render_from_template('mod_tracker/ccs', $template);
    }

    public function history(&$tracker, $history, $statehistory, $initialviewmode) {
        global $DB;

        $statuskeys = tracker_get_statuskeys($tracker);
        $statuscodes = tracker_get_statuscodes();

        $template = new Stdclass;

        $template->initialviewmode = $initialviewmode;
        $template->historystr = get_string('history', 'tracker');
        $template->assigneesstr = get_string('assignees', 'tracker');
        $template->bystr = get_string('by', 'tracker');

        if (!empty($history)) {
            foreach ($history as $owner) {
                $histtpl = new StdClass;
                $user = $DB->get_record('user', array('id' => $owner->userid));
                $bywhom = $DB->get_record('user', array('id' => $owner->bywhomid));

                $histtpl->date = userdate($owner->timeassigned);
                $histtpl->user = $this->user($user);
                $histtpl->username = fullname($bywhom);
                $template->history[] = $histtpl;
            }
        }

        $template->statehistorystr = get_string('statehistory', 'tracker');

        if (!empty($statehistory)) {
            foreach ($statehistory as $state) {
                $histtpl = new Stdclass;
                $bywhom = $DB->get_record('user', array('id' => $state->userid));
                $histtpl->statedate = userdate($state->timechange);
                $histtpl->changer = $this->user($bywhom);
                $histtpl->codefrom = $statuscodes[$state->statusfrom];
                $histtpl->keyfrom = $statuskeys[$state->statusfrom];
                $histtpl->codeto = $statuscodes[$state->statusto];
                $histtpl->keyto = $statuskeys[$state->statusto];
                $template->statehistory[] = $histtpl;
            }
        }

        return $this->output->render_from_template('mod_tracker/history', $template);
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
                        $taburl = new moodle_url('/mod/tracker/pro/view.php', $params);
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


    public function edit_option_form(&$cm, &$form, $action, $errors = null) {

        $template = new StdClass;

        $template->formurl = new moodle_url('/mod/tracker/view.php');
        $template->id = $cm->id;
        $template->action = $action;
        $template->actionstr = get_string($action.'option', 'tracker');
        $template->type = $form->type;
        $template->elementid = $form->elementid;
        $template->optionid = @$form->optionid;

        $template->errorclassname = print_error_class($errors, 'name');
        $template->name = @$form->name;
        $template->errorclassdescription = print_error_class($errors, 'description');
        // $template->filtereddesc = htmlspecialchars(stripslashes(@$form->description));
        $template->filtereddesc = @$form->description;

        $template->jshandler = 'document.forms[\'editoptionform\'].what.value = \'\';';
        $template->jshandler .= 'document.forms[\'editoptionform\'].submit();';

        return $this->output->render_from_template('mod_tracker/editoptionsform', $template);
    }

    public function option_list_view(&$cm, &$element) {
        global $COURSE, $DB;

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

                $usingissues = $DB->get_records('tracker_issueattribute', array('elementitemid' => $option->id), 'id', '*', 0, 1);
                if (empty($usingissues)) {
                    $params = array('id' => $cm->id,
                                    'view' => 'admin',
                                    'what' => 'deleteelementoption',
                                    'optionid' => $option->id,
                                    'elementid' => $option->elementid);
                    $deleteurl = new moodle_url('/mod/tracker/view.php', $params);
                    $pix = $this->output->pix_icon('/t/delete', '', 'core');
                    $actions .= '<a href="'.$deleteurl.'" title="'.get_string('delete').'">'.$pix.'</a>';
                } else {
                    $firstusing = array_shift($usingissues);
                    $pix = $this->output->pix_icon('/t/delete', '', 'core');
                    $str = get_string('cannotdeleteoption', 'tracker');
                    $params = ['id' => $cm->id, 'view' => 'view', 'screen' => 'viewanissue', 'issueid' => $firstusing->id];
                    $firstuseurl = new moodle_url('/mod/tracker/view.php', $params);
                    $actions .= '<a target="_blank" href="'.$firstuseurl.'" class="shadowed" title="'.$str.'">'.$pix.'</a>';
                }

                $rowlabel = '<b> '.get_string('option', 'tracker').' '.$option->sortorder.':</b>';
                // $table->data[] = array($rowlabel, $option->name, format_string($option->description, true, $COURSE->id), $actions);
                $table->data[] = array($rowlabel, $option->name, format_text($option->description, FORMAT_HTML), $actions);
            }
        }
        return html_writer::table($table);
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

    public function status_listform_part(&$tracker, &$cm, &$issue, &$context) {

        static $fullstatuskeys;
        static $statuskeys;
        static $statuscodes;

        if (!isset($fullstatuskeys)) {
            $fullstatuskeys = tracker_get_statuskeys($tracker);
            $statuskeys = tracker_get_statuskeys($tracker, $cm);
            $statuskeys[0] = get_string('nochange', 'tracker');
            $statuscodes = tracker_get_statuscodes();
        }

        $template = new Stdclass;
        $template->code = $statuscodes[$issue->status];
        $template->static = $fullstatuskeys[0 + $issue->status];

        if (has_capability('mod/tracker:manage', $context)) {
            $template->editlink = true;
        } else if (has_capability('mod/tracker:develop', $context)) {
            $template->editlink = true;
        } else if (has_capability('mod/tracker:resolve', $context)) {
            $template->button = true;
        }

        /*
        $rendered = $this->output->render_from_template('mod_tracker/listformstatuspart', $template);
        return array($template->code, $rendered);
        */
        return $template;
    }

    public function assignedto_listform_part(&$issue, &$context) {
        global $DB;

        $template = new StdClass;

        if (empty($issue->assignedto)) {
            $template->unassigned = true;
        } else {
            $user = $DB->get_record('user', array('id' => $issue->assignedto));
            $template->assignedto = fullname($user);
        }

        $systemcontext = context_system::instance();

        if (has_capability('moodle/site:config', $systemcontext)) {
            $template->editlink = true;
            $template->giveto = 'all';
        } else if (has_capability('mod/tracker:manage', $context)) {
            $template->editlink = true;
            $template->giveto = 'developers';
        } else if (has_capability('mod/tracker:develop', $context)) {
            $template->editlink = true;
            $template->giveto = 'managers';
        }
        return $template;
    }

    public function controls_listform_part(&$cm, &$issue, &$context) {
        global $DB, $USER;

        $screen = optional_param('screen', '', PARAM_TEXT);

        $actions = '';
        if (has_capability('mod/tracker:manage', $context)) {
            $params = array('id' => $cm->id, 'issueid' => $issue->id, 'what' => 'delete');
            $deleteurl = new moodle_url('/mod/tracker/view.php', $params);
            $alt = get_string('delete');
            $pix = $this->output->pix_icon('t/delete', $alt, 'core');
            $actions .= '&nbsp;<a href="'.$deleteurl.'" title="'.$alt.'" >'.$pix.'</a>';
        }

        if (!$DB->get_record('tracker_issuecc', array('trackerid' => $issuer->trackerid, 'userid' => $USER->id, 'issueid' => $issue->id))) {
            $params = array('id' => $cm->id,
                            'view' => 'profile',
                            'screen' => $screen,
                            'issueid' => $issue->id,
                            'what' => 'register');
            $registerurl = new moodle_url('/mod/tracker/view.php', $params);
            $alt = get_string('register', 'tracker');
            $pix = $this->output->pix_icon('register', $alt, 'mod_tracker');
            $actions .= '&nbsp;<a href="'.$registerurl.'" title="'.$alt.'" >'.$pix.'</a>';
        }

        $sort = optional_param('sort', 'resolutionpriority', PARAM_TEXT);
        if (preg_match('/^resolutionpriority/', $sort)) {
            $actions .= $this->prioritycontrols_listform_part($cm, $issue, $context);
        }

        return $actions;
    }

    public function prioritycontrols_listform_part(&$cm, &$issue, &$context) {

        $actions = '';

        if (has_capability('mod/tracker:managepriority', $context)) {

            if ($issue->resolutionpriority < $issue->maxpriority) {
                $params = array('id' => $cm->id, 'issueid' => $issue->id, 'what' => 'raisetotop');
                $raiseurl = new moodle_url('/mod/tracker/view.php', $params);
                $alt = get_string('raisetotop', 'tracker');
                $pix = $this->output->pix_icon('totop', $alt, 'mod_tracker');
                $actions .= '&nbsp;<a href="'.$raiseurl.'" title="'.$alt.'" >'.$pix.'</a>';

                $params = array('id' => $cm->id, 'issueid' => $issue->id, 'what' => 'raisepriority');
                $rpurl = new moodle_url('/mod/tracker/view.php', $params);
                $alt = get_string('raisepriority', 'tracker');
                $pix = $this->output->pix_icon('up', $alt, 'mod_tracker');
                $actions .= '&nbsp;<a href="'.$rpurl.'" title="'.$alt.'" >'.$pix.'</a>';
            } else {
                $actions .= '&nbsp;'.$this->output->pix_icon('up_shadow', '', 'mod_tracker');
                $actions .= '&nbsp;'.$this->output->pix_icon('totop_shadow', '', 'mod_tracker');
            }

            if ($issue->resolutionpriority > 1) {
                $params = array('id' => $cm->id, 'issueid' => $issue->id, 'what' => 'lowerpriority');
                $lowerurl = new moodle_url('/mod/tracker/view.php', $params);
                $alt = get_string('lowerpriority', 'tracker');
                $pix = $this->output->pix_icon('down', $alt, 'mod_tracker');
                $actions .= '&nbsp;<a href="'.$lowerurl.'" title="'.$alt.'" >'.$pix.'</a>';

                $params = array('id' => $cm->id, 'issueid' => $issue->id, 'what' => 'lowerpriority');
                $lburl = new moodle_url('/mod/tracker/view.php', $params);
                $alt = get_string('lowertobottom', 'tracker');
                $pix = $this->output->pix_icon('tobottom', $alt, 'mod_tracker');
                $actions .= '&nbsp;<a href="'.$lburl.'" title="'.$alt.'" ></a>';
            } else {
                $actions .= '&nbsp;'.$this->output->pix_icon('down_shadow', '', 'mod_tracker');
                $actions .= '&nbsp;'.$this->output->pix_icon('tobottom_shadow', '', 'mod_tracker');
            }
        }

        return $actions;
    }

    public function list_edit_form($args) {
        global $DB;

        static $fullstatuskeys;
        static $statuskeys;
        static $statuscodes;

        $cmid = $DB->get_field('context', 'instanceid', array('id' => $args->ctx));
        $context = context_module::instance($cmid);
        if (!$context) {
            throw new MoodleException('Unkown context ID '.$args->ctx);
        }
        $systemcontext = context_system::instance();

        if ($args->mode == 'assignedto') {
            $usermenu = array();
            if (has_capability('moodle/site:config', $systemcontext)) {
                if ($developers = tracker_getdevelopers($context)) {
                    foreach ($developers as $developer) {
                        $usersmenu[$developer->id] = fullname($developer);
                    }
                }
                $managers = tracker_getadministrators($context);
                if ($developers = tracker_getdevelopers($context)) {
                    foreach ($managers as $manager) {
                        $usersmenu[$manager->id] = fullname($manager);
                    }
                }
            } else if (has_capability('mod/tracker:manage', $context)) {
                // Managers can assign bugs to any developer.
                if ($developers = tracker_getdevelopers($context)) {
                    foreach ($developers as $developer) {
                        $usersmenu[$developer->id] = fullname($developer);
                    }
                }
            } else if (has_capability('mod/tracker:develop', $context)) {
                // Developers can giveback a bug back to managers.
                $managers = tracker_getadministrators($context);
                if ($developers = tracker_getdevelopers($context)) {
                    foreach ($managers as $manager) {
                        $usersmenu[$manager->id] = fullname($manager);
                    }
                }
            }

            if (!empty($usersmenu)) {
                $issue = $DB->get_record('tracker_issue', array('id' => $args->id));

                $template->select = '<select id="assignedto-select-'.$issue->id.'" name="assignedto-'.$issue->id.'" class="select selectpicker">'."\n";
                $selected = (empty($issue->assignedto)) ? 'selected="selected"' : '';
                $template->select .= '<option value="0" '.$selected.'>'.get_string('unassigned', 'tracker').'</option>'."\n";
                foreach ($usersmenu as $k => $v) {
                    $selected = ($issue->assignedto == $k) ? 'selected="selected"' : '';
                    $template->select .= '<option value="'.$k.'" '.$selected.'>'.$v.'</option>'."\n";
                }
                $template->select .= '</select>'."\n";
            }
        } else if ($args->mode == 'status') {

            $cm = $DB->get_record('course_modules', array('id' => $context->instanceid));
            $tracker = $DB->get_record('tracker', array('id' => $cm->instance));
            $issue = $DB->get_record('tracker_issue', array('id' => $args->id));

            if (!isset($fullstatuskeys)) {
                $fullstatuskeys = tracker_get_statuskeys($tracker);
                $statuskeys = tracker_get_statuskeys($tracker, $cm);
                $statuscodes = tracker_get_statuscodes();
            }

            $template = new Stdclass;
            $template->code = $statuscodes[$issue->status];
            $template->static = $fullstatuskeys[0 + $issue->status];

            if (has_capability('mod/tracker:manage', $context) ||
                    has_capability('mod/tracker:develop', $context)) {
                $hasselect = true;
            }
            if ($hasselect) {
                $template->select = '<select id="status-select-'.$issue->id.'" name="status-'.$issue->id.'" class="select selectpicker">'."\n";
                foreach ($statuskeys as $k => $v) {
                    $selected = ($issue->status == $k) ? 'selected="selected"' : '';
                    $template->select .= '<option value="'.$k.'" '.$selected.' data-content="<span class=\'status-'.$statuscodes[$k].'\'>'.$v.'</span>">'.$v.'</option>'."\n";
                }
                $template->select .= '</select>'."\n";
            }
        }

        $str = $this->output->render_from_template('mod_tracker/listeditform', $template);
        return $str;
    }

    public static function select(array $options,
                                  $name,
                                  $selected = '',
                                  $nothing = array('' => 'choosedots'),
                                  array $attributes = null,
                                  $classprefix = '',
                                  $classvaluemapping = null) {

        $attributes = (array)$attributes;
        if (is_array($nothing)) {
            foreach ($nothing as $k => $v) {
                if ($v === 'choose' || $v === 'choosedots') {
                    $nothing[$k] = get_string('choosedots');
                }
            }
            $options = $nothing + $options; // Keep keys, do not override.

        } else if (is_string($nothing) && $nothing !== '') {
            // BC
            $options = array('' => $nothing) + $options;
        }

        // We may accept more values if multiple attribute specified.
        $selected = (array)$selected;
        foreach ($selected as $k => $v) {
            $selected[$k] = (string)$v;
        }

        if (!isset($attributes['id'])) {
            $id = 'menu'.$name;
            // Name may contain [], which would make an invalid id. e.g. numeric question type editing form, assignment quickgrading.
            $id = str_replace('[', '', $id);
            $id = str_replace(']', '', $id);
            $attributes['id'] = $id;
        }

        if (!isset($attributes['class'])) {
            $class = 'menu'.$name;
            // Name may contaion [], which would make an invalid class. e.g. numeric question type editing form, assignment quickgrading.
            $class = str_replace('[', '', $class);
            $class = str_replace(']', '', $class);
            $attributes['class'] = $class;
        }
        $attributes['class'] = 'select ' . $attributes['class']; // Add 'select' selector always.

        $attributes['name'] = $name;

        if (!empty($attributes['disabled'])) {
            $attributes['disabled'] = 'disabled';
        } else {
            unset($attributes['disabled']);
        }

        $output = '';
        foreach ($options as $value => $label) {
            if (is_array($label)) {
                // Ignore key, it just has to be unique.
                $output .= html_writer::select_optgroup(key($label), current($label), $selected);
            } else {
                if (is_array($classvaluemapping) && array_key_exists($value, $classvaluemapping)) {
                    $optionclass = $classprefix.$classvaluemapping[$value];
                } else {
                    $optionclass = $classprefix.$value;
                }
                $output .= self::select_option($label, $value, $selected, $optionclass);
            }
        }
        return html_writer::tag('select', $output, $attributes);
    }

    /**
     * Returns HTML to display a select box option.
     *
     * @param string $label The label to display as the option.
     * @param string|int $value The value the option represents
     * @param array $selected An array of selected options
     * @return string HTML fragment
     */
    private static function select_option($label, $value, array $selected, $optionclass = '') {
        $attributes = array();
        $value = (string)$value;
        if (in_array($value, $selected, true)) {
            $attributes['selected'] = 'selected';
        }
        $attributes['value'] = $value;
        $attributes['class'] = $optionsclass;
        return html_writer::tag('option', $label, $attributes);
    }

    public function last_comment($lastcomment, $context) {
        global $DB;

        $commentuser = $DB->get_record('user', array('id' => $lastcomment->userid));

        $template = new StdClass;

        $comment = file_rewrite_pluginfile_urls($lastcomment->comment, 'pluginfile.php', $context->id, 'mod_tracker',
                                                    'issuecomment', $lastcomment->id);

        $template->comment = format_text($comment, $lastcomment->commentformat);
        $template->by = fullname($commentuser).' '.$this->output->user_picture($commentuser);

        return $this->output->render_from_template('mod_tracker/lastcomment', $template);
    }

    /**
     * Renders the full issue list
     */
    public function issuelist($issues, $totalcount, $cm, $tracker, $view, $screen, $resolved) {
        global $DB, $CFG;

        $context = context_module::instance($cm->id);

        $this->mustache = $this->get_mustache();
        include_once($CFG->dirroot.'/mod/tracker/classes/output/mustache_sortby_helper.php');
        $helper = new mod\tracker\mustache_sortby_helper();
        $this->mustache->addHelper('sortby', [$helper, 'sortby']);

        $baseurl = new moodle_url('/mod/tracker/view.php', array('id' => $cm->id, 'view' => $view, 'screen' => $screen));

        $select = " trackerid = ? GROUP BY trackerid ";
        $maxpriority = $DB->get_field_select('tracker_issue', 'MAX(resolutionpriority)', $select, array($tracker->id));

        $template = new Stdclass;
        $template->cmid = $cm->id;
        $template->screen = $screen;
        $template->prefix = $tracker->ticketprefix;
        $template->trackerid = $tracker->id;

        // Listable column name.
        $template->haslistables = $tracker->haslistables;
        if (!empty($tracker->listables)) {
            foreach ($tracker->listables as $listable) {
                $listabletpl = new StdClass;
                $listabletpl->name = $listable->name;
                $listabletpl->description = $listable->description;
                $template->listables[] = $listabletpl;
            }
        }

        $page = optional_param('page', 0, PARAM_INT);
        $pagedurl = new moodle_url('/mod/tracker/view.php', ['id' => $cm->id, 'view' => $view, 'screen' => $screen]);
        if ($totalcount > TRACKER_LIST_PAGE_SIZE) {
            $template->pager = $this->output->paging_bar($totalcount, $page , TRACKER_LIST_PAGE_SIZE, $pagedurl, 'page');
        }

        $template->formurl = new moodle_url('/mod/tracker/view.php');
        if ($resolved) {
            $template->priority = false;
            $template->transferable = false;
        }

        if ($screen == 'mywork') {
            $template->ismywork = true;
        }

        if ($screen == 'mytickets') {
            $template->ismytickets = true;
        }

        if (!empty($issues)) {
            // Product data for table.
            foreach ($issues as $issue) {

                $issuetpl = new Stdclass;

                $issuetpl->id = $issue->id;
                $issuetpl->fullid = $tracker->ticketprefix.$issue->id;
                $issuetpl->contextid = $context->id;

                // Issue number.
                $params = array('id' => $cm->id, 'view' => 'view', 'screen' => 'viewanissue', 'issueid' => $issue->id);
                $issuetpl->issueurl = new moodle_url('/mod/tracker/view.php', $params);
                $issuetpl->issuenumber = '<a href="'.$issuetpl->issueurl.'">'.$tracker->ticketprefix.$issue->id.'</a>';

                // Issue summary.
                $issuetpl->summary = format_string($issue->summary);

                // Issue date.
                $issuetpl->datereported = date('Y/m/d H:i', $issue->datereported);

                // Issue reporter.
                $user = $DB->get_record('user', array('id' => $issue->reportedby));
                $issuetpl->reportedby = fullname($user);

                // Listable fields.
                if ($tracker->haslistables) {
                    foreach ($tracker->listables as $listable) {
                        $listabletpl = new StdClass;
                        $listabletpl->name = $listable->name;
                        $listabletpl->value = $listable->view($issue->id);
                        $issuetpl->listables[] = $listabletpl;
                    }
                }

                // Issue assigned.
                $issuetpl->assignedto = $this->assignedto_listform_part($issue, $context);

                // Issue status.
                $issuetpl->status = $this->status_listform_part($tracker, $cm, $issue, $context);

                $issuetpl->watches = $issue->watches;

                // Issue solved signal.
                $hassolution = $issue->status == RESOLVED && !empty($issue->resolution);
                $alt = get_string('hassolution', 'tracker');
                $pix = $this->output->pix_icon('solution', $alt, 'mod_tracker');
                $issuetpl->solution = ($hassolution) ? $pix : '';

                // Issue controls.
                $issue->maxpriority = $maxpriority;
                $issuetpl->controls = $this->controls_listform_part($cm, $issue, $context);

                if (!empty($tracker->parent)) {
                    $issuetpl->transfer = ($issue->status == TRANSFERED) ? tracker_print_transfer_link($tracker, $issue) : '';
                }

                // Compose final dataset.
                if (!$resolved) {
                    $issuetpl->priority = $maxpriority - $issue->resolutionpriority + 1;
                }
                $template->issues[] = $issuetpl;
            }

            if (tracker_can_workon($tracker, $context)) {
                $template->cansubmit = true;
            }
        } else {
            $template->emptylist = true;
            if (!$resolved) {
                $str = get_string('noissuesreported', 'tracker');
                $template->emptynotification = $this->output->notification($str, 'box generalbox', 'notice');
            } else {
                $str = get_string('noissuesresolved', 'tracker');
                $template->emptynotification = $this->output->notification($str, 'box generalbox', 'notice');
            }
        }

        return $this->render_from_template('mod_tracker/issuelistform', $template);
    }

    public function sorticons($columnname) {
        global $FULLME;

        $sort = optional_param('sort', 'datereported DESC', PARAM_TEXT);
        list($sortby, $dir) = explode(' ', $sort);
        $template = new Stdclass;
        $class = '';
        if ($sortby == $columnname) {
            if ($dir == 'DESC') {
                $icon = $this->output->pix_icon('t/sort_desc', '', 'core');
                $sort = "$columnname ASC";
            } else {
                $icon = $this->output->pix_icon('t/sort_asc', '', 'core');
                $sort = "$columnname DESC";
            }
        } else {
            $icon = $this->output->pix_icon('t/sort', '', 'core');
            $sort = "$columnname ASC";
            $class = 'dimmed';
        }

        $meurl = new moodle_url($FULLME);
        $meurl->params(['sort' => $sort]);
        return html_writer::tag('a', $icon, ['href' => $meurl, 'class' => $class]);
    }

    /**
     * Renders a template by name with the given context.
     *
     * The provided data needs to be array/stdClass made up of only simple types.
     * Simple types are array,stdClass,bool,int,float,string
     *
     * @since 2.9
     * @param array|stdClass $context Context containing data for the template.
     * @return string|boolean
     */
    public function render_from_template($templatename, $context) {
        static $templatecache = array();

        try {
            // Grab a copy of the existing helper to be restored later.
            $uniqidhelper = $this->mustache->getHelper('uniqid');
        } catch (Mustache_Exception_UnknownHelperException $e) {
            // Helper doesn't exist.
            $uniqidhelper = null;
        }

        // Provide 1 random value that will not change within a template
        // but will be different from template to template. This is useful for
        // e.g. aria attributes that only work with id attributes and must be
        // unique in a page.
        $this->mustache->addHelper('uniqid', new \core\output\mustache_uniqid_helper());
        if (isset($templatecache[$templatename])) {
            $template = $templatecache[$templatename];
        } else {
            try {
                $template = $this->mustache->loadTemplate($templatename);
                $templatecache[$templatename] = $template;
            } catch (Mustache_Exception_UnknownTemplateException $e) {
                throw new moodle_exception('Unknown template: ' . $templatename);
            }
        }

        $renderedtemplate = trim($template->render($context));

        // If we had an existing uniqid helper then we need to restore it to allow
        // handle nested calls of render_from_template.
        if ($uniqidhelper) {
            $this->mustache->addHelper('uniqid', $uniqidhelper);
        }

        return $renderedtemplate;
    }
}