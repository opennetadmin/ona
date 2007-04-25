<?



//////////////////////////////////////////////////////////////////////////////
// Function: ws_display()
//
// Description:
//   Displays a host record and all associated info in the work_space div.
//////////////////////////////////////////////////////////////////////////////
function ws_display($window_name, $form='') {
    global $conf, $self, $onadb, $mysql;
    global $images, $color, $style, $msgtype;
    $html = '';
    $js = '';
    $debug_val = 3;  // used in the auth() calls to supress logging

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);

    // Load the host record
    if ($form['host_id'])
        list($status, $rows, $record) = ona_get_host_record(array('id' => $form['host_id']));
    else if ($form['host']) {
        list($status, $rows, $record) = ona_find_host($form['host']);
    }
    if ($status or !$rows) {
        array_pop($_SESSION['ona']['work_space']['history']);
        $html .= "<br><center><font color=\"red\"><b>Host doesn't exist!</b></font></center>";
        $response = new xajaxResponse();
        $response->addAssign("work_space_content", "innerHTML", $html);
        return($response->getXML());
    }
    
    // Update History Title (and tell the browser to re-draw the history div)
    $history = array_pop($_SESSION['ona']['work_space']['history']);
    $js .= "xajax_window_submit('work_space', ' ', 'rewrite_history');";
    if ($history['title'] == $window_name) {
        $history['title'] = $record['name'];
        array_push($_SESSION['ona']['work_space']['history'], $history);
    }

    // Create some javascript to refresh the current page
    $refresh = htmlentities(str_replace(array("'", '"'), array("\\'", '\\"'), $history['url']), ENT_QUOTES);
    $refresh = "xajax_window_submit('work_space', '{$refresh}');";

    // FIXME: umm.. put this somewhere else
    if (!$record['name']) $record['name'] = "NONE SET";

    // Interface (and find out how many there are)
    list($status, $interfaces, $interface) = ona_get_interface_record(array('host_id' => $record['id']), '');
    $record['IP_ADDRESS'] = ip_mangle($interface['ip_addr'], 'dotted');
    $interface_style = '';
    if ($interfaces > 1) {
        $interface_style = 'font-weight: bold;';
    }

    // Network description
    list($status, $rows, $subnet) = ona_get_subnet_record(array('ID' => $interface['subnet_id']));
    $record['SUBNET'] = $subnet['name'];
    $record['IP_SUBNET_MASK'] = ip_mangle($subnet['ip_mask'], 'dotted');
    $record['IP_SUBNET_MASK_CIDR'] = ip_mangle($subnet['ip_mask'], 'cidr');

    // Device Description
    list($status, $rows, $device) = ona_get_device_record(array('id' => $record['device_id']));
    list($status, $rows, $device_type) = ona_get_device_type_record(array('id' => $device['device_type_id']));
    list($status, $rows, $role) = ona_get_role_record(array('id' => $device_type['role_id']));
    list($status, $rows, $model) = ona_get_model_record(array('id' => $device_type['model_id']));
    list($status, $rows, $manufacturer) = ona_get_manufacturer_record(array('id' => $model['manufacturer_id']));
    $record['device'] = "{$manufacturer['name']}, {$model['name']}";
    $record['device'] = str_replace('Unknown', '?', $record['device']);

    // Device serial number and/or asset tag
    $record['serial_number'] = $device['serial_number'];
    $record['asset_tag'] = $device['asset_tag'];

    // Server info
    list($status, $rows, $server) = ona_get_server_record(array('host_id' => $record['id']));
    if ($server['DHCP_SERVER']) {$record['DHCP_SERVER'] = $server['DHCP_SERVER'];}
    if ($server['DNS_SERVER'])  {$record['DNS_SERVER']  = $server['DNS_SERVER'];}
    if ($server['ID'])          {$record['SERVER_ID']   = $server['ID'];}

    // Get location_number from the location_id
    list($status, $rows, $location) = ona_get_location_record(array('id' => $record['location_id']));



    $style['content_box'] = <<<EOL
        margin: 10px 20px;
        padding: 2px 4px;
        background-color: #FFFFFF;
        vertical-align: top;
