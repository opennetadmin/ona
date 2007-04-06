<?
// Include map portal functions
include('include/functions_network_map.inc.php');



//////////////////////////////////////////////////////////////////////////////
// Function: ws_display()
//
// Description:
//   Displays a subnet record and all associated info in the work_space div.
//////////////////////////////////////////////////////////////////////////////
function ws_display($window_name, $form='') {
    global $conf, $self, $onadb;
    global $images, $color, $style;
    $html = '';
    $js = '';
    $debug_val = 3;  // used in the auth() calls to supress logging

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);

    // Load the subnet record
    if ($form['subnet_id'])
        list($status, $rows, $record) = ona_get_subnet_record(array('id' => $form['subnet_id']));
    else if ($form['ip'])
        list($status, $rows, $record) = ona_get_subnet_record(array('ip_addr' => ip_mangle($form['ip'], 'numeric')));
    else if ($form['subnet'])
        list($status, $rows, $record) = ona_get_subnet_record(array('name' => $form['subnet']));
    if ($status or !$rows) {
        array_pop($_SESSION['ona']['work_space']['history']);
        $html .= "<br><center><font color=\"red\"><b>Subnet doesn't exist!</b></font></center>";
        $response = new xajaxResponse();
        $response->addAssign("work_space_content", "innerHTML", $html);
        return($response->getXML());
    }

    // Update History Title
    $history = array_pop($_SESSION['ona']['work_space']['history']);
    $js .= "xajax_window_submit('work_space', ' ', 'rewrite_history');";
    if ($history['title'] == $window_name) {
        $history['title'] = $record['name'];
        array_push($_SESSION['ona']['work_space']['history'], $history);
    }

    // Create some javascript to refresh the current page
    $refresh = htmlentities(str_replace(array("'", '"'), array("\\'", '\\"'), $history['url']), ENT_QUOTES);
    $refresh = "xajax_window_submit('work_space', '{$refresh}');";

    // Convert IP and Netmask to a presentable format
    $record['ip_addr'] = ip_mangle($record['ip_addr'], 'dotted');
    $record['ip_mask'] = ip_mangle($record['ip_mask'], 'dotted');
    $record['ip_subnet_mask_cidr'] = ip_mangle($record['ip_mask'], 'cidr');

    // Calculate the percentage of the subnet that's used (total size - allocated hosts - dhcp pool size)
    $usage_html = get_subnet_usage_html($record['id']);

    list($status, $rows, $type) = ona_get_subnet_type_record(array('id' => $record['subnet_type_id']));
    $record['type'] = $type['display_name'];

    // Vlan Record
    list($status, $rows, $vlan) = ona_get_vlan_record(array('id' => $record['vlan_id']));
    $record['vlan_name']        = $vlan['name'];
    $record['vlan_campus_id']   = $vlan['vlan_campus_id'];

    // Vlan Campus Record
    list($status, $rows, $vlan_campus) = ona_get_vlan_campus_record(array('id' => $record['vlan_campus_id']));
    $record['vlan_campus_name'] = $vlan_campus['name'];


    $style['content_box'] = <<<EOL
        margin: 10px 20px;
        padding: 2px 4px;
        background-color: #FFFFFF;
EOL;

    $style['label_box'] = <<<EOL
        font-weight: bold;
        padding: 2px 4px;
        border: solid 1px {$color['border']};
        background-color: {$color['window_content_bg']};
EOL;

    // Escape data for display in html
    foreach(array_keys($record) as $key) { $record[$key] = htmlentities($record[$key], ENT_QUOTES); }


    $html .= <<<EOL
    <!-- FORMATTING TABLE -->
    <div style="{$style['content_box']}">
    <table cellspacing="0" border="0" cellpadding="0"><tr>

        <!-- START OF FIRST COLUMN OF SMALL BOXES -->
        <td nowrap="true" valign="top" style="padding-right: 15px;">
