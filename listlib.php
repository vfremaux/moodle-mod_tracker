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
 * Version details
 *
 * @package    block_course_ascendants
 * @category   blocks
 * @copyright  2012 onwards Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_tracker;

defined('MOODLE_INTERNAL') || die();

class datalist {

    protected $table;

    protected $itemidfield;

    protected $orderfield;

    protected $context;

    public function __construct($table, $itemidfield, $orderfield, $listcontext) {
        $this->table = $table;
        $this->itemidfield = $itemidfield;
        $this->orderfield = $orderfield;
        $this->context = $listcontext;
    }

    public function up($itemid) {
        global $DB;

        $idfield = $this->itemidfield;
        $orderfield = $this->orderfield;

        $params = array($idfield => $itemid);
        $this->add_context($params);
        $item = $DB->get_record($this->table, $params);
        $params = array($idfield => $item->$idfield, $orderfield => $item->$orderfield + 1);
        if (!$nextitem = $DB->get_record($this->table, $params)) {
            return;
        }
        $nextitem->$orderfield--;
        $item->$orderfield++;
        $DB->update_record($this->table, $item);
        $DB->update_record($this->table, $nextitem);
    }

    public function down($itemid) {
        global $DB;

        $idfield = $this->itemidfield;
        $orderfield = $this->orderfield;

        $params = array($idfield => $itemid);
        $this->add_context($params);
        $item = $DB->get_record($this->table, $params);
        if ($item->$orderfield == 0) {
            return;
        }
        $params = array($idfield => $item->$idfield, $orderfield => $item->$orderfield - 1);
        $this->add_context($params);
        $previtem = $DB->get_record($this->table, $params);
        if (!empty($previtem)) {
            $previtem->$orderfield++;
            $item->$orderfield--;
            $DB->update_record($this->table, $item);
            $DB->update_record($this->table, $previtem);
        }
    }

    public function last_order($itemid) {
        global $DB;

        $params = array($this->itemidfield => $itemid);
        $this->add_context($params);
        $lastorder = $DB->get_field($this->table, 'MAX('.$this->orderfield.')', $params);
        return $lastorder;
    }

    public function remove($itemid) {
        global $DB;

        $idfield = $this->itemidfield;
        $orderfield = $this->orderfield;

        $params = array($idfield => $itemid);
        $this->add_context($params);
        $oldorder = $DB->get_field($this->table, $orderfield, $params);
        $DB->delete_records($this->table, $params);
        $sql = "
            UPDATE
                {".$this->table."} bas
            SET
                $orderfield = $orderfield - 1
            WHERE
                $idfield = ? AND
                $orderfield > ?
        ";
        $DB->execute($sql, array($itemid, $oldorder));
    }

    /**
     * Add the context to the query params
     */
    protected function add_context(&$params) {
        if (!empty($this->context)) {
            foreach ($this->context as $field => $value) {
                $params[$field] = $value;
            }
        }
    }
}