EOL;

    $style['label_box'] = <<<EOL
        font-weight: bold;
        padding: 2px 4px;
        border: solid 1px {$color['border']};
        background-color: {$color['window_content_bg']};
EOL;

    // Escape data for display in html
    foreach(array_keys($record) as $key) { $record[$key] = htmlentities($record[$key], ENT_QUOTES); }
        // force 300px width to Notes field if the length is longer than 50 characters and put Notes at the top of the td
        $notes_width = "";
        $notes_valign = "";
        if(strlen($record['notes']) > 50) {
            $notes_width =' style="width: 300px" ';
            $notes_valign = ' valign="top" ';
        }

    $html .= <<<EOL

    <!-- FORMATTING TABLE -->
    <div style="{$style['content_box']}">
    <table cellspacing="0" border="0" cellpadding="0"><tr>

        <!-- START OF FIRST COLUMN OF SMALL BOXES -->
        <td nowrap="true" valign="top" style="padding-right: 15px;">

            <!-- HOST INFORMATION -->
            <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">

                <tr><td colspan="99" nowrap="true" style="{$style['label_box']}">
                    <!-- LABEL -->
                    <form id="form_host_{$record['id']}"
                        ><input type="hidden" name="host_id" value="{$record['id']}"
                        ><input type="hidden" name="js" value="{$refresh}"
                    ></form>
EOL;

    if (auth('host_modify',$debug_val)) {
        $html .= <<<EOL
                    <a title="Edit host. ID: {$record['id']}"
                       class="act"
                       onClick="xajax_window_submit('edit_host', xajax.getFormValues('form_host_{$record['id']}'), 'editor');"
                    ><img src="{$images}/silk/page_edit.png" border="0"></a>
EOL;
    }
    if (auth('host_del',$debug_val)) {
        $html .= <<<EOL
                    <a title="Delete host"
                       class="act"
                       onClick="var doit=confirm('Are you sure you want to delete this host?');
                                if (doit == true)
                                    xajax_window_submit('edit_host', xajax.getFormValues('form_host_{$record['id']}'), 'delete');"
                    ><img src="{$images}/silk/delete.png" border="0"></a>
EOL;
    }
    $html .= <<<EOL
                     &nbsp;{$record['name']}.<a title="View domain. ID: {$record['domain_id']}"
                                                     class="domain"
                                                     onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain\', \'domain_id=>{$record['domain_id']}\', \'display\')');"
                                                  >{$record['domain_fqdn']}</a>
                </td></tr>

                <tr>
                    <td align="right" nowrap="true"><b>Device Model</b>&nbsp;</td>
                    <td class="padding" align="left">{$record['device']}&nbsp;</td>
                </tr>

                <tr>
                    <td align="right" nowrap="true"><b>Lvl</b>&nbsp;</td>
                    <td class="padding" align="left">{$record['LVL']}&nbsp;</td>
                </tr>

                <tr>
                    <td align="right" {$notes_valign} nowrap="true"><b>Notes</b>&nbsp;</td>
                    <td class="padding" align="left" {$notes_width}>{$record['notes']}&nbsp;</td>
                </tr>

                <tr>
                    <td align="right" nowrap="true"><b>Serial Number</b>&nbsp;</td>
                    <td class="padding" align="left">{$record['serial_number']}&nbsp;</td>
                </tr>
                
                <tr>
                    <td align="right" nowrap="true"><b>Asset Tag</b>&nbsp;</td>
                    <td class="padding" align="left">{$record['asset_tag']}&nbsp;</td>
                </tr>                
            </table>
