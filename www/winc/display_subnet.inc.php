<?
// Include map portal functions for the subnet_map workspace plugin
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


    list($status, $rows, $type) = ona_get_subnet_type_record(array('id' => $record['subnet_type_id']));
    $record['type'] = $type['display_name'];

    // Vlan Record
    list($status, $rows, $vlan) = ona_get_vlan_record(array('id' => $record['vlan_id']));
    $record['vlan_name']        = $vlan['name'];
    $record['vlan_campus_id']   = $vlan['vlan_campus_id'];
    $record['vlan_campus_name'] = $vlan['vlan_campus_name'];

    // extra stuff to pass to ws_plugins
    $extravars['refresh']=$refresh;
    $extravars['window_name']=$window_name;


    // Escape data for display in html
    foreach(array_keys($record) as $key) { $record[$key] = htmlentities($record[$key], ENT_QUOTES); }

    // Create a div for workspace plugins to live
    $html .= "<div id='wsplugins' style='margin: 10px;'>";

    $wspl = workspace_plugin_loader('subnet_detail',$record,$extravars);
    $html .= $wspl[0]; $js .= $wspl[1];

    $wspl = workspace_plugin_loader('dhcp_servers',$record,$extravars);
    $html .= $wspl[0]; $js .= $wspl[1];

    $wspl = workspace_plugin_loader('dhcp_pools',$record,$extravars);
    $html .= $wspl[0]; $js .= $wspl[1];

    $wspl = workspace_plugin_loader('dhcp_entries',$record,$extravars);
    $html .= $wspl[0]; $js .= $wspl[1];

    $wspl = workspace_plugin_loader('custom_attributes',$record,$extravars);
    $html .= $wspl[0]; $js .= $wspl[1];

    // Display messages
    $wspl = workspace_plugin_loader('messages',$record,$extravars);
    $html .= $wspl[0]; $js .= $wspl[1];

    $html .= <<<EOL

    </div>
    <br style="clear:both;">

    <form id="form_subnet_{$record['id']}"
        ><input type="hidden" name="subnet_id" value="{$record['id']}"
        ><input type="hidden" name="js" value="{$refresh}"
    ></form>

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
                         title="Filter"
                         style="position: relative;
                                display: inline;
                                color: #CACACA;
                                cursor: text;"
                         onClick="this.style.display = 'none'; el('{$form_id}_filter').focus();"
                    >Name</div>
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
            <form id="form_host_add_{$record['id']}"
                ><input type="hidden" name="subnet_id" value="{$record['id']}"
                ><input type="hidden" name="js" value="{$refresh}"
            ></form>
EOL;

    if (auth('host_add',$debug_val)) {
        $html .= <<<EOL
            <a title="Add host"
               class="act"
               onClick="xajax_window_submit('edit_host', xajax.getFormValues('form_host_add_{$record['id']}'), 'editor');"
            ><img src="{$images}/silk/page_add.png" border="0"></a>&nbsp;

            <a title="Add host"
               class="act"
               onClick="xajax_window_submit('edit_host', xajax.getFormValues('form_host_add_{$record['id']}'), 'editor');"
            >Add a new host</a>&nbsp;
EOL;
    }

    if (auth('interface_modify',$debug_val)) {
        $html .= <<<EOL

             <a title="Add interface"
               class="act"
               onClick="xajax_window_submit('edit_interface', xajax.getFormValues('form_host_add_{$record['id']}'), 'editor');"
            ><img src="{$images}/silk/page_add.png" border="0"></a>&nbsp;

            <a title="Add interface"
               class="act"
               onClick="xajax_window_submit('edit_interface', xajax.getFormValues('form_host_add_{$record['id']}'), 'editor');"
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
                    <input name="subnet" value="{$record['id']}" type="hidden">
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
    if ($js) { $response->addScript($js); }
    return($response->getXML());
}


















?>