<?php

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
