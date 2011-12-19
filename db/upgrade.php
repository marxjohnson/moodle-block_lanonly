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
 * Defines the function for upgrading the block from Moodle 1.9 installs
 *
 * @package    block_lanonly
 * @author      Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @copyright   2010 Tauntons College, UK
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Based on the HTML block's upgrade function, converts existing block instances to 2.x style
 *
 * @param mixed $oldversion
 * @access public
 * @return bool
 */
function xmldb_block_lanonly_upgrade($oldversion) {

    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2010112100) {
        $params = array();
        $sql = "SELECT * FROM {block_instances} b WHERE b.blockname = :blockname";
        $params['blockname'] = 'lanonly';
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $record) {
            $config = unserialize(base64_decode($record->configdata));
            if (!empty($config) && is_object($config)) {
                if (!empty($config->lantext) || !empty($config->notlantext)) {
                    $data = clone($config);
                    $config->text_onsite = $data->lantext;
                    $config->title_onsite = $data->lantitle;
                    $config->format_onsite = FORMAT_HTML;
                    $config->text_offsite = $data->notlantext;
                    $config->format_offsite = $data->notlantitle;
                    $config->format_offsite = FORMAT_HTML;
                    unset(
                        $config->lantext,
                        $config->lantitle,
                        $config->notlantext,
                        $config->notlantitle
                    );
                    $record->configdata = base64_encode(serialize($config));
                    $DB->update_record('block_instances', $record);
                }
            }
        }
        $rs->close();

        /// html block savepoint reached
        upgrade_block_savepoint(true, 2010112100, 'lanonly');
    }

    return true;
}
