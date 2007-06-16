<?



//////////////////////////////////////////////////////////////////////////////
// Function: ws_display()
//
// Description:
//   Displays a DHCP server record and all associated info in the work_space div.
//////////////////////////////////////////////////////////////////////////////
function ws_display($window_name, $form='') {
    global $conf, $self, $onadb;
    global $images, $color, $style;
    $html = '';
    $js = '';
    $debug_val = 3;  // used in the auth() calls to supress logging

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);

    // Load the server record
    list($status, $rows, $record) = ona_get_host_record(array('id' => $form['host_id']));
    if ($status or !$rows) {
        array_pop($_SESSION['ona']['work_space']['history']);
        $html .= "<br><center><font color=\"red\"><b>Server doesn't exist!</b></font></center>";
        $response = new xajaxResponse();
        $response->addAssign("work_space_content", "innerHTML", $html);
        return($response->getXML());
    }

    // Pick up host information
    list($status, $rows, $host) = ona_find_host($form['host_id']);
    $record['fqdn'] = $host['fqdn'];

    // Update History Title
    $history = array_pop($_SESSION['ona']['work_space']['history']);
    $js .= "xajax_window_submit('work_space', ' ', 'rewrite_history');";
    if ($history['title'] == $window_name) {
        $history['title'] = "DHCP server - ". $record['name'];
        array_push($_SESSION['ona']['work_space']['history'], $history);
    }

    // Create some javascript to refresh the current page
    $refresh = htmlentities(str_replace(array("'", '"'), array("\\'", '\\"'), $history['url']), ENT_QUOTES);
    $refresh = "xajax_window_submit('work_space', '{$refresh}');";


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

    $html .= <<<EOL
    <!-- FORMATTING TABLE -->
    <div style="{$style['content_box']}">
    <table cellspacing="0" border="0" cellpadding="0"><tr>

        <!-- START OF FIRST COLUMN OF SMALL BOXES -->
        <td nowrap="true" valign="top" style="padding-right: 15px;">
EOL;


    // SERVER INFORMATION
    $html .= <<<EOL
            <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">
                <!-- LABEL -->
                <tr><td colspan="99" nowrap="true" style="{$style['label_box']}">
                    DHCP server <a title="View host"
                           class="nav"
                           onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_host\', \'host_id=>{$record['id']}\', \'display\')');"
                        >{$record['name']}</a>.<a title="View domain. ID: {$record['domain_id']}"
                                                     class="domain"
                                                     onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain\', \'domain_id=>{$record['domain_id']}\', \'display\')');"
                                                  >{$record['domain_fqdn']}</a>
                </td></tr>
            </table>
EOL;
    // END SERVER INFORMATION


    $html .= <<<EOL
        <!-- END OF FIRST COLUMN OF SMALL BOXES -->
        </td>

        <!-- START OF SECOND COLUMN OF SMALL BOXES -->
        <td valign="top" style="padding-right: 15px;">
EOL;

// FIXME:  MP.. well this is a nice thing to have but its off for now
//         The reason is that there is no unique server table so I cant associate these records with the host_id because
//         it would conflict with the existing host dhcp  entries.  maybe another field indicating it is server related???
//
//
//     // DHCP ENTRIES BOX
//     $html .= <<<EOL
//             <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">
//
//                 <!-- LABEL -->
//                 <tr><td colspan="2" nowrap="true" style="{$style['label_box']}">DHCP entries</td></tr>
// EOL;
//
//
//     list($status, $rows, $dhcp_entries) = db_get_records($onadb, 'dhcp_option_entries', array('host_id' => $record['id']), '');
//     if ($rows) {
//         foreach ($dhcp_entries as $entry) {
//             list($status, $rows, $dhcp_type) = ona_get_dhcp_option_entry_record(array('id' => $entry['id']));
//             foreach(array_keys($dhcp_type) as $key) { $dhcp_type[$key] = htmlentities($dhcp_type[$key], ENT_QUOTES); }
//
//             $html .= <<<EOL
//                 <tr onMouseOver="this.className='row-highlight';"
//                     onMouseOut="this.className='row-normal';">
//
//                     <td align="left" nowrap="true">
//                         {$dhcp_type['display_name']}&nbsp;&#061;&#062;&nbsp;{$dhcp_type['value']}&nbsp;
//                     </td>
//                     <td align="right">
//                         <form id="form_dhcp_entry_{$entry['id']}"
//                             ><input type="hidden" name="id" value="{$entry['id']}"
//                             ><input type="hidden" name="server_id" value="{$record['id']}"
//                             ><input type="hidden" name="js" value="{$refresh}"
//                         ></form>
// EOL;
//             if (auth('advanced',$debug_val)) {
//                 $html .= <<<EOL
//                         <a title="Edit DHCP Entry. ID: {$dhcp_type['id']}"
//                            class="act"
//                            onClick="xajax_window_submit('edit_dhcp_option_entry', xajax.getFormValues('form_dhcp_entry_{$entry['id']}'), 'editor');"
//                         ><img src="{$images}/silk/page_edit.png" border="0"></a>&nbsp;
//
//                         <a title="Delete DHCP Entry. ID: {$dhcp_type['id']}"
//                            class="act"
//                            onClick="var doit=confirm('Are you sure you want to delete this DHCP entry?');
//                                     if (doit == true)
//                                         xajax_window_submit('edit_dhcp_option_entry', xajax.getFormValues('form_dhcp_entry_{$entry['id']}'), 'delete');"
//                         ><img src="{$images}/silk/delete.png" border="0"></a>&nbsp;
// EOL;
//             }
//             $html .= <<<EOL
//                     </td>
//                 </tr>
//
// EOL;
//         }
//     }
//
//     if (auth('advanced',$debug_val)) {
//         $html .= <<<EOL
//                 <tr>
//                     <td colspan="2" align="left" valign="middle" nowrap="true" class="act-box">
//
//                         <form id="form_dhcp_entry_add_{$record['id']}"
//                             ><input type="hidden" name="server_id" value="{$record['id']}"
//                             ><input type="hidden" name="js" value="{$refresh}"
//                         ></form>
//
//                         <a title="Add DHCP Entry"
//                            class="act"
//                            onClick="xajax_window_submit('edit_dhcp_option_entry', xajax.getFormValues('form_dhcp_entry_add_{$record['id']}'), 'editor');"
//                         ><img src="{$images}/silk/page_add.png" border="0"></a>&nbsp;
//
//                         <a title="Add DHCP Entry"
//                            class="act"
//                            onClick="xajax_window_submit('edit_dhcp_option_entry', xajax.getFormValues('form_dhcp_entry_add_{$record['id']}'), 'editor');"
//                         >Add DHCP Entry</a>&nbsp;
//                     </td>
//                 </tr>
// EOL;
//     }
//
//     $html .= "            </table>";
//
//     // END DHCP ENTRIES LIST



    $html .= <<<EOL
        <!-- END OF SECOND COLUMN OF SMALL BOXES -->
        </td>

        <!-- START OF THIRD COLUMN OF SMALL BOXES -->
        <td valign="top" style="padding-right: 15px;">