EOL;

    // SUBNET INFORMATION BOX
    $html .= <<<EOL
            <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">
                <tr>
                <td colspan="99" nowrap="true">
                    <!-- LABEL -->
                    <div style="{$style['label_box']}">
                        <table cellspacing="0" border="0" cellpadding="0">
                            <tr><td nowrap="true">
                                <form id="form_subnet_{$record['id']}"
                                    ><input type="hidden" name="subnet_id" value="{$record['id']}"
                                    ><input type="hidden" name="js" value="{$refresh}"
                                ></form>
EOL;

    if (auth('subnet_modify',$debug_val)) {
        $html .= <<<EOL

                                <a title="Edit subnet. ID: {$record['id']}"
                                   class="act"
                                   onClick="xajax_window_submit('edit_subnet', xajax.getFormValues('form_subnet_{$record['id']}'), 'editor');"
                                ><img src="{$images}/silk/page_edit.png" border="0"></a>&nbsp;
EOL;
    }
    if (auth('subnet_del',$debug_val)) {
        $html .= <<<EOL
                                <a title="Delete subnet. ID: {$record['id']}"
                                   class="act"
                                   onClick="var doit=confirm('Are you sure you want to delete this subnet?');
                                            if (doit == true)
                                                xajax_window_submit('edit_subnet', xajax.getFormValues('form_subnet_{$record['id']}'), 'delete');"
                                ><img src="{$images}/silk/delete.png" border="0"></a>
EOL;
    }
    $html .= <<<EOL
                            &nbsp;
                            </td>
                            <td nowrap="true">
                                <b>{$record['name']}</b>&nbsp;
                            </td></tr>
                        </table>
                    </div>
                </td>
                </tr>
EOL;
    // Display the vlan info line only if there is a vlan associated
    if ($record['vlan_id']) {
        $html .= <<<EOL
                <tr>
                    <td align="right" nowrap="true"><b>Vlan</b>&nbsp;</td>
                    <td class="padding" align="left">
                        <a title="View Vlan Campus"
                           class="nav"
                        onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_vlan_campus\', \'vlan_campus_id=>{$record['vlan_campus_id']}\', \'display\')');"
                        >{$record['vlan_campus_name']}</a>&nbsp;&#047;&nbsp;<a title="View Vlan"
                           class="nav"
                           onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_vlan\', \'vlan_id=>{$record['vlan_id']}\', \'display\')');"
                        >{$record['name']}</a>
                     </td>
                </tr>
EOL;
    }
    $html .= <<<EOL
                <tr>
                    <td align="right" nowrap="true"><b>IP Address</b>&nbsp;</td>
                    <td class="padding" align="left">
                        {$record['ip_addr']}&nbsp;
                    </td>
                </tr>

                <tr>
                    <td align="right" nowrap="true"><b>Netmask</b>&nbsp;</td>
                    <td class="padding" align="left">{$record['ip_mask']} (/{$record['ip_subnet_mask_cidr']})&nbsp;</td>
                </tr>

                <tr>
                    <td align="right" nowrap="true"><b>Usage</b>&nbsp;</td>
                    <td class="padding" align="left" valign="middle">{$usage_html}</td>
                </tr>

                <tr>
                    <td align="right" nowrap="true"><b>Type</b>&nbsp;</td>
                    <td class="padding" align="left">{$record['type']}&nbsp;</td>
                </tr>

            </table>
