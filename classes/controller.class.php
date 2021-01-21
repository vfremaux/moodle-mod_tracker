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
 * @package  mod_tracker
 * @category mod
 * @author   Valery Fremaux
 *
 * A common base class for all tracker controlers.
 *
 */
namespace mod_tracker;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/tracker/listlib.php');

use Stdclass;
use Exception;

class base_controller {

    public $data;

    /**
     * Boolean status. Tells if controller is loaded with data.
     */
    protected $received;

    /**
     * Boolean status. Tells that controller or child class has processed the usecase.
     */
    protected $done;

    /**
     * The course tracker instance
     */
    protected $tracker;

    /**
     * The course module instance
     */
    protected $cm;

    /**
     * return url for error sideways.
     */
    protected $url;

    /**
     * If controller needs to produce some output, put it in this var
     */
    public $out;

    public function __construct(&$tracker, $cm, $url = '') {
        $this->tracker = $tracker;
        $this->cm = $cm;
        $this->url = $url;
        $this->received = false;
        $this->out = '';
    }

    public function receive($cmd, $data = null) {

        if (!empty($data)) {
            $this->data = $data;
            $this->received = true;
            return true; // Tells child class we are loaded.
        } else {
            $this->data = new StdClass;
        }

        return false; // Tells child class to load it's part.
    }

    public function process($cmd) {
        global $DB, $USER;

        if (!$this->received) {
            throw new Exception("Controller invoked without data.");
        }
    }
}