EOL;



    // FAILOVER GROUP INFO BOX
    // get failover group information
    list($status, $rows, $failover_groups) = db_get_records($onadb, 'dhcp_failover_groups', "primary_server_id = {$record['id']} or secondary_server_id = {$record['id']}");

    if ($rows) {
    $html .= <<<EOL
            <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">

                <!-- LABEL -->
                <tr><td colspan="2" nowrap="true" style="{$style['label_box']}">Failover groups</td></tr>
EOL;

        foreach ($failover_groups as $failover) {
            // Get DNS name for primary and secondary servers
         //   list($status, $rows, $fail_pri_server) = ona_get_server_record(array('ID' => $failover['PRIMARY_SERVER_ID']));
            list($status, $rows, $fail_pri_host)   = ona_get_host_record(array('id' => $failover['primary_server_id']));
         //   list($status, $rows, $fail_sec_server) = ona_get_server_record(array('ID' => $failover['SECONDARY_SERVER_ID']));
            list($status, $rows, $fail_sec_host)   = ona_get_host_record(array('id' => $failover['secondary_server_id']));


            $html .= <<<EOL
                <tr onMouseOver="this.className='row-highlight';"
                    onMouseOut="this.className='row-normal';">
                    <td align="left">GROUP_ID-{$failover['id']}&#058;&nbsp;{$fail_pri_host['name']}<img src="{$images}/silk/link.png" border="0">{$fail_sec_host['name']}&nbsp;</td>
                    <td align="right">
                        <a title="Edit failover group.  ID: {$failover['id']}"
                           class="act"
                           onClick="xajax_window_submit('edit_dhcp_failover_group', '{$failover['id']}', 'editor');"
                        ><img src="{$images}/silk/page_edit.png" border="0"></a>
                    </td>
                </tr>
            </table>
EOL;
        }
    }
    // END FAILOVER GROUP INFO BOX


    $html .= <<<EOL
        </td>
        <!-- END OF THIRD COLUMN OF SMALL BOXES -->
    </tr></table>
    </div>
    <!-- END OF TOP SECTION -->
EOL;






    // SUBNET LIST
    $tab = 'dhcp_server';
    $submit_window = "list_{$tab}";
    $form_id = "{$submit_window}_filter_form";
    $_SESSION['ona'][$form_id]['tab'] = $tab;
    $content_id = "{$window_name}_{$submit_window}";
    $html .= <<<EOL
    <!-- SUBNET LIST -->
    <div style="border: 1px solid {$color['border']}; margin: 10px 20px;">

        <!-- Tab & Quick Filter -->
        <table id="{$form_id}_table" cellspacing="0" border="0" cellpadding="0">
            <tr>
                <td id="{$form_id}_subnets_tab" class="table-tab-active">
                    Assigned Subnets <span id="{$form_id}_{$tab}_count"></span>
                </td>

                <td id="{$form_id}_quick_filter" class="padding" align="right" width="100%">
                    <form id="{$form_id}" onSubmit="return false;">
                    <input id="{$form_id}_page" name="page" value="1" type="hidden">
                    <input name="content_id" value="{$content_id}" type="hidden">
                    <input name="form_id" value="{$form_id}" type="hidden">
                    <input name="server_id" value="{$record['id']}" type="hidden">
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
    if (auth('advanced',$debug_val)) {
        $html .= <<<EOL
        <div class="act-box" style="padding: 2px 4px; border-top: 1px solid {$color['border']}">
            <form id="form_dhcp_server_{$record['id']}"
                    ><input type="hidden" name="server" value="{$record['id']}"
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
            >Assign subnet</a>&nbsp;
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