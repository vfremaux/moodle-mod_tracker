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
 * @package      mod_tracker
 * @category     mod
 * @author       Clifford Tham, Valery Fremaux > 1.8
 *
 * Prints a form for user preferences.
 */
defined('MOODLE_INTERNAL') || die();

$mywatches = tracker_getwatches($tracker->id, $USER->id);

echo $output;
echo $OUTPUT->heading(get_string('mywatches', 'tracker'));

echo $OUTPUT->box_start('center', '80%', '', '', 'generalbox', 'bugreport');

if (empty($mywatches)) {
    echo $OUTPUT->notification(get_string('nowatches', 'tracker'));
} else {
    $idstr = get_string('id', 'tracker');
    $summarystr = get_string('summary', 'tracker');
    $peoplestr = get_string('observers', 'tracker');
    $actionstr = get_string('action', 'tracker');
    $notificationstr = get_string('notifications', 'tracker');
    $table = new html_table();
    $table->head = array("<b>$idstr</b>",
                         "<b>$summarystr</b>",
                         "<b>$peoplestr</b>",
                         "<b>$actionstr</b>",
                         "<b>$notificationstr</b>");
    $table->size = array('10%', '50%', '10%', '10%', '%20');
    $table->align = array('left', 'left', 'center', 'center', 'center');
    foreach ($mywatches as $awatch) {
        $params = array('id' => $cm->id,
                        'view' => 'profile',
                        'what' => 'unregister',
                        'issueid' => $awatch->issueid,
                        'ccid' => $awatch->userid);
        $unregisterurl = new moodle_url('/mod.tracker/view.php', $params);
        $alt = get_string('delete');
        $pix = $OUTPUT->pix_icon('t/delete', $alt, 'core');
        $actions = '<a href="'.$unregister.'" title="'.$alt.'">'.$pix.'</a>';

        $params = array('id' => $cm->id, 'view' => 'profile', 'what' => 'editwatch', 'ccid' => $awatch->userid);
        $updateurl = new moodle_url('/mod/tracker/view.php', $params);
        $alt = get_string('update');
        $pix = $OUTPUT->pix_icon('t/edit', $alt, 'core');
        $actions .= '&nbsp;<a href="'.$updateurl.'" title="'.$alt.'">'.$pix.'</a>';

        $states = array(
            ENABLED_OPEN => array('open', 'setwhenopens', 'unsetwhenopens'),
            ENABLED_RESOLVING => array('resolving', 'setwhenworks', 'unsetwhenworks'),
            ENABLED_WAITING => array('waiting', 'setwhenwaits', 'unsetwhenwaits'),
            ENABLED_TESTING => array('testing', 'setwhentesting', 'unsetwhentesting'),
            ENABLED_PUBLISHED => array('published', 'setwhenpublished', 'unsetwhenpublished'),
            ENABLED_RESOLVED => array('resolved', 'setwhenpublished', 'unsetwhenpublished'),
            ENABLED_ABANDONNED => array('abandonned', 'setwhenthrown', 'unsetwhenthrown'),
            ON_COMMENT => array('oncomment', 'setoncomment', 'unsetoncomment'),
        );

        foreach ($states as $statekey => $state) {
            if ($tracker->enabledstates & $statekey) {
                $pixurl = $OUTPUT->image_url($state[0], 'mod_tracker');
                if ($awatch->events & $statekey) {
                    $pix = '<img id="watch-'.$state[0].'-img" class="" src="'.$pixurl.'" />';
                    $seturl = 'javascript:updatewatch('.$cm->id.', '.$awatch->id.', \''.$state[0].'\', 0, \''.sesskey().'\')';
                    $notifications = '&nbsp;<a href="'.$seturl.'" title="'.get_string($state[2], 'tracker').'">'.$pix.'</a>';
                } else {
                    $pix = '<img id="watch-'.$state[0].'-img" class="tracker-shadow" src="'.$pixurl.'" />';
                    $seturl = 'javascript:updatewatch('.$cm->id.', '.$awatch->id.', \''.$state[0].'\', 1, \''.sesskey().'\')';
                    $notifications = '&nbsp;<a href="'.$seturl.'" title="'.get_string($state[1], 'tracker').'\>'.$pix.'</a>';
                }
            }
        }

        $params = array('id' => $cm->id, 'view' => 'view', 'screen' => 'viewanissue', 'issueid' => $awatch->issueid);
        $watchurl = new moodle_url('/mod.tracker/view.php', $params);
        $watchid = '<a href="'.$watchurl.'">'.$tracker->ticketprefix.$awatch->issueid.'</a>';

        $table->data[] = array($watchid, $awatch->summary, $awatch->people, $actions, $notifications);
    }
    echo html_writer::table($table);
}

echo $OUTPUT->box_end();
