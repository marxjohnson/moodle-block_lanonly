<?php
/*
 * This is a copy of the html block but with a check for private IP addresses-
 * it only displays different content to users within the LAN and those outside
 */


class block_lanonly extends block_base {

    function is_local(){
       $iplong = ip2long( $_SERVER['REMOTE_ADDR']);

       if(
           (ip2long('10.0.0.0')<=$iplong)and(ip2long('10.255.255.255')>=$iplong)
        or (ip2long('172.16.0.0')<=$iplong)and(ip2long('172.31.255.255')>=$iplong)
        or (ip2long('192.168.0.0')<=$iplong)and(ip2long('192.168.255.255')>=$iplong)
       ){
           return true;
       }else{
           return false;
       }
    }


    function preferred_width() {
        // The preferred value is in pixels
        return 250;
    }

    function init() {
        $this->title = get_string('pluginname','block_lanonly');
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
            } else {
                $title = $this->config->title_onsite;
                $text = $this->config->text_onsite['text'];
            }

        } else {

            if (empty($this->config)) {
                $title = get_string('newblock','block_lanonly');
                $text = '';
            } else {
                $title = $this->config->title_onsite;
                $text = $this->config->title_offsite['text'];
            }

        }

        $this->title = format_string($title);

        $this->content = new stdClass;

        $formatoptions = new object();
        $formatoptions->noclean = true;

        if (!$this->content->text = format_text($text, FORMAT_HTML,$formatoptions)) {
             $this->content->text= '';
        }
        
        $this->content->footer = '';

        return $this->content;
    }

    /**
     * Will be called before an instance of this block is backed up, so that any links in
     * any links in any HTML fields on config can be encoded.
     * @return string
     */
    function get_backup_encoded_config() {
        /// Prevent clone for non configured block instance. Delegate to parent as fallback.
        if (empty($this->config)) {
            return parent::get_backup_encoded_config();
        }
        $data = clone($this->config);
        $data->text = backup_encode_absolute_links($data->text);
        return base64_encode(serialize($data));
    }

    /**
     * This function makes all the necessary calls to {@link restore_decode_content_links_worker()}
     * function in order to decode contents of this block from the backup
     * format to destination site/course in order to mantain inter-activities
     * working in the backup/restore process.
     *
     * This is called from {@link restore_decode_content_links()} function in the restore process.
     *
     * NOTE: There is no block instance when this method is called.
     *
     * @param object $restore Standard restore object
     * @return boolean
     **/
    function decode_content_links_caller($restore) {
        global $CFG;

        if ($restored_blocks = get_records_select("backup_ids","table_name = 'block_instance' AND backup_code = $restore->backup_unique_code AND new_id > 0", "", "new_id")) {
            $restored_blocks = implode(',', array_keys($restored_blocks));
            $sql = "SELECT bi.*
                      FROM {$CFG->prefix}block_instance bi
                           JOIN {$CFG->prefix}block b ON b.id = bi.blockid
                     WHERE b.name = 'html' AND bi.id IN ($restored_blocks)";

            if ($instances = get_records_sql($sql)) {
                foreach ($instances as $instance) {
                    $blockobject = block_instance('html', $instance);
                    $blockobject->config->text = restore_decode_absolute_links($blockobject->config->text);
                    $blockobject->config->text = restore_decode_content_links_worker($blockobject->config->text, $restore);
                    $blockobject->instance_config_commit($blockobject->pinned);
                }
            }
        }

        return true;
    }
}
?>