EOL;
    // END SUBNET INFORMATION


    // SMALL SUBNET MAP

    // Get the numeric IP address of our subnet (we replace the last quad with a .0)
    $ip = ip_mangle(preg_replace('/\.\d+$/', '.0', $record['ip_addr']), 'numeric');
    $ip_subnet = ip_mangle($record['ip_addr'], 'numeric');

    $html .= <<<EOL
            <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">
                <tr><td colspan="99" nowrap="true">
                    <!-- LABEL -->
                    <div style="{$style['label_box']}">
                        <a title="Display full sized subnet map"
                           class="act"
                           onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_block_map\', \'ip_block_start=>{$record['ip_addr']}\', \'display\');');"
                        ><img src="{$images}/silk/shape_align_left.png" border="0"></a>&nbsp;
                        <a title="Highlight current subnet"
                           class="act"
                           onClick="
                             var _el = el('{$ip_subnet}_block');
                             if (_el) {
                               if (_el.style.isHighlighted) {
                                 _el.style.backgroundColor = '{$color['bgcolor_map_subnet']}';
                                 _el.style.isHighlighted = false;
                               }
                               else {
                                 _el.style.backgroundColor = '{$color['bgcolor_map_selected']}';
                                 _el.style.isHighlighted = true;
                               }
                             }
                           "
                        ><img src="{$images}/silk/paintbrush.png" border="0"></a>&nbsp;
                        <b>Subnet Map</b>
                    </div>
                </td></tr>

                <tr><td colspan="99" nowrap="true">
                    <input type="hidden" id="{$window_name}_zoom" name="zoom" value="7">
                    <div id="{$window_name}_portal" style="position: relative; height: 150px; width: 355px;">
                        <span id="{$window_name}_substrate"></span>
                    </div>
                </td></tr>
            </table>
EOL;

    // Get javascript to setup the map portal mouse handlers
    // Force ip end to be less than ip start to prevent Block highlighting
    $portal_js .= get_portal_js($window_name, $ip, $ip -1);

    // END SMALL SUBNET MAP


    $html .= <<<EOL
        <!-- END OF FIRST COLUMN OF SMALL BOXES -->
        </td>

        <!-- START OF SECOND COLUMN OF SMALL BOXES -->
        <td valign="top" style="padding-right: 15px;">
EOL;


    // LOCATION INFO BOX
//    require_once('winc/tooltips.inc.php');
//    list ($locationhtml, $locationjs) = get_location_html($record['location_id']);
//    $html .= $locationhtml;
//    $js .= $locationjs;
    // END UNIT INFO BOX



    $html .= <<<EOL
        <!-- END OF SECOND COLUMN OF SMALL BOXES -->
        </td>

        <!-- START OF THIRD COLUMN OF SMALL BOXES -->
        <td valign="top" style="padding-right: 15px;">
EOL;


    // DHCP SERVER LIST
    $html .= <<<EOL
        <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">
            <tr>
            <!-- maybe turn this option on later
                <td>
                    <form id="{$form['form_id']}_dhcp_serv_{$record['id']}"
                            ><input type="hidden" name="server" value="{$record['id']}"
                            ><input type="hidden" name="js" value="{$refresh}"
                    ></form>

                    <a title="Assign zone"
                    class="act"
                    onClick="xajax_window_submit('edit_zone_server', xajax.getFormValues('{$form['form_id']}_dhcp_serv_{$record['id']}'), 'editor');"
                    ><img src="{$images}/silk/page_add.png" border="0"></a>
                </td>
            -->
                <td colspan="99" nowrap="true" style="{$style['label_box']}">DHCP servers&nbsp;</td>
            </tr>

