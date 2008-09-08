<?php

$rec_content = print_r($record, true);
$debug_display = 0;  // Set this to 1 to see contents of the $record array

$title_left_html = 'Global DHCP Entries';
$hasgateway = 0;

// Default kind will be global settings.  This means host and subnet ids are 0 in the option_entries table
$kind = 'global';
list($status, $rows, $dhcp_entries) = db_get_records($onadb, 'dhcp_option_entries', array('host_id' => 0, 'subnet_id' => 0), '');
$title_description = "These settings are global for all DHCP configurations";

// Determine if this is a host or a subnet we are dealing with
if (is_numeric($record['subnet_type_id'])) {
    $kind = 'subnet';
    list($status, $rows, $dhcp_entries) = db_get_records($onadb, 'dhcp_option_entries', array('subnet_id' => $record['id']), '');
    $title_left_html = 'DHCP Entries';
    $title_description = "";
}
// determining host type using devicefull is kinda crappy.. works for now
if (isset($record['devicefull'])) {
    $kind = 'host';
    list($status, $rows, $dhcp_entries) = db_get_records($onadb, 'dhcp_option_entries', array('host_id' => $record['id']), '');
    $title_left_html = 'DHCP Entries';
    unset($title_description);
}
// DHCP ENTRIES LIST
$modbodyhtml .= <<<EOL
        <!-- DHCP INFORMATION -->
        <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px; margin-top: 0px;">
EOL;

if ($debug_display) {
    $modbodyhtml .= <<<EOL
        <tr><td><pre>{$rec_content}</pre></td></tr>
EOL;
}

if ($rows) {
    foreach ($dhcp_entries as $entry) {
        list($status, $rows, $dhcp_type) = ona_get_dhcp_option_entry_record(array('id' => $entry['id']));
        foreach(array_keys($dhcp_type) as $key) { $dhcp_type[$key] = htmlentities($dhcp_type[$key], ENT_QUOTES); }

        if ($dhcp_type['display_name'] == "Default Gateway") { $hasgateway = 1;}

        $modbodyhtml .= <<<EOL
            <tr onMouseOver="this.className='row-highlight';"
                onMouseOut="this.className='row-normal';">

                <td align="left" nowrap="true" title="DHCP Option number: {$dhcp_type['number']}">
                    {$dhcp_type['display_name']}&nbsp;&nbsp;
                </td>
                <td align="left" nowrap="true" style="border-left: 1px solid; border-left-color: #aaaaaa;padding-left: 3px;">
                    {$dhcp_type['value']}&nbsp;
                </td>
                <td align="right" nowrap="true">
                    <form id="form_dhcp_entry_{$entry['id']}"
                        ><input type="hidden" name="id" value="{$entry['id']}"
                        ><input type="hidden" name="{$kind}_id" value="{$record['id']}"
                        ><input type="hidden" name="js" value="{$extravars['refresh']}"
                    ></form>
EOL;
        if (auth('advanced',$debug_val)) {
            $modbodyhtml .= <<<EOL
                    <a title="Edit DHCP Entry. ID: {$dhcp_type['id']}"
                        class="act"
                        onClick="xajax_window_submit('edit_dhcp_option_entry', xajax.getFormValues('form_dhcp_entry_{$entry['id']}'), 'editor');"
                    ><img src="{$images}/silk/page_edit.png" border="0"></a>

                    <a title="Delete DHCP Entry. ID: {$dhcp_type['id']}"
                        class="act"
                        onClick="var doit=confirm('Are you sure you want to delete this DHCP entry?');
                                if (doit == true)
                                    xajax_window_submit('edit_dhcp_option_entry', xajax.getFormValues('form_dhcp_entry_{$entry['id']}'), 'delete');"
                    ><img src="{$images}/silk/delete.png" border="0"></a>
EOL;
        }
        $modbodyhtml .= <<<EOL
                </td>
            </tr>

EOL;
    }
}
// If there are no DHCP entries but this is a subnet with a pool/and or dhcp servers, we need a gateway at least!

    if ($kind == 'subnet' and $hasgateway == 0) {
        // Gather info about this subnet and if it is assigned to any dhcp servers.
        list($status, $rows, $dhcp_servers)   = db_get_records($onadb, 'dhcp_server_subnets', array('subnet_id' => $record['id']));
        list($status, $poolrows, $dhcp_pools) = db_get_records($onadb, 'dhcp_pools', array('subnet_id' => $record['id']));


        if ($rows or $poolrows) {
            $modbodyhtml .= <<<EOL
            <tr style="background-color: #FFDDDD;" title="There is no defined gateway entry for this subnet!">
                <td colspan=10 nowrap="true">
                    <img src='{$images}/silk/error.png' border='0'> Please add a default gateway option!
                </td>
EOL;
        }
    }


if (auth('advanced',$debug_val)) {
    $modbodyhtml .= <<<EOL
            <tr>
                <td colspan="5" align="left" valign="middle" nowrap="true" class="act-box">

                    <form id="form_dhcp_entry_add_{$record['id']}"
                        ><input type="hidden" name="{$kind}_id" value="{$record['id']}"
                        ><input type="hidden" name="js" value="{$extravars['refresh']}"
                    ></form>

                    <a title="Add DHCP Entry"
                        class="act"
                        onClick="xajax_window_submit('edit_dhcp_option_entry', xajax.getFormValues('form_dhcp_entry_add_{$record['id']}'), 'editor');"
                    ><img src="{$images}/silk/page_add.png" border="0"></a>&nbsp;

                    <a title="Add DHCP Entry"
                        class="act"
                        onClick="xajax_window_submit('edit_dhcp_option_entry', xajax.getFormValues('form_dhcp_entry_add_{$record['id']}'), 'editor');"
                    >Add DHCP Entry</a>&nbsp;
                </td>
            </tr>
EOL;
}

$modbodyhtml .= "</table>";

// END DHCP ENTRIES LIST
unset($rec_content);


?>