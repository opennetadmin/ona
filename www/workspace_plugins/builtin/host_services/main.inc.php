<?php

$title_left_html = 'Managed services on this host';

    // SERVICE CONFIGURATION BOX
    $serverinfo = '';
    $is_dns_server = 0;
    $is_dhcp_server = 0;
    $dhcp_rows = 0;
    $domain_rows = 0;

    // Determine if this is actaually a server by counting server "uses"
    // Is this a DNS server?
    list($status, $domain_rows, $domain_server) = db_get_records($onadb, 'dns_server_domains', 'host_id = '. $onadb->qstr($record['id']));
    if ($domain_rows >= 1) { $is_dns_server = 1; }

    // Is this a DHCP server?
    list($status, $dhcp_rows, $dhcp_server) = db_get_records($onadb, 'dhcp_server_subnets', 'host_id = '. $onadb->qstr($record['id']));
    list($status, $dhcp_group_rows, $dhcp_group_server) = db_get_records($onadb, 'dhcp_failover_groups', 'primary_server_id = '. $onadb->qstr($record['id']).' or secondary_server_id = '. $onadb->qstr($record['id']), '');
    if ($dhcp_rows or $dhcp_group_rows) { $is_dhcp_server = 1; $dhcp_rows = $dhcp_rows+$dhcp_group_rows;}


    if ($is_dhcp_server==1) {
       $serverinfo .= <<<EOL
            <tr title="View DHCP service"
                style="cursor: pointer;"
                onMouseOver="this.className='row-highlight'"
                onMouseOut="this.className='row-normal'"
                onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_dhcp_server\', \'host_id=>{$record['id']}\', \'display\')');"
            >
                <td>DHCP</td>
                <td>Y</td>
                <td align="right">{$dhcp_rows}</td>
                <td align="left"><span style="font-size: 10px;">&nbsp;(Subnets)</span></td>
                <td align="right"><img src="{$images}/silk/zoom.png" border="0">&nbsp;</td>
            </tr>
EOL;
    }
    else {
        $serverinfo .= <<<EOL
            <form id="form_dhcp_serv_{$record['id']}"
                ><input type="hidden" name="server" value="{$record['id']}"
                ><input type="hidden" name="js" value="xajax_window_submit('work_space', 'xajax_window_submit(\'display_dhcp_server\', \'host_id=>{$record['id']}\', \'display\')');"
            ></form>

            <tr title="Add DHCP service"
                onMouseOver="this.className='row-highlight'"
                onMouseOut="this.className='row-normal'"
EOL;

        if (auth('advanced',$debug_val)) {
            $serverinfo .= <<<EOL
                style="cursor: pointer;"
                onClick="xajax_window_submit('edit_dhcp_server', xajax.getFormValues('form_dhcp_serv_{$record['id']}'), 'editor');"
EOL;
        }

        $serverinfo .= <<<EOL
                >
                <td>DHCP</td>
                <td>N</td>
                <td align="right">{$dhcp_rows}</td>
                <td align="left"><span style="font-size: 10px;">&nbsp;(Subnets)</span></td>
EOL;

        if (auth('advanced',$debug_val)) {
            $serverinfo .= <<<EOL
                <td align="right"><img src="{$images}/silk/page_add.png" border="0">&nbsp;</td>
EOL;
        }

        $serverinfo .= "            </tr>";
    }

    if ($is_dns_server==1) {
       $serverinfo .= <<<EOL
            <tr title="View DNS service"
                style="cursor: pointer;"
                onMouseOver="this.className='row-highlight'"
                onMouseOut="this.className='row-normal'"
                onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain_server\', \'host_id=>{$record['id']}\', \'display\')');"
            >
                <td>DNS</td>
                <td>Y</td>
                <td align="right">{$domain_rows}</td>
                <td align="left"><span style="font-size: 10px;">&nbsp;(Domains)</span></td>
                <td align="right"><img src="{$images}/silk/zoom.png" border="0">&nbsp;</td>
            </tr>
EOL;
    }
    else {
        $serverinfo .= <<<EOL
            <form id="form_dns_serv_{$record['id']}"
                ><input type="hidden" name="server" value="{$record['id']}"
                ><input type="hidden" name="js" value="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain_server\', \'host_id=>{$record['id']}\', \'display\')');"
            ></form>

            <tr title="Add DNS service"
                onMouseOver="this.className='row-highlight'"
                onMouseOut="this.className='row-normal'"
EOL;

        if (auth('advanced',$debug_val)) {
            $serverinfo .= <<<EOL
                style="cursor: pointer;"
                onClick="xajax_window_submit('edit_domain_server', xajax.getFormValues('form_dns_serv_{$record['id']}'), 'editor');"
EOL;
        }

        $serverinfo .= <<<EOL
            >
                <td>DNS</td>
                <td>N</td>
                <td align="right">{$domain_rows}</td>
                <td align="left"><span style="font-size: 10px;">&nbsp;(Domains)</span></td>
EOL;

        if (auth('advanced',$debug_val)) {
            $serverinfo .= <<<EOL
                <td align="right"><img src="{$images}/silk/page_add.png" border="0">&nbsp;</td>
EOL;
        }

        $serverinfo .= "            </tr>";

    }

// create workspace menu items
// This is where you list an array of menu items to display for this workspace
if ($is_dhcp_server==0) {
    $modwsmenu[0]['menutitle'] = 'Add DHCP Services';
    $modwsmenu[0]['tooltip']   = 'Add DHCP Services to this host';
    $modwsmenu[0]['authname']  = 'advanced';
    $modwsmenu[0]['commandjs'] = "xajax_window_submit('edit_dhcp_server', xajax.getFormValues('form_host_{$record['id']}'), 'editor');";
    $modwsmenu[0]['image'] = '/images/silk/page_add.png';
}
if ($is_dns_server==0) {
    $modwsmenu[1]['menutitle'] = 'Add DNS Services';
    $modwsmenu[1]['tooltip']   = 'Add DNS Services to this host';
    $modwsmenu[1]['authname']  = 'advanced';
    $modwsmenu[1]['commandjs'] = "xajax_window_submit('edit_domain_server', xajax.getFormValues('form_host_{$record['id']}'), 'editor');";
    $modwsmenu[1]['image'] = '/images/silk/page_add.png';
}

if ($is_dhcp_server==1 or $is_dns_server==1) {
    $modbodyhtml .= <<<EOL
            <!-- SERVICES CONFIGURATION BOX -->
            <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">
                <tr><td>
                {$serverinfo}
                </td></tr>
            </table>
EOL;
}
    // END SERVICE CONFIGURATION BOX



?>