EOL;


    // SERVICE CONFIGURATION BOX
    $serverinfo = '';
    $is_dns_server = 0;
    $is_dhcp_server = 0;
    $dhcp_rows = 0;
    $domain_rows = 0;

    // Determine if this is actaually a server by counting server "uses"
    if ($record['SERVER_ID']) {
        // Is this a DNS server?
        list($status, $domain_rows, $domain_server) = db_get_records($onadb, 'DOMAIN_SERVERS_B', 'SERVER_ID = '. $onadb->qstr($record['SERVER_ID']));
        if ($domain_rows >= 1) { $is_dns_server = 1; }

        // Is this a DHCP server?
        list($status, $dhcp_rows, $dhcp_server) = db_get_records($onadb, 'DHCP_SERVER_SUBNETS_B', 'SERVER_ID = '. $onadb->qstr($record['SERVER_ID']));
        if ($dhcp_rows >= 1) { $is_dhcp_server = 1; }

        // FIXME: added temporarily to display as a server even if it has no subnets/domains assoicated with it
        // I plan on removing this later when server_b is fixed up better.
//        $is_dns_server = 1;
//        $is_dhcp_server = 1;
    }

    if ($is_dhcp_server==1) {
       $serverinfo .= <<<EOL
            <tr title="View DHCP service"
                style="cursor: pointer;"
                onMouseOver="this.className='row-highlight'"
                onMouseOut="this.className='row-normal'"
                onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_dhcp_server\', \'host_id=>{$record['ID']}\', \'display\')');"
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
            <form id="form_dhcp_serv_{$record['ID']}"
                ><input type="hidden" name="server" value="{$record['ID']}"
                ><input type="hidden" name="js" value="xajax_window_submit('work_space', 'xajax_window_submit(\'display_dhcp_server\', \'host_id=>{$record['ID']}\', \'display\')');"
            ></form>

            <tr title="Add DHCP service"
                onMouseOver="this.className='row-highlight'"
                onMouseOut="this.className='row-normal'"
EOL;

        if (auth('advanced',$debug_val)) {
            $serverinfo .= <<<EOL
                style="cursor: pointer;"
                onClick="xajax_window_submit('edit_dhcp_server', xajax.getFormValues('form_dhcp_serv_{$record['ID']}'), 'editor');"
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
                onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_dns_server\', \'host_id=>{$record['ID']}\', \'display\')');"
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
            <form id="form_dns_serv_{$record['ID']}"
                ><input type="hidden" name="server" value="{$record['ID']}"
                ><input type="hidden" name="js" value="xajax_window_submit('work_space', 'xajax_window_submit(\'display_dns_server\', \'host_id=>{$record['ID']}\', \'display\')');"
            ></form>

            <tr title="Add DNS service"
                onMouseOver="this.className='row-highlight'"
                onMouseOut="this.className='row-normal'"