EOL;
    // Get a list of servers
    list($status, $rows, $dhcpservers) = db_get_records($onadb, 'DHCP_SERVER_SUBNETS_B', array('SUBNET_ID' => $record['id']));
    if ($rows) {
        foreach ($dhcpservers as $dhcpserver) {

            list($status, $rows, $host) = ona_find_host($dhcpserver['SERVER_ID']);
            $host['fqdn'] = htmlentities($host['fqdn'], ENT_QUOTES);
            $html .= <<<EOL
                <tr onMouseOver="this.className='row-highlight';"
                    onMouseOut="this.className='row-normal';">
                    <td align="left" nowrap="true">
                        <a title="View server. ID: {$host['id']}"
                           class="nav"
                           onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_dhcp_server\', \'host_id=>{$host['id']}\', \'display\')');"
                        >{$host['fqdn']}</a>&nbsp;
                    </td>
                     <td align="right" nowrap="true">
                        <form id="form_dhcp_serv_{$dhcpserver['id']}"
                                ><input type="hidden" name="server_id" value="{$host['fqdn']}"
                                ><input type="hidden" name="subnet_id" value="{$dhcpserver['SUBNET_ID']}"
                                ><input type="hidden" name="js" value="{$refresh}"
                        ></form>
EOL;

            if (auth('advanced',$debug_val)) {
                $html .= <<<EOL
                        <a title="Remove server assignment"
                           class="act"
                           onClick="var doit=confirm('Are you sure you want to remove this subnet from this DHCP server?');
                           if (doit == true)
                                xajax_window_submit('edit_dhcp_server', xajax.getFormValues('form_dhcp_serv_{$dhcpserver['ID']}'), 'delete');"
                        ><img src="{$images}/silk/page_delete.png" border="0"></a>
EOL;
            }
            $html .= <<<EOL
                        &nbsp;
                   </td>

                </tr>
EOL;
        }
    }

    if (auth('advanced',$debug_val)) {
        $html .= <<<EOL
                <tr>
                    <td colspan="3" align="left" valign="middle" nowrap="true" class="act-box">
                        <form id="form_dhcp_server_{$record['id']}"
                                ><input type="hidden" name="subnet" value="{$record['DESCRIPTION']}"
                                ><input type="hidden" name="js" value="{$refresh}"
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
    $html .= "        </table>";

    // END DHCP SERVER LIST



    // DHCP ENTRIES LIST
    $html .= <<<EOL
        <!-- DHCP INFORMATION -->
        <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">
            <!-- LABEL -->
            <tr><td colspan="2" nowrap="true" style="{$style['label_box']}">DHCP Entries</td></tr>
EOL;
    // Get dhcp entry records
    list($status, $rows, $dhcp_entry) = db_get_records($onadb, 'DHCP_ENTRY_B', array('SUBNET_ID' => $record['id']), '');
    if ($rows) {
        foreach ($dhcp_entry as $entry) {
            list($status, $rows, $dhcp_type) = ona_get_dhcp_entry_record(array('id' => $entry['id']));
            foreach(array_keys($dhcp_type) as $key) { $dhcp_type[$key] = htmlentities($dhcp_type[$key], ENT_QUOTES); }

            $html .= <<<EOL
                <tr onMouseOver="this.className='row-highlight';"
                    onMouseOut="this.className='row-normal';">
                    <td align="left" nowrap="true">
                        {$dhcp_type['DHCP_DESCRIPTION']}&nbsp;&#061;&#062;&nbsp;{$dhcp_type['DHCP_PARAMETER_VALUE']}
                    </td>
                    <td align="right" nowrap="true">
                        <form id="form_dhcp_entry_{$entry['id']}"
                            ><input type="hidden" name="id" value="{$entry['id']}"
                            ><input type="hidden" name="subnet_id" value="{$record['id']}"
                            ><input type="hidden" name="js" value="{$refresh}"
                        ></form>
EOL;

            if (auth('advanced',$debug_val)) {
                $html .= <<<EOL

                        <a title="Edit DHCP Entry. ID: {$dhcp_type['id']}"
                           class="act"
                           onClick="xajax_window_submit('edit_dhcp_entry', xajax.getFormValues('form_dhcp_entry_{$entry['id']}'), 'editor');"
                        ><img src="{$images}/silk/page_edit.png" border="0"></a>&nbsp;

                        <a title="Delete DHCP Entry. ID: {$dhcp_type['id']}"
                           class="act"
                           onClick="var doit=confirm('Are you sure you want to delete this DHCP entry?');
                                    if (doit == true)
                                        xajax_window_submit('edit_dhcp_entry', xajax.getFormValues('form_dhcp_entry_{$entry['id']}'), 'delete');"
                        ><img src="{$images}/silk/delete.png" border="0"></a>
EOL;
            }
            $html .= <<<EOL
                        &nbsp;
                    </td>
                </tr>
EOL;
        }
    }

    if (auth('advanced',$debug_val)) {
        $html .= <<<EOL
                <tr>
                    <td colspan="2" align="left" valign="middle" nowrap="true" class="act-box">
                        <form id="form_dhcp_entry_{$record['id']}"
                            ><input type="hidden" name="subnet_id" value="{$record['id']}"
                            ><input type="hidden" name="js" value="{$refresh}"
                        ></form>

                        <a title="Add DHCP Entry"
                           class="act"
                           onClick="xajax_window_submit('edit_dhcp_entry', xajax.getFormValues('form_dhcp_entry_{$record['id']}'), 'editor');"
                        ><img src="{$images}/silk/page_add.png" border="0"></a>&nbsp;

                        <a title="Add DHCP Entry"
                           class="act"
                           onClick="xajax_window_submit('edit_dhcp_entry', xajax.getFormValues('form_dhcp_entry_{$record['id']}'), 'editor');"
                        >Add DHCP Entry</a>&nbsp;
                    </td>
                </tr>
EOL;
    }
    $html .= "        </table>";

    // END DHCP ENTRIES LIST



    // DHCP POOL INFO
    $haspool = 0;
    $html .= <<<EOL
            <!-- DHCP POOL INFORMATION -->
            <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">
                <!-- LABEL -->
                <tr><td colspan="2" nowrap="true" style="{$style['label_box']}">DHCP Pools</td></tr>
EOL;
    // get dhcp pool records
    list($status, $rows, $dhcp_pool) = db_get_records($onadb, 'DHCP_POOL_B', array('SUBNET_ID' => $record['id']));
    if ($rows) {
        $haspool = 1;
        foreach ($dhcp_pool as $pool) {
            $pool['ip_addr_START']   = ip_mangle($pool['ip_addr_START'], 'dotted');
            $pool['ip_addr_END']     = ip_mangle($pool['ip_addr_END'], 'dotted');

            $html .= <<<EOL
                <tr>
                    <td align="left" nowrap="true">
                        {$pool['ip_addr_START']}&nbsp;Thru&nbsp;{$pool['ip_addr_END']}:&nbsp;
EOL;


            // Display information about what server this pool is assigned to
            if ($pool['SERVER_ID']) {
                list($status, $rows, $dhcp_server)      = ona_get_server_record(array('id' => $pool['SERVER_ID']));
                list($status, $rows, $dhcp_server_host) = ona_get_host_record(array('id' => $dhcp_server['HOST_ID']));
                // foreach(array_keys($dhcp_server_host) as $key) { $dhcp_server_host[$key] = htmlentities($dhcp_server_host[$key], ENT_QUOTES); }
                $html .= <<<EOL
                        <a title="View DHCP server"
                           class="nav"
                           onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_dhcp_server\', \'host_id=>{$dhcp_server['HOST_ID']}\', \'display\')');"
                        >{$dhcp_server_host['PRIMARY_DNS_NAME']}</a>&nbsp;
EOL;

            }
            // Display information about what pool group this pool is assigned to
            // TODO: make this more efficient.  seems like there would be a better way to do this
            if ($pool['DHCP_FAILOVER_GROUP_ID']) {
                list($status, $rows, $failover_group) = ona_get_dhcp_failover_group_record(array('DHCP_FAILOVER_GROUP_ID' => $pool['DHCP_FAILOVER_GROUP_ID']));

                list($status, $rows, $server1)      = ona_get_server_record(array('ID' => $failover_group['PRIMARY_SERVER_ID']));
                list($status, $rows, $server2)      = ona_get_server_record(array('ID' => $failover_group['SECONDARY_SERVER_ID']));
                list($status, $rows, $server_host1) = ona_get_host_record(array('ID' => $server1['HOST_ID']));
                list($status, $rows, $server_host2) = ona_get_host_record(array('ID' => $server2['HOST_ID']));

                $html .= <<<EOL
                        <a title="View DHCP server (Primary failover)"
                           class="nav"
                           onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_dhcp_server\', \'host_id=>{$server1['HOST_ID']}\', \'display\')');"
                        >{$server_host1['PRIMARY_DNS_NAME']}</a>&#047;
                        <a title="View DHCP server (Secondary failover)"
                           class="nav"
                           onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_dhcp_server\', \'host_id=>{$server2['HOST_ID']}\', \'display\')');"
                        >{$server_host2['PRIMARY_DNS_NAME']}</a>
EOL;


            }

            $html .= <<<EOL
                </td>
                    <td align="right" nowrap="true">
                        <form id="form_dhcp_pool_{$pool['DHCP_POOL_ID']}"
                            ><input type="hidden" name="id" value="{$pool['DHCP_POOL_ID']}"
                            ><input type="hidden" name="subnet" value="{$record['ID']}"
                            ><input type="hidden" name="js" value="{$refresh}"
                        ></form>
