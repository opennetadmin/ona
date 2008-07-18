<?

// This module displays DHCP servers associated with a subnet


$title_left_html = 'DHCP Servers';

$title_right_html = '';

$modbodyhtml .= <<<EOL
        <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">
EOL;


// Get a list of servers
list($status, $rows, $dhcpservers) = db_get_records($onadb,
                                                    'hosts',
                                                    'id IN (SELECT host_id
                                                                FROM   dhcp_server_subnets
                                                                WHERE  subnet_id = '.$record['id'].'
                                                                UNION
                                                                SELECT primary_server_id
                                                                FROM  dhcp_failover_groups
                                                                WHERE id IN (SELECT dhcp_failover_group_id
                                                                                                FROM dhcp_pools
                                                                                                WHERE subnet_id = '.$record['id'].')
                                                                UNION
                                                                SELECT secondary_server_id
                                                                FROM  dhcp_failover_groups
                                                                WHERE id IN (SELECT dhcp_failover_group_id
                                                                                                FROM dhcp_pools
                                                                                                WHERE subnet_id = '.$record['id'].'))'
                                                        );

if ($rows) {
    foreach ($dhcpservers as $dhcphost) {

        list($status, $rows, $host) = ona_find_host($dhcphost['id']);
        list($dhcpsubnetstatus, $dhcpsubnetrows, $dhcpserver) = ona_get_dhcp_server_subnet_record(array('subnet_id' => $record['id'],'host_id' => $host['id']));
        $host['fqdn'] = htmlentities($host['fqdn'], ENT_QUOTES);
        $modbodyhtml .= <<<EOL
            <tr onMouseOver="this.className='row-highlight';"
                onMouseOut="this.className='row-normal';">
                <td align="left" nowrap="true">
                    <a title="View server. ID: {$host['id']}"
                        class="nav"
                        onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_dhcp_server\', \'host_id=>{$host['id']}\', \'display\')');"
                    >{$host['fqdn']}</a>&nbsp;
                </td>
                    <td align="right" nowrap="true">
EOL;

        if (auth('advanced',$debug_val) && $dhcpsubnetrows == 1) {
            $modbodyhtml .= <<<EOL
                    <form id="form_dhcp_serv_{$dhcpserver['id']}"
                            ><input type="hidden" name="server_id" value="{$host['fqdn']}"
                            ><input type="hidden" name="subnet_id" value="{$dhcpserver['subnet_id']}"
                            ><input type="hidden" name="js" value="{$extravars['refresh']}"
                    ></form>

                    <a title="Remove server assignment"
                        class="act"
                        onClick="var doit=confirm('Are you sure you want to remove this subnet from this DHCP server?');
                        if (doit == true)
                            xajax_window_submit('edit_dhcp_server', xajax.getFormValues('form_dhcp_serv_{$dhcpserver['id']}'), 'delete');"
                    ><img src="{$images}/silk/page_delete.png" border="0"></a>
EOL;
        }
        elseif (auth('advanced',$debug_val)) {
            $modbodyhtml .= <<<EOL
                    <span title="You must change the failover group assignment on the pool to remove this entry."><img src="{$images}/silk/comment.png" border="0"></span>
EOL;
        }
        $modbodyhtml .= <<<EOL
                    &nbsp;
                </td>

            </tr>
EOL;
    }
}

if (auth('advanced',$debug_val)) {
    $modbodyhtml .= <<<EOL
            <tr>
                <td colspan="3" align="left" valign="middle" nowrap="true" class="act-box">
                    <form id="form_dhcp_server_{$record['id']}"
                            ><input type="hidden" name="subnet" value="{$record['name']}"
                            ><input type="hidden" name="js" value="{$extravars['refresh']}"
                    ></form>
                    <!-- ADD SUBNET LINK -->
                    <a title="Assign subnet to DHCP server"
                    class="act"
                    onClick="xajax_window_submit('edit_dhcp_server', xajax.getFormValues('form_dhcp_server_{$record['id']}'), 'editor');"
                    ><img src="{$images}/silk/page_add.png" border="0"></a>&nbsp;

                    <a title="Assign subnet to DHCP server"
                    class="act"
                    onClick="xajax_window_submit('edit_dhcp_server', xajax.getFormValues('form_dhcp_server_{$record['id']}'), 'editor');"
                    >Add DHCP Server</a>&nbsp;
                </td>
            </tr>
EOL;
}
$modbodyhtml .= "        </table>";



?>