EOL;

        if (auth('advanced',$debug_val)) {
            $serverinfo .= <<<EOL
                style="cursor: pointer;"
                onClick="xajax_window_submit('edit_domain_server', xajax.getFormValues('form_dns_serv_{$record['ID']}'), 'editor');"
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

    $html .= <<<EOL
            <!-- SERVICES CONFIGURATION BOX -->
            <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">
                <tr>
                    <td colspan="99" nowrap="true" style="{$style['label_box']}">
                    Services provided by this host&nbsp;</td>
                </tr>
                {$serverinfo}
            </table>
EOL;
    // END SERVICE CONFIGURATION BOX


    // INFOBIT LIST (CLASSIFICATIONS)
    $html .= <<<EOL
            <!-- INFOBIT LIST -->
            <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">
                <tr><td colspan="99" nowrap="true" style="{$style['label_box']}">
                    Roles
                </td></tr>
EOL;
    // Get a list of infobits, and loop through them
    list($status, $rows, $infobits) = db_get_records($onadb, 'roles', array('host_id' => $record['id']), '');
    if ($rows) {
        foreach ($infobits as $class) {
            list($status, $rows, $infobit) = ona_get_infobit_record(array('ID' => $class['INFOBIT_ID']));
            $class['COMMENTS'] = htmlentities($class['COMMENTS'], ENT_QUOTES);
            $infobit['VALUE'] = htmlentities($infobit['VALUE'], ENT_QUOTES);
            $infobit['NAME'] = htmlentities($infobit['NAME'], ENT_QUOTES);
            $html .= <<<EOL
                <tr onMouseOver="this.className='row-highlight';"
                    onMouseOut="this.className='row-normal';">
                    <td align="left" nowrap="true">
                        <span title="{$class['COMMENTS']}">{$infobit['NAME']} &#040;{$infobit['VALUE']}&#041;</span>
                    </td>
                    <td align="right" nowrap="true">
                        <form id="form_class_{$class['ID']}"
                            ><input type="hidden" name="class" value="{$infobit['ID']}"
                            ><input type="hidden" name="host_id" value="{$record['ID']}"
                            ><input type="hidden" name="id" value="{$class['ID']}"
                            ><input type="hidden" name="js" value="{$refresh}"
                        ></form>
EOL;
            if (auth('advanced',$debug_val)) {
            $html .= <<<EOL

                        <a title="Edit Class. ID: {$class['ID']}"
                           class="act"
                           onClick="xajax_window_submit('edit_class', xajax.getFormValues('form_class_{$class['ID']}'), 'editor');"
                        ><img src="{$images}/silk/page_edit.png" border="0"></a>&nbsp;

                        <a title="Delete Class. ID: {$class['ID']}"
                           class="act"
                           onClick="var doit=confirm('Are you sure you want to delete this role?');
                                    if (doit == true)
                                        xajax_window_submit('edit_class', xajax.getFormValues('form_class_{$class['ID']}'), 'delete');"
                        ><img src="{$images}/silk/delete.png" border="0"></a>&nbsp;
EOL;
            }
            $html .= <<<EOL
                    </td>
                </tr>
EOL;
        }
    }

    if (auth('advanced',$debug_val)) {
        $html .= <<<EOL
                <tr>
                    <td colspan="2" align="left" valign="middle" nowrap="true" class="act-box">
                        <form id="form_class_{$record['ID']}"
                            ><input type="hidden" name="host_id" value="{$record['ID']}"
                            ><input type="hidden" name="js" value="{$refresh}"
                        ></form>

                        <a title="Add class"
                           class="act"
                           onClick="xajax_window_submit('edit_class', xajax.getFormValues('form_class_{$record['ID']}'), 'editor');"
                        ><img src="{$images}/silk/page_add.png" border="0"></a>&nbsp;

                        <a title="Add class"
                           class="act"
                           onClick="xajax_window_submit('edit_class', xajax.getFormValues('form_class_{$record['ID']}'), 'editor');"
                        >Add role</a>&nbsp;
                    </td>
                </tr>
EOL;
    }

    $html .= "            </table>";

    // END INFOBIT LIST (CLASSIFICATIONS)



    $html .= <<<EOL
        <!-- END OF FIRST COLUMN OF SMALL BOXES -->
        </td>

        <!-- START OF SECOND COLUMN OF SMALL BOXES -->
        <td valign="top" style="padding-right: 15px;">
EOL;

    // LOCATION INFO BOX
    require_once('winc/tooltips.inc.php');
    list ($locationhtml, $locationjs) = get_location_html($record['location_id']);
    $html .= $locationhtml;
    $js .= $locationjs;
    // END LOCATION INFO BOX


    // DHCP ENTRIES LIST (this first table has an extra margin-top that the others don't have since the get_location_html doesn't pad itself)
    $html .= <<<EOL
            <!-- DHCP INFORMATION -->
            <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px; margin-top: 0px;">

                <!-- LABEL -->
                <tr><td colspan="99" nowrap="true" style="{$style['label_box']}">DHCP entries</td></tr>
EOL;

    list($status, $rows, $dhcp_entries) = db_get_records($onadb, 'DHCP_ENTRY_B', array('host_id' => $record['ID']), '');
    if ($rows) {
        foreach ($dhcp_entries as $entry) {
            list($status, $rows, $dhcp_type) = ona_get_dhcp_entry_record(array('ID' => $entry['ID']));
            foreach(array_keys($dhcp_type) as $key) { $dhcp_type[$key] = htmlentities($dhcp_type[$key], ENT_QUOTES); }

            $html .= <<<EOL
                <tr onMouseOver="this.className='row-highlight';"
                    onMouseOut="this.className='row-normal';">

                    <td align="left" nowrap="true">
                        {$dhcp_type['DHCP_DESCRIPTION']}&nbsp;&#061;&#062;&nbsp;{$dhcp_type['DHCP_PARAMETER_VALUE']}&nbsp;
                    </td>
                    <td align="right">
                        <form id="form_dhcp_entry_{$entry['ID']}"
                            ><input type="hidden" name="id" value="{$entry['ID']}"
                            ><input type="hidden" name="host_id" value="{$record['ID']}"
                            ><input type="hidden" name="js" value="{$refresh}"
                        ></form>
EOL;
            if (auth('advanced',$debug_val)) {
                $html .= <<<EOL
                        <a title="Edit DHCP Entry. ID: {$dhcp_type['ID']}"
                           class="act"
                           onClick="xajax_window_submit('edit_dhcp_entry', xajax.getFormValues('form_dhcp_entry_{$entry['ID']}'), 'editor');"
                        ><img src="{$images}/silk/page_edit.png" border="0"></a>&nbsp;

                        <a title="Delete DHCP Entry. ID: {$dhcp_type['ID']}"
                           class="act"
                           onClick="var doit=confirm('Are you sure you want to delete this DHCP entry?');
                                    if (doit == true)
                                        xajax_window_submit('edit_dhcp_entry', xajax.getFormValues('form_dhcp_entry_{$entry['ID']}'), 'delete');"
                        ><img src="{$images}/silk/delete.png" border="0"></a>&nbsp;
EOL;
            }
            $html .= <<<EOL
                    </td>
                </tr>

EOL;
        }
    }

    if (auth('advanced',$debug_val)) {
        $html .= <<<EOL
                <tr>
                    <td colspan="2" align="left" valign="middle" nowrap="true" class="act-box">

                        <form id="form_dhcp_entry_{$record['ID']}"
                            ><input type="hidden" name="host_id" value="{$record['ID']}"
                            ><input type="hidden" name="js" value="{$refresh}"
                        ></form>

                        <a title="Add DHCP Entry"
                           class="act"
                           onClick="xajax_window_submit('edit_dhcp_entry', xajax.getFormValues('form_dhcp_entry_{$record['ID']}'), 'editor');"
                        ><img src="{$images}/silk/page_add.png" border="0"></a>&nbsp;

                        <a title="Add DHCP Entry"
                           class="act"
                           onClick="xajax_window_submit('edit_dhcp_entry', xajax.getFormValues('form_dhcp_entry_{$record['ID']}'), 'editor');"
                        >Add DHCP Entry</a>&nbsp;
                    </td>
                </tr>
EOL;
    }

    $html .= "            </table>";

    // END DHCP ENTRIES LIST


    $html .= <<<EOL
        <!-- END OF SECOND COLUMN OF SMALL BOXES -->
        </td>


        <!-- START OF THIRD COLUMN OF SMALL BOXES -->
        <td valign="top">
EOL;





    // CONFIG ARCHIVE LIST
    // List config archives if they have permission to see them
   // if (auth('host_config_admin',$debug_val) and authlvl($record['lvl'])) {
        list($status, $total_configs, $tmp) = db_get_records($onadb, 'configurations', array('host_id' => $record['id']), '', 0);
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

            $html .= <<<EOL
            <!-- CONFIG ARCHIVES LIST -->
            <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">
            <!-- Label -->
            <tr><td colspan="99" nowrap="true" style="{$style['label_box']}">
                Config archives&nbsp;&#040;{$total_configs}&#041;
            </td></tr>
            {$row_html}
            </table>
EOL;
        }
    //}
    // END CONFIG ARCHIVE LIST


    // HOST ACTION LIST
    $html .= <<<EOL
            <!-- HOST ACTIONS LIST -->
            <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">
                <tr><td colspan="99" nowrap="true" style="{$style['label_box']}">
                    Host actions
                </td></tr>
                <tr>
                    <td align="left" nowrap="true">
                        <a title="Telnet to host"
                           class="act"
                           href="telnet:{$record['fqdn']}"
                        ><img src="{$images}/silk/lightning_go.png" border="0">Telnet</a>&nbsp;
                    </td>
                </tr>

               <!--  If SSH ever becomes something we use a bunch, we could enable it, given browser support
                     <td align="left" nowrap="true">
                        <a title="SSH to host"
                           class="act"
                           href="ssh:{$record['fqdn']}"
                        ><img src="{$images}/silk/lightning_go.png" border="0">SSH</a>&nbsp;
                    </td>
                -->
                 </tr>
