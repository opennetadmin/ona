<?php

// This module displays DHCP servers associated with a subnet


$title_left_html = 'DHCP Pools';

$title_right_html = '';

$haspool = 0;

$modbodyhtml .= <<<EOL
        <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">
EOL;



// get dhcp pool records
list($status, $rows, $dhcp_pool) = db_get_records($onadb, 'dhcp_pools', array('subnet_id' => $record['id']));
if ($rows) {
    $haspool = 1;

    // Gather info about this subnet and if it is assigned to any dhcp servers.
    list($status, $srows, $dhcp_servers) = db_get_records($onadb, 'dhcp_server_subnets', array('subnet_id' => $record['id']));

    foreach ($dhcp_pool as $pool) {
        // Test for a dhcp server subnet entry for the pool or that it is part of a failover group
        $hasserver = $rowstyle = '';
        if (!$srows and $pool['dhcp_failover_group_id'] == 0) {
            $hasserver = "<img src='{$images}/silk/error.png' border='0'>";
            $rowstyle = 'style="background-color: #FFDDDD;" title="There is no DHCP server defined for this subnet!"';
        }

        $pool['ip_addr_start']   = ip_mangle($pool['ip_addr_start'], 'dotted');
        $pool['ip_addr_end']     = ip_mangle($pool['ip_addr_end'], 'dotted');

        $modbodyhtml .= <<<EOL
            <tr {$rowstyle}>
                <td align="left" nowrap="true">
                    {$hasserver} {$pool['ip_addr_start']}&nbsp;Thru&nbsp;{$pool['ip_addr_end']}&nbsp;
EOL;


    // Display information about what pool group this pool is assigned to
    // TODO: make this more efficient.  seems like there would be a better way to do this
    if ($pool['dhcp_failover_group_id']) {
        list($status, $rows, $failover_group) = ona_get_dhcp_failover_group_record(array('id' => $pool['dhcp_failover_group_id']));

        list($status, $rows, $server_host1) = ona_get_host_record(array('id' => $failover_group['primary_server_id']));
        list($status, $rows, $server_host2) = ona_get_host_record(array('id' => $failover_group['secondary_server_id']));

        $modbodyhtml .= <<<EOL
                <a title="View DHCP server (Primary failover) - {$server_host1['fqdn']}"
                    class="nav"
                    onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_dhcp_server\', \'host_id=>{$server_host1['id']}\', \'display\')');"
                >{$server_host1['name']}</a>&#047;
                <a title="View DHCP server (Secondary failover) - {$server_host2['fqdn']}"
                    class="nav"
                    onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_dhcp_server\', \'host_id=>{$server_host2['id']}\', \'display\')');"
                >{$server_host2['name']}</a>
EOL;


    }

    $modbodyhtml .= <<<EOL
        </td>
            <td align="right" nowrap="true">
                <form id="form_dhcp_pool_{$pool['id']}"
                    ><input type="hidden" name="id" value="{$pool['id']}"
                    ><input type="hidden" name="subnet" value="{$record['id']}"
                    ><input type="hidden" name="js" value="{$extravars['refresh']}"
                ></form>
EOL;

    if (auth('advanced',$debug_val)) {
        $modbodyhtml .= <<<EOL
                <a title="Edit DHCP Pool. ID: {$pool['id']}"
                    class="act"
                    onClick="xajax_window_submit('edit_dhcp_pool', xajax.getFormValues('form_dhcp_pool_{$pool['id']}'), 'editor');"
                ><img src="{$images}/silk/page_edit.png" border="0"></a>

                <a title="Delete DHCP Pool. ID: {$pool['id']}"
                    class="act"
                    onClick="var doit=confirm('Are you sure you want to delete this DHCP pool?');
                            if (doit == true)
                                xajax_window_submit('edit_dhcp_pool', xajax.getFormValues('form_dhcp_pool_{$pool['id']}'), 'delete');"
                ><img src="{$images}/silk/delete.png" border="0"></a>
EOL;
    }
    $modbodyhtml .= <<<EOL
            </td>
    </tr>
EOL;

        }
}

if (auth('advanced',$debug_val)) {
    $modbodyhtml .= <<<EOL
            <tr>
                <td colspan="2" align="left" valign="middle" nowrap="true" class="act-box">
                    <form id="form_pool_add_{$pool['id']}"
                        ><input type="hidden" name="subnet" value="{$record['id']}"
                        ><input type="hidden" name="js" value="{$extravars['refresh']}"
                    ></form>
                    <a title="Add DHCP Pool"
                        class="act"
                        onClick="xajax_window_submit('edit_dhcp_pool', xajax.getFormValues('form_pool_add_{$pool['id']}'), 'editor');"
                    ><img src="{$images}/silk/page_add.png" border="0"></a>&nbsp;

                    <a title="Add DHCP Pool"
                        class="act"
                        onClick="xajax_window_submit('edit_dhcp_pool', xajax.getFormValues('form_pool_add_{$pool['id']}'), 'editor');"
                    >Add DHCP Pool</a>
                </td>
            </tr>
            </td>
EOL;
}


$modbodyhtml .= "        </table>";



?>