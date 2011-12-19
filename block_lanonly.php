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
 * Defines the class for the On-Site/Off-Site block
 *
 * @package    block_lanonly
 * @author      Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @copyright   2010 Tauntons College, UK
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class definition for the On-Sire/Off-Site block
 *
 * This is based on {@see block_html}, but uses IP address detection to determine if the user is
 * requesting the page from an internal network, and displays alternative content if they are.
 *
 * @uses block_base
 */
class block_lanonly extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_lanonly');
    }

    public function preferred_width() {
        // The preferred value is in pixels
        return 250;
    }

    public function applicable_formats() {
        return array('all' => true);
    }

    public function instance_allow_multiple() {
        return true;
    }


    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        if ($this->is_local()) {

            if (empty($this->config)) {
                $title = get_string('newblock', 'block_lanonly');
                $text = '';
                $format = '';
            } else {
                $title = $this->config->title_onsite;
                $text = $this->config->text_onsite;
                $format = $this->config->format_onsite;
            }

        } else {

            if (empty($this->config)) {
                $title = get_string('newblock', 'block_lanonly');
                $text = '';
                $format = '';
            } else {
                $title = $this->config->title_onsite;
                $text = $this->config->title_offsite;
                $format = $this->config->format_offsite;
            }

        }

        $this->title = format_string($title);

        $filteropt = new stdClass;
        $filteropt->overflowdiv = true;
        if ($this->content_is_trusted()) {
            // fancy html allowed only on course, category and system blocks.
            $filteropt->noclean = true;
        }

        $this->content = new stdClass;
        $this->content->footer = '';
        if (!empty($text)) {
            // rewrite url
            $text = file_rewrite_pluginfile_urls($text,
                                                 'pluginfile.php',
                                                 $this->context->id,
                                                 'block_lanonly',
                                                 'content',
                                                 null);
            $this->content->text = format_text($text, $format, $filteropt);
        } else {
            $this->content->text = '';
        }

        unset($filteropt); // memory footprint

        return $this->content;
    }

    public function is_local() {

        $iplong = ip2long($_SERVER['REMOTE_ADDR']);
        $islanclassa = ip2long('10.0.0.0') <= $iplong && ip2long('10.255.255.255' >= $iplong;
        $islanclassb = ip2long('172.16.0.0') <= $iplong && ip2long('172.31.255.255') >= $iplong;
        $islanclassc = ip2long('192.168.0.0') <= $iplong && ip2long('192.168.255.255') >= $iplong;
        if ($islanclassa || $islanclassb || $islanclassc) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Serialize and store config data
     */
    public function instance_config_save($data, $nolongerused = false) {
        global $DB;

        $config = clone($data);
        // Move embedded files into a proper filearea and adjust HTML links to match
        $config->text_onsite = file_save_draft_area_files($data->text_onsite['itemid'],
                                                          $this->context->id,
                                                          'block_lanonly',
                                                          'content',
                                                          0,
                                                          array('subdirs'=>true),
                                                          $data->text_onsite['text']);
        $config->format_onsite = $data->text_onsite['format'];
        $config->text_offsite = file_save_draft_area_files($data->text_offsite['itemid'],
                                                           $this->context->id,
                                                           'block_lanonly',
                                                           'content',
                                                           0,
                                                           array('subdirs'=>true),
                                                           $data->text_offsite['text']);
        $config->format_offsite = $data->text_offsite['format'];

        parent::instance_config_save($config, $nolongerused);
    }

    public function instance_delete() {
        global $DB;
        $fs = get_file_storage();
        $fs->delete_area_files($this->context->id, 'block_lanonly');
        return true;
    }

    public function content_is_trusted() {
        global $SCRIPT;

        if (!$context = get_context_instance_by_id($this->instance->parentcontextid)) {
            return false;
        }
        //find out if this block is on the profile page
        if ($context->contextlevel == CONTEXT_USER) {
            if ($SCRIPT === '/my/index.php') {
                // this is exception - page is completely private, nobody else may see content there
                // that is why we allow JS here
                return true;
            } else {
                // no JS on public personal pages, it would be a big security issue
                return false;
            }
        }

        return true;
    }
}