EOL;
    // If this is a switch, print the switchport admin button
    if (strstr($record['DEVICE'],"Switch") and auth('switchport_admin',$debug_val)) {
        $html .= <<<EOL
                 <tr>
                    <td align="left" nowrap="true">
                        <a title="Switchport admin"
                           class="act"
                           onClick="xajax_window_submit('display_switchport_admin', 'host_id=>{$record['ID']}', 'display');"
                        ><img src="{$images}/silk/brick_edit.png" border="0">Switchport admin</a>&nbsp;
                    </td>
                </tr>
EOL;
    }
    $html .= <<<EOL
            </table>
EOL;
    // END HOST ACTION LIST




    // START MESSAGES BOX
    // $tablename is a reference directly to the table that contains the item
    // we are displaying to the user.  This is a kludge since we cannot
    // directly link the mysql tables to the onadb tables with the ID of the table.
    // It is possible that you can have the same ID in multiple tables, currently.
//     $tablename = 'HOSTS_B';
//     require_once('winc/tooltips.inc.php');
//     list($lineshtml, $linesjs) = get_message_lines_html("table_id_ref = {$record['id']} AND table_name_ref LIKE '{$tablename}'");
//     if ($lineshtml) {
//        $html .= <<<EOL
//             <!-- MESSAGES LIST -->
//             <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">
//                 <tr><td colspan="99" nowrap="true" style="{$style['label_box']}">
//                     Messages
//                 </td></tr>
//                 <tr><td>
// EOL;
//         $html .= $lineshtml;
//         $js .= $linesjs;
//         $html .= "</td></tr></table>";
//     }
    // END MESSAGES LIST








    $html .= <<<EOL
        </td>
        <!-- END OF THIRD COLUMN OF SMALL BOXES -->
    </tr></table>
    </div>
    <!-- END OF TOP SECTION -->

