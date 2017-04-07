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
 * @package    mod_tracker
 * @category   mod
 * @author     Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class mod_tracker_reports_renderer extends \plugin_renderer_base {

    protected $tracker;
    protected $context;
    protected $statuscodes;
    protected $statuskeys;
    protected $dateiter; // Iterator.
    protected $ticketsbymonth;
    protected $ticketsprogressbymonth;
    protected $lowest;
    protected $highest;

    public function init($tracker) {

        $this->tracker = $tracker;
        $cm = get_coursemodule_from_instance('tracker', $tracker->id);
        $this->context = context_module::instance($cm->id);

        $this->statuskeys = tracker_get_statuskeys($tracker);
        $this->statuscodes = tracker_get_statuscodes($tracker);

        $this->ticketsbymonth = tracker_get_stats_by_month($tracker);
        $this->ticketsprogressbymonth = tracker_backtrack_stats_by_month($tracker);

        if (!empty($this->ticketsbymonth)) {
            $ticketdates = $this->ticketsbymonth;
            unset($ticketdates['sum']);
            $availdates = array_keys($ticketdates);
            if (!empty($availdates)) {
                $this->lowest = $availdates[0];

                $this->highest = $availdates[count($availdates) - 1];
                $low = new StdClass();
                list($low->year, $low->month) = explode('-', $this->lowest);
            }

            $this->dateiter = new date_iterator($low->year, $low->month);
            $this->colwidth = 60 / $this->dateiter->getiterations($this->highest);
        }
    }

    public function counters($alltickets) {

        $str = '<div class="container-fluid">';
        $str .= '<div class="row-fluid">';
        $str .= '<div class="span12 col-12">';
        $str .= $this->output->heading(get_string('countbymonth', 'tracker', $alltickets), 3);

        $str .= $this->count_by_month(true, $this->ticketsbymonth);

        $str .= $this->count_by_month(false, $this->ticketsbymonth);

        $str .= $this->count_new();

        $str .= '</div>';
        $str .= '</div>';
        $str .= '</div>';

        return $str;
    }

    public function evolution($alltickets) {

        $str = '<table width="100%" cellpadding="5">';
        $str .= '<tr valign="top">';
        $str .= '<td>';
        $str .= $this->output->heading(get_string('evolutionbymonth', 'tracker', $alltickets), 3);
        $str .= $this->count_by_month(false, $this->ticketsprogressbymonth);
        $str .= $this->count_by_month(true, $this->ticketsprogressbymonth);

        $str .= $this->progress_trends();

        foreach ($this->totalsum as $k => $v) {
            $data[0][] = array($k, $this->trendsum[$k]);
            $data[1][] = array($k, $this->testsum[$k]);
            $data[2][] = array($k, $this->ressum[$k]);
        }
        $jqplot = array(
            'title' => array(
                'text' => get_string('generaltrend', 'tracker'),
                'fontSize' => '1.3em',
                'color' => '#000080',
                ),
            'legend' => array(
                'show' => true,
                'location' => 'e',
                'placement' => 'outsideGrid',
                'showSwatch' => true,
                'marginLeft' => '10px',
                'border' => '1px solid #808080',
                'labels' => array(get_string('activeplural', 'tracker'),
                                  get_string('intest', 'tracker'),
                                  get_string('resolvedplural2', 'tracker')),
            ),
            'axesDefaults' => array('labelRenderer' => '$.jqplot.CanvasAxisLabelRenderer'),
            'axes' => array(
                'xaxis' => array(
                    'label' => get_string('month', 'tracker'),
                    'renderer' => '$.jqplot.CategoryAxisRenderer',
                    'pad' => 0
                    ),
                'yaxis' => array(
                    'autoscale' => true,
                    'tickOptions' => array('formatString' => '%2d'),
                    'rendererOptions' => array('forceTickAt0' => true),
                    'label' => get_string('tickets', 'tracker'),
                    'labelRenderer' => '$.jqplot.CanvasAxisLabelRenderer',
                    'labelOptions' => array('angle' => 90)
                    )
                ),
            'series' => array(
                array('color' => '#C00000'),
                array('color' => '#80FF80'),
                array('color' => '#00C000'),
            ),
        );
        local_vflibs_jqplot_print_graph('plot1', $jqplot, $data, 550, 250, 'margin-top:20px;');

        $str .= '</td>';
        $str .= '</tr>';
        $str .= '</table>';
    }

    public function count_by_month($isactive, $tickets) {

        if ($isactive) {
            $colorclass = 'red';
        } else {
            $colorclass = 'green';
        }

        $str = '';

        $str .= '<table width="95%" class="generaltable">';
        $str .= '<tr valign="top">';
        $str .= '<td width="40%" align="left">'.get_string('status', 'tracker').'</td>';
        $current = $this->dateiter->current();
        while (strcmp($current, $this->highest) <= 0) {
            $str .= '<td align="right" width="'.$this->colwidth.'%">'.$current.'</td>';
            $this->dateiter->next();
            $current = $this->dateiter->current();
        }
        $str .= '</tr>';

        foreach (array_keys($this->statuskeys) as $key) {
            if ($isactive) {
                $exclude = in_array($key, array(ABANDONNED, RESOLVED, VALIDATED, TRANSFERED));
            } else {
                $exclude = !in_array($key, array(ABANDONNED, RESOLVED, VALIDATED, TRANSFERED));
            }
            if ($exclude) {
                continue;
            }

            $str .= '<tr valign="top">';
            $str .= '<td width="40%" align="left" class="status-'.$this->statuscodes[$key].'">'.$this->statuskeys[$key].'</td>';
            $this->dateiter->reset();
            $current = $this->dateiter->current();
            $last = 0;
            while (strcmp($current, $this->highest) <= 0) {
                $str .= '<td align="right" width="'.$this->colwidth.'%">';
                $new = 0 + @$tickets[$current][$key];
                $diff = $new - $last;
                $valueclass = ($new == 0) ? 'nullclass' : '';
                $str .= '<span class="'.$valueclass.'">'.$new.'</span>';
                $str .= ' ';
                $str .= ($diff > 0) ? '<span class="'.$colorclass.'">(+'.$diff.')</span>' : '';
                $last = $new;
                $str .= '</td>';
                $this->dateiter->next();
                $current = $this->dateiter->current();
            }
            $str .= '</tr>';
        }
        $str .= '</table>';

        return $str;
    }

    public function count_new() {

        $str = '';

        $str .= '<table width="95%" class="generaltable">';
        $str .= '<tr valign="top">';
        $statusstr = get_string('createdinmonth', 'tracker', $this->ticketsbymonth['sum']);
        $str .= '<td width="40%" align="left" class="status-">'.$statusstr.'</td>';
        $this->dateiter->reset();
        $current = $this->dateiter->current();
        while (strcmp($current, $this->highest) <= 0) {
            $str .= '<td align="right" width="'.$this->colwidth.'%" class="c0 header"><b>';
            $new = 0 + @$ticketsbymonth[$current]['sum'];
            $valueclass = ($new == 0) ? 'nullclass' : '';
            $str .= '<span class="'.$valueclass.'">'.$new.'</span>';
            $str .= '</b>';
            $str .= '</td>';
            $this->dateiter->next();
            $current = $this->dateiter->current();
        }
        $str .= '</tr>';
        $str .= '</table>';

        return $str;
    }

    public function progress_trends() {

        $str = '<table width="95%" class="generaltable">';
        $str .= '<tr valign="top">';
        $str .= '<td width="40%" align="left" class="status-">'.get_string('runninginmonth', 'tracker').'</td>';

        $this->dateiter->reset();
        $current = $this->dateiter->current();
        while (strcmp($current, $this->highest) <= 0) {

            $str .= '<td align="right" width="'.$this->colwidth.'%" class="c0 header"><b>';

            $new = 0 + @$ticketsprogressbymonth[$current]['sum'];
            $this->totalsum[$current] = @$ticketsprogressbymonth[$current]['sum'];
            $this->trendsum[$current] = @$ticketsprogressbymonth[$current]['sum'] - @$ticketsprogressbymonth[$current][ABANDONNED];
            $valueclass = ($new == 0) ? 'nullclass' : '';
            $str .= '<span class="'.$valueclass.'">'.$new.'</span>';
            $str .= '</b>';
            $str .= '</td>';

            $this->dateiter->next();
            $current = $this->dateiter->current();
        }

        $str .= '</tr>';
        $str .= '<tr valign="top">';
        $str .= '<td width="40%" align="left" class="status-">'.get_string('inworkinmonth', 'tracker').'</td>';

        $this->dateiter->reset();
        $current = $this->dateiter->current();
        while (strcmp($current, $this->highest) <= 0) {

            $str .= '<td align="right" width="'.$this->colwidth.'%" class="c0 header">';

            $new = 0 + @$ticketsprogressbymonth[$current]['sumunres'];
            $this->ressum[$current] = @$ticketsprogressbymonth[$current][RESOLVED] + @$ticketsprogressbymonth[$current][ABANDONNED];
            $this->testsum[$current] = @$ticketsprogressbymonth[$current][RESOLVED] + @$ticketsprogressbymonth[$current][TESTING];
            $this->testsum[$current] += @$ticketsprogressbymonth[$current][ABANDONNED];
            $valueclass = ($new == 0) ? 'nullclass' : 'redtext';
            $str .= '<span class="'.$valueclass.'">'.$new.'</span>';

            $str .= '</td>';

            $this->dateiter->next();
            $current = $this->dateiter->current();
        }

        $str .= '</tr>';
        $str .= '<tr valign="top">';
        $str .= '<td width="40%" align="left" class="status-">'.get_string('elucidationratio', 'tracker').'</td>';

        $this->dateiter->reset();
        $current = $this->dateiter->current();
        while (strcmp($current, $this->highest) <= 0) {

            $str .= '<td align="right" width="'.$this->colwidth.'%" class="c0 header">';
            $realtickets = @$ticketsprogressbymonth[$current]['sum'] - @$ticketsprogressbymonth[$current][ABANDONNED];
            $cond = $realtickets != 0;
            $new = 0 + ($cond) ? (($realtickets - @$ticketsprogressbymonth[$current]['sumunres']) / $realtickets * 100) : 0;
            $valueclass = ($new == 0) ? 'nullclass' : '';
            $str .= '<span class="'.$valueclass.'">'.sprintf('%.1f', $new).'%</span>';
            $str .= '</td>';
            $this->dateiter->next();
            $current = $this->dateiter->current();
        }
        $str .= '</tr>';
        $str .= '</table>';

        return $str;
    }

    public function status_stats() {
        global $DB;

        $tickets = tracker_get_stats($this->tracker);
        $alltickets = $DB->count_records('tracker_issue', array('trackerid' => $this->tracker->id));

        $statsbyassignee = tracker_get_stats_by_user($this->tracker, 'assignedto');
        $statsbyreporter = tracker_get_stats_by_user($this->tracker, 'reportedby');

        $str = '';

        $str .= '<div class="container-fluid">'; // Table.
        $str .= '<div class="row-fluid">'; // Row.
        $str .= '<div class="span4 col-4">'; // Cell.

        $str .= $this->output->heading(get_string('countbystate', 'tracker', $alltickets), 3);
        $str .= $this->count_by_states(true, $tickets, $alltickets);
        $str .= $this->count_by_states(false, $tickets, $alltickets);

        $str .= '</div>'; // Cell.
        $str .= '<div class="span4 col-4">'; // Cell.

        $str .= $this->output->heading(get_string('countbyassignee', 'tracker', $alltickets), 3);
        $str .= $this->count_by_assignee($statsbyassignee, $alltickets);

        $str .= '</div>'; // Cell.
        $str .= '<div class="span4 col-4">'; // Cell.

        $str .= $this->output->heading(get_string('countbyreporter', 'tracker', $alltickets), 3);
        $str .= $this->count_by_reporter($statsbyreporter, $alltickets);

        $str .= '</div>'; // Cell.
        $str .= '</div>'; // Row.
        $str .= '</div>'; // Table.

        return $str;
    }

    public function count_by_states($isactive, $tickets, $alltickets) {

        $str = '';

        $str .= '<table width="80%" class="generaltable">';
        $str .= '<tr>';
        $str .= '<th width="40%" align="left">'.get_string('status', 'tracker').'</th>';
        $str .= '<th width="30%" align="right">'.get_string('count', 'tracker').'</th>';
        $str .= '<th width="30%" align="right"></th>';
        $str .= '</tr>';
        foreach (array_keys($this->statuskeys) as $key) {

            if ($isactive) {
                $exclude = in_array($key, array(ABANDONNED, RESOLVED, VALIDATED, TRANSFERED));
            } else {
                $exclude = !in_array($key, array(ABANDONNED, RESOLVED, VALIDATED, TRANSFERED));
            }

            if ($exclude) {
                continue;
            }
            $str .= '<tr>';
            $str .= '<td width="40%" align="left" class="status-'.$this->statuscodes[$key].'">'.$this->statuskeys[$key].'</td>';
            $str .= '<td width="30%" align="right">'.(0 + @$tickets[$key]).'</td>';
            $rate = ($alltickets) ? sprintf("%2d", ((0 + @$tickets[$key]) / $alltickets) * 100) : '0';
            $str .= '<td width="30%" align="right">'.$rate.' %</td>';
            $str .= '</tr>';
        }
        $str .= '</table>';

        return $str;
    }

    public function count_by_assignee($statsbyassignee, $alltickets) {
        if (empty($statsbyassignee)) {
            return $this->output->notification(get_string('noticketsorassignation', 'tracker'));
        } else {
            $str = '<table width="95%" class="generaltable">';
            $line = 0;
            foreach ($statsbyassignee as $r) {
                if (empty($r->name)) {
                    $r->name = get_string('unassigned', 'tracker');
                }
                $str .= '<tr class="r'.$line.'">';
                $str .= '<td width="50%" align="left">'.$r->name.'</td>';
                $str .= '<td width="10%" align="right" class="tracker-report-assignee">'.$r->sum.'</td>';
                $str .= '<td width="40%">';
                foreach ($r->status as $statkey => $subresult) {
                    $statcode = $this->statuscodes[$statkey];
                    $str .= '<span class="status-'.$statcode.'">'.$subresult.'</span> ';
                }
                $str .= '</td>';

                $str .= '</tr>';
                $line = ($line + 1) % 2;
            }
            $str .= '</table>';

            return $str;
        }
    }

    public function count_by_reporter($statsbyreporter, $alltickets) {
        if (empty($statsbyreporter)) {
            return $this->output->notification(get_string('notickets', 'tracker'));
        } else {
            $str = '<table width="95%" class="generaltable">';
            $line = 0;
            foreach ($statsbyreporter as $r) {
                $str .= '<tr class="r'.$line.'">';
                $str .= '<td width="50%" align="left">'.$r->name.'</td>';
                $str .= '<td width="10%" align="right" class="report-status-reporter">'.$r->sum.'</td>';
                $str .= '<td width="40%">';
                foreach ($r->status as $statkey => $subresult) {
                    $statcode = $this->statuscodes[$statkey];
                    $str .= '<span class="status-'.$statcode.'">'.$subresult.'</span> ';
                }
                $str .= '</td>';
                $str .= '</tr>';
                $line = ($line + 1) % 2;
            }
            $str .= '</table>';

            return $str;
        }
    }
}