EOL;

            if (auth('advanced',$debug_val)) {
                $html .= <<<EOL
                        <a title="Edit DHCP Pool. ID: {$pool['DHCP_POOL_ID']}"
                           class="act"
                           onClick="xajax_window_submit('edit_dhcp_pool', xajax.getFormValues('form_dhcp_pool_{$pool['DHCP_POOL_ID']}'), 'editor');"
                        ><img src="{$images}/silk/page_edit.png" border="0"></a>&nbsp;

                        <a title="Delete DHCP Pool. ID: {$pool['DHCP_POOL_ID']}"
                           class="act"
                           onClick="var doit=confirm('Are you sure you want to delete this DHCP pool?');
                                    if (doit == true)
                                        xajax_window_submit('edit_dhcp_pool', xajax.getFormValues('form_dhcp_pool_{$pool['DHCP_POOL_ID']}'), 'delete');"
                        ><img src="{$images}/silk/delete.png" border="0"></a>
EOL;
            }
            $html .= <<<EOL
                        &nbsp;
                    </td>
                </tr>
EOL;

        }
    }

    if (auth('advanced',$debug_val)) {
        $html .= <<<EOL
                <tr>
                    <td colspan="2" align="left" valign="middle" nowrap="true" class="act-box">
                        <form id="form_pool_add_{$pool['DHCP_POOL_ID']}"
                            ><input type="hidden" name="subnet" value="{$record['ID']}"
                            ><input type="hidden" name="js" value="{$refresh}"
                        ></form>
                        <a title="Add DHCP Pool"
                           class="act"
                           onClick="xajax_window_submit('edit_dhcp_pool', xajax.getFormValues('form_pool_add_{$pool['DHCP_POOL_ID']}'), 'editor');"
                        ><img src="{$images}/silk/page_add.png" border="0"></a>&nbsp;

                        <a title="Add DHCP Pool"
                           class="act"
                           onClick="xajax_window_submit('edit_dhcp_pool', xajax.getFormValues('form_pool_add_{$pool['DHCP_POOL_ID']}'), 'editor');"
                        >Add DHCP Pool</a>&nbsp;
                    </td>
                </tr>
                </td>
EOL;
    }

    $html .= "        </table>";

    // END DHCP POOL INFO


    // START MESSAGES BOX
    // $tablename is a reference directly to the table that contains the item
    // we are displaying to the user.  This is a kludge since we cannot
    // directly link the mysql tables to the onadb tables with the ID of the table.
    // It is possible that you can have the same ID in multiple tables, currently.
