<?





// CONFIG ARCHIVE LIST
// List config archives if they have permission to see them
// if (auth('host_config_admin',$debug_val) and authlvl($record['lvl'])) {
list($status, $total_configs, $tmp) = db_get_records($onadb, 'configurations', array('host_id' => $record['id']), '', 0);

$titlehtml = "Config Archives&nbsp;&#040;{$total_configs}&#041";


if ($total_configs) {
    // Ok, basically we're going to get a list of each config type, and see how many of each type this host has
    $row_html = '';
    list($status, $rows, $types) = db_get_records($onadb, 'configuration_types', 'id > 0', 'name');
    foreach ($types as $type) {
        // See how many of this type the host has
        list($status, $rows, $tmp) = db_get_records($onadb, 'configurations', array('host_id' => $record['id'], 'configuration_type_id' => $type['id']), '', 0);
        if ($rows) {
            // Escape data for display in html
            foreach(array_keys($type) as $key) { $type[$key] = htmlentities($type[$key], ENT_QUOTES); }
            $row_html .= <<<EOL
    <tr title="View configs"
        style="cursor: pointer;"
        onMouseOver="this.className='row-highlight';"
        onMouseOut="this.className='row-normal';"
        onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_config_text\', \'host_id=>{$record['id']},type_id=>{$type['id']}\', \'display_list\')');"
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
}
//}
// END CONFIG ARCHIVE LIST



?>