<?php
/*
 * This is a copy of the html block but with a check for private IP addresses-
 * it only displays different content to users within the LAN and those outside
 */


class block_lanonly extends block_base {    

    function init() {
        $this->title = get_string('pluginname','block_lanonly');
    }

    function preferred_width() {
        // The preferred value is in pixels
        return 250;
    }    

    function applicable_formats() {
        return array('all' => true);
    }

    function instance_allow_multiple() {
        return true;
    }


    function get_content() {
        if ($this->content !== NULL) {
            return $this->content;
        }

        if ($this->is_local()) {

            if (empty($this->config)) {
                $title = get_string('newblock','block_lanonly');
                $text = '';
                $format = '';
            } else {
                $title = $this->config->title_onsite;
                $text = $this->config->text_onsite;
                $format = $this->config->format_onsite;
            }

        } else {

            if (empty($this->config)) {
                $title = get_string('newblock','block_lanonly');
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
            $text = file_rewrite_pluginfile_urls($text, 'pluginfile.php', $this->context->id, 'block_lanonly', 'content', NULL);
            $this->content->text = format_text($text, $format, $filteropt);
        } else {
            $this->content->text = '';
        }

        unset($filteropt); // memory footprint

        return $this->content;
    }

    function is_local() {

        $iplong = ip2long( $_SERVER['REMOTE_ADDR']);
        if ((ip2long('10.0.0.0') <= $iplong) && (ip2long('10.255.255.255') >= $iplong)
        || (ip2long('172.16.0.0') <= $iplong) && (ip2long('172.31.255.255') >= $iplong)
        || (ip2long('192.168.0.0') <= $iplong) && (ip2long('192.168.255.255') >= $iplong)) {

            return true;

       } else {
            return false;
       }
    }

    /**
     * Serialize and store config data
     */
    function instance_config_save($data, $nolongerused = false) {
        global $DB;

        $config = clone($data);
        // Move embedded files into a proper filearea and adjust HTML links to match
        $config->text_onsite = file_save_draft_area_files($data->text_onsite['itemid'], $this->context->id, 'block_lanonly', 'content', 0, array('subdirs'=>true), $data->text_onsite['text']);
        $config->format_onsite = $data->text_onsite['format'];
        $config->text_offsite = file_save_draft_area_files($data->text_offsite['itemid'], $this->context->id, 'block_lanonly', 'content', 0, array('subdirs'=>true), $data->text_offsite['text']);
        $config->format_offsite = $data->text_offsite['format'];

        parent::instance_config_save($config, $nolongerused);
    }

    function instance_delete() {
        global $DB;
        $fs = get_file_storage();
        $fs->delete_area_files($this->context->id, 'block_lanonly');
        return true;
    }

    function content_is_trusted() {
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
?>