/*    $tablename = 'SUBNETS_B';
    require_once('winc/tooltips.inc.php');
    list($lineshtml, $linesjs) = get_message_lines_html("table_id_ref = {$record['ID']} AND table_name_ref LIKE '{$tablename}'");
    if ($lineshtml) {
       $html .= <<<EOL
            <!-- MESSAGES LIST -->
            <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">
                <tr><td colspan="99" nowrap="true" style="{$style['label_box']}">
                    Messages
                </td></tr>
                <tr><td>
EOL;
        $html .= $lineshtml;
        $js .= $linesjs;
        $html .= "</td></tr></table>";
    }    */
    // END MESSAGES LIST


    $html .= <<<EOL
        </td>
        <!-- END OF THIRD COLUMN OF SMALL BOXES -->
    </tr></table>
    </div>
    <!-- END OF TOP SECTION -->
EOL;






    // HOST LIST
    $tab = 'hosts';
    $submit_window = "list_{$tab}";
    $form_id = "{$submit_window}_filter_form";
    $_SESSION['ona'][$form_id]['tab'] = $tab;
    $content_id = "{$window_name}_{$submit_window}";
    $html .= <<<EOL
    <!-- HOST LIST -->
    <div style="border: 1px solid {$color['border']}; margin: 10px 20px;">

        <!-- Tab & Quick Filter -->
        <table id="{$form_id}_table" cellspacing="0" border="0" cellpadding="0">
            <tr>
                <td id="{$form_id}_{$tab}_tab" class="table-tab-active">
                    Associated {$tab} <span id="{$form_id}_{$tab}_count"></span>
                </td>

                <td id="{$form_id}_quick_filter" class="padding" align="right" width="100%">
                    <form id="{$form_id}" onSubmit="return false;">
                    <input id="{$form_id}_page" name="page" value="1" type="hidden">
                    <input name="content_id" value="{$content_id}" type="hidden">
                    <input name="form_id" value="{$form_id}" type="hidden">
                    <input name="subnet_id" value="{$record['id']}" type="hidden">
                    <div id="{$form_id}_filter_overlay"
                         style="position: relative;
                                display: inline;
                                color: #CACACA;
                                cursor: text;"
                         onClick="this.style.display = 'none'; el('{$form_id}_filter').focus();"
                    >Filter</div>
                    <input
                        id="{$form_id}_filter"
                        name="filter"
                        class="filter"
                        type="text"
                        value=""
                        size="10"
                        maxlength="20"
                        alt="Quick Filter"
                        onFocus="el('{$form_id}_filter_overlay').style.display = 'none';"
                        onBlur="if (this.value == '') el('{$form_id}_filter_overlay').style.display = 'inline';"
                        onKeyUp="
                            if (typeof(timer) != 'undefined') clearTimeout(timer);
                            code = 'if ({$form_id}_last_search != el(\'{$form_id}_filter\').value) {' +
                                   '    {$form_id}_last_search = el(\'{$form_id}_filter\').value;' +
                                   '    document.getElementById(\'{$form_id}_page\').value = 1;' +
                                   '    xajax_window_submit(\'{$submit_window}\', xajax.getFormValues(\'{$form_id}\'), \'display_list\');' +
                                   '}';
                            timer = setTimeout(code, 700);"
                    >
                    </form>
                </td>

            </tr>
        </table>

        <div id='{$content_id}'>{$conf['loading_icon']}</div>

        <!-- ADD HOST LINK -->
        <div class="act-box" style="padding: 2px 4px; border-top: 1px solid {$color['border']}">
            <form id="form_host_add_{$record['ID']}"
                ><input type="hidden" name="subnet_id" value="{$record['ID']}"
                ><input type="hidden" name="js" value="{$refresh}"
            ></form>
