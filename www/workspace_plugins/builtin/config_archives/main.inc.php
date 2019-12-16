<?php

// CONFIG ARCHIVE LIST
// List config archives if they have permission to see them
if (auth('host_config_admin',$debug_val)) {
    list($status, $total_configs, $tmp) = db_get_records($onadb, 'configurations', array('host_id' => $record['id']), '', 0);

    $title_left_html = "Config Archives{$extravars['host_name']} &#040;{$total_configs}&#041";

    if ($total_configs) {
        // Ok, basically we're going to get a list of each config type, and see how many of each type this host has
        $row_html = '';
        list($status, $rows, $types) = db_get_records($onadb, 'configuration_types', 'id > 0', 'name');
        foreach ($types as $type) {
            // See how many of this type the host has
            list($status, $rows, $tmp) = db_get_records($onadb, 'configurations', array('host_id' => $record['id'], 'configuration_type_id' => $type['id']), '', 0);
            if ($rows) {
                // Select the first config record of the specified type and host
                list($status, $rows_conf, $config) = db_get_records($onadb, 'configurations', array('host_id' => $record['id'], 'configuration_type_id' => $type['id']), 'ctime DESC', 1);

                // Escape data for display in html
                foreach(array_keys($type) as $key) { $type[$key] = htmlentities($type[$key], ENT_QUOTES); }
                $row_html .= <<<EOL
        <tr title="View {$type['name']} archives"
            style="cursor: pointer;"
            onMouseOver="this.className='row-highlight';"
            onMouseOut="this.className='row-normal';"
            onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_config_text\', \'host_id=>{$record['id']},type_id=>{$type['id']},displayconf=>{$config[0]['id']}\', \'display\')');"
        >
            <td align="left">{$type['name']} ({$rows})</td>
            <td align="right"><img src="{$images}/silk/zoom.png" border="0">&nbsp;</td>
        </tr>
EOL;
            }
        }

        $modbodyhtml .= <<<EOL
        <!-- CONFIG ARCHIVES LIST -->
        <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">
        {$row_html}
        </table>
EOL;
    } else {
        // MP: FIXME: find a better way to just delete the div element outright.
        $modjs = "el('config_archives_container').innerHTML='';";
    }

} else {
    // dont display anything
    $modbodyhtml = '';
}
// END CONFIG ARCHIVE LIST

?>