EOL;









    // INTERFACE LIST
    $tab = 'interfaces';
    $submit_window = "list_{$tab}";
    $form_id = "{$submit_window}_filter_form";
    $_SESSION['ona'][$form_id]['tab'] = $tab;
    $content_id = "{$window_name}_{$submit_window}";
    $html .= <<<EOL
    <!-- INTERFACE LIST -->
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
                    <input name="host_id" value="{$record['id']}" type="hidden">
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
EOL;

    if (auth('host_add',$debug_val)) {
        $html .= <<<EOL

        <!-- ADD INTERFACE LINK -->
        <div class="act-box" style="padding: 2px 4px; border-top: 1px solid {$color['border']}">
            <form id="form_interface_{$record['id']}"
                ><input type="hidden" name="host_id" value="{$record['id']}"
                ><input type="hidden" name="js" value="{$refresh}"
            ></form>

            <a title="Add interface"
               class="act"
               onClick="xajax_window_submit('edit_interface', xajax.getFormValues('form_interface_{$record['id']}'), 'editor');"
            ><img src="{$images}/silk/page_add.png" border="0"></a>&nbsp;

            <a title="Add interface"
               class="act"
               onClick="xajax_window_submit('edit_interface', xajax.getFormValues('form_interface_{$record['id']}'), 'editor');"
            >Add interface</a>&nbsp;
        </div>
EOL;
    }

    $html .= "    </div>";

    $js .= <<<EOL
        /* Setup the quick filter */
        el('{$form_id}_filter_overlay').style.left = (el('{$form_id}_filter_overlay').offsetWidth + 10) + 'px';
        {$form_id}_last_search = '';

        /* Tell the browser to load/display the list */
        xajax_window_submit('{$submit_window}', xajax.getFormValues('{$form_id}'), 'display_list');
EOL;



    // Insert the new html into the window
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $response->addAssign("work_space_content", "innerHTML", $html);
    if ($js) { $response->addScript($js); }
    return($response->getXML());
}


















?>