EOL;

    if (auth('host_add',$debug_val)) {
        $html .= <<<EOL
            <a title="Add host"
               class="act"
               onClick="xajax_window_submit('edit_host', xajax.getFormValues('form_host_add_{$record['ID']}'), 'editor');"
            ><img src="{$images}/silk/page_add.png" border="0"></a>&nbsp;

            <a title="Add host"
               class="act"
               onClick="xajax_window_submit('edit_host', xajax.getFormValues('form_host_add_{$record['ID']}'), 'editor');"
            >Add a new host</a>&nbsp;
EOL;
    }

    if (auth('interface_modify',$debug_val)) {
        $html .= <<<EOL

             <a title="Add interface"
               class="act"
               onClick="xajax_window_submit('edit_interface', xajax.getFormValues('form_host_add_{$record['ID']}'), 'editor');"
            ><img src="{$images}/silk/page_add.png" border="0"></a>&nbsp;

            <a title="Add interface"
               class="act"
               onClick="xajax_window_submit('edit_interface', xajax.getFormValues('form_host_add_{$record['ID']}'), 'editor');"
            >Add interface to an existing host</a>&nbsp;
EOL;
    }

    $html .= <<<EOL

            <!-- List by IP Address LINK -->
            <a title="List Hosts by IP"
               class="act"
               onClick="xajax_window_submit('app_full_list',  xajax.getFormValues('{$form_id}'), 'display');"
            ><img src="{$images}/silk/page_white_go.png" border="0"></a>&nbsp;

            <a title="List Hosts by IP"
               class="act"
               onClick="xajax_window_submit('app_full_list',  xajax.getFormValues('{$form_id}'), 'display');"
            >List Hosts by IP</a>&nbsp;

        </div>

    </div>
EOL;

    $js .= <<<EOL
        /* Setup the quick filter */
        el('{$form_id}_filter_overlay').style.left = (el('{$form_id}_filter_overlay').offsetWidth + 10) + 'px';
        {$form_id}_last_search = '';

        /* Tell the browser to load/display the list */
        xajax_window_submit('{$submit_window}', xajax.getFormValues('{$form_id}'), 'display_list');
EOL;

    // Temp turn OFF of the dhcp lease list.. its not ready yet.
    $haspool = 0;
    if ($haspool == 1) {
    // DHCP Lease LIST
    $tab = 'dhcp_leases';
    $submit_window = "list_{$tab}";
    $form_id = "{$submit_window}_filter_form";
    $_SESSION['ona'][$form_id]['tab'] = $tab;
    $content_id = "{$window_name}_{$submit_window}";
    $html .= <<<EOL
    <!-- Lease LIST -->
    <div style="border: 1px solid {$color['border']}; margin: 10px 20px;">

        <!-- Tab & Quick Filter -->
        <table id="{$form_id}_table" cellspacing="0" border="0" cellpadding="0">
            <tr>
                <td id="{$form_id}_{$tab}_tab" class="table-tab-active">
                    Associated {$tab} <span id="{$form_id}_{$tab}_count"></span>
                </td>

                <td id="{$form_id}_quick_filter" class="padding" align="right" width="100%">
                    <form id="{$form_id}" onSubmit="return false;">
                    <input id="{$form_id}_page" name="page" value="1" type="hidden">
                    <input name="content_id" value="{$content_id}" type="hidden">
                    <input name="form_id" value="{$form_id}" type="hidden">
                    <input name="subnet" value="{$record['ID']}" type="hidden">
                    <div id="{$form_id}_filter_overlay"
                         style="position: relative;
                                display: inline;
                                color: #CACACA;
                                cursor: text;"
                         onClick="this.style.display = 'none'; el('{$form_id}_filter').focus();"
                    >Filter</div>
                    <input
                        id="{$form_id}_filter"
                        name="filter"
                        class="filter"
                        type="text"
                        value=""
                        size="10"
                        maxlength="20"
                        alt="Quick Filter"
                        onFocus="el('{$form_id}_filter_overlay').style.display = 'none';"
                        onBlur="if (this.value == '') el('{$form_id}_filter_overlay').style.display = 'inline';"
                        onKeyUp="
                            if (typeof(timer) != 'undefined') clearTimeout(timer);
                            code = 'if ({$form_id}_last_search != el(\'{$form_id}_filter\').value) {' +
                                   '    {$form_id}_last_search = el(\'{$form_id}_filter\').value;' +
                                   '    document.getElementById(\'{$form_id}_page\').value = 1;' +
                                   '    xajax_window_submit(\'{$submit_window}\', xajax.getFormValues(\'{$form_id}\'), \'display_list\');' +
                                   '}';
                            timer = setTimeout(code, 700);"
                    >
                    </form>
                </td>

            </tr>
        </table>

        <div id='{$content_id}'>
            {$conf['loading_icon']}
        </div>


    </div>
EOL;
    $js .= <<<EOL
        /* Setup the quick filter */
        el('{$form_id}_filter_overlay').style.left = (el('{$form_id}_filter_overlay').offsetWidth + 10) + 'px';
        {$form_id}_last_search = '';

        /* Tell the browser to load/display the list */
        xajax_window_submit('{$submit_window}', xajax.getFormValues('{$form_id}'), 'display_list');

EOL;
    }


    // Insert the new html into the window
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $response->addAssign("work_space_content", "innerHTML", $html);
    if ($js) { $response->addScript($js . $portal_js); }
    return($response->getXML());
}


















?>