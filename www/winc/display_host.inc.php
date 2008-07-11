<?



//////////////////////////////////////////////////////////////////////////////
// Function: ws_display()
//
// Description:
//   Displays a host record and all associated info in the work_space div.
//////////////////////////////////////////////////////////////////////////////
function ws_display($window_name, $form='') {
    global $conf, $self, $onadb, $base;
    global $images, $color, $style;
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
    $record['ip_address'] = ip_mangle($interface['ip_addr'], 'dotted');
    $interface_style = '';
    if ($interfaces > 1) {
        $interface_style = 'font-weight: bold;';
    }

    // Subnet description
    list($status, $rows, $subnet) = ona_get_subnet_record(array('id' => $interface['subnet_id']));
    $record['subnet'] = $subnet['name'];
    $record['ip_subnet_mask'] = ip_mangle($subnet['ip_mask'], 'dotted');
    $record['ip_subnet_mask_cidr'] = ip_mangle($subnet['ip_mask'], 'cidr');

    // Device Description
    list($status, $rows, $device) = ona_get_device_record(array('id' => $record['device_id']));
    $record['device_type_id'] = $device['device_type_id'];
    list($status, $rows, $device_type) = ona_get_device_type_record(array('id' => $device['device_type_id']));
    list($status, $rows, $role) = ona_get_role_record(array('id' => $device_type['role_id']));
    list($status, $rows, $model) = ona_get_model_record(array('id' => $device_type['model_id']));
    list($status, $rows, $manufacturer) = ona_get_manufacturer_record(array('id' => $model['manufacturer_id']));
    $record['devicefull'] = "{$manufacturer['name']}, {$model['name']} ({$role['name']})";
    $record['device'] = str_replace('Unknown', '?', $record['devicefull']);
    $record['location_id'] = $device['location_id']; 

    // Device serial number and/or asset tag
    $record['serial_number'] = $device['serial_number'];
    $record['asset_tag'] = $device['asset_tag'];

    // Get location_number from the location_id
    list($status, $rows, $location) = ona_get_location_record(array('id' => $record['location_id']));

    // extra stuff to pass to ws_plugins
    $extravars['refresh']=$refresh;
    $extravars['window_name']=$window_name;

    // Escape data for display in html
    foreach(array_keys($record) as $key) { $record[$key] = htmlentities($record[$key], ENT_QUOTES); }

    // Create a div for workspace plugins to live
    $html .= "<div id='wsplugins' style='margin: 10px;'>";

    // Start displaying all the ws plugins
    $wspl = workspace_plugin_loader('host_detail',$record,$extravars);
    $html .= $wspl[0]; $js .= $wspl[1];

    $wspl = workspace_plugin_loader('host_services',$record);
    $html .= $wspl[0]; $js .= $wspl[1];

    $wspl = workspace_plugin_loader('custom_attributes',$record,$extravars);
    $html .= $wspl[0]; $js .= $wspl[1];

    $wspl = workspace_plugin_loader('dhcp_entries',$record,$extravars);
    $html .= $wspl[0]; $js .= $wspl[1];

    $wspl = workspace_plugin_loader('config_archives',$record);
    $html .= $wspl[0]; $js .= $wspl[1];

    // Display the host_action workspace_plugin
    $wspl = workspace_plugin_loader('host_actions',$record);
    $html .= $wspl[0]; $js .= $wspl[1];

    // Display messages
    $wspl = workspace_plugin_loader('messages',$record,$extravars);
    $html .= $wspl[0]; $js .= $wspl[1];

    $html .= <<<EOL

    </div>
    <br style="clear:both;">

    <form id="form_host_{$record['id']}"
        ><input type="hidden" name="host_id" value="{$record['id']}"
        ><input type="hidden" name="js" value="{$refresh}"
    ></form>

EOL;





    // RECORD LIST
    $tab = 'records';
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
                    Associated DNS {$tab} <span id="{$form_id}_{$tab}_count"></span>
                </td>

                <td id="{$form_id}_quick_filter" class="padding" align="right" width="100%">
                    <form id="{$form_id}" onSubmit="return false;">
                    <input id="{$form_id}_page" name="page" value="1" type="hidden">
                    <input name="content_id" value="{$content_id}" type="hidden">
                    <input name="form_id" value="{$form_id}" type="hidden">
                    <input name="host_id" value="{$record['id']}" type="hidden">
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

        <div id='{$content_id}'>
            {$conf['loading_icon']}
        </div>
EOL;

    if (auth('host_add',$debug_val)) {
        $html .= <<<EOL

        <!-- ADD RECORD LINK -->
        <div class="act-box" style="padding: 2px 4px; border-top: 1px solid {$color['border']}">
            <form id="form_record_{$record['id']}"
                ><input type="hidden" name="host_id" value="{$record['id']}"
                ><input type="hidden" name="js" value="{$refresh}"
            ></form>

            <a title="Add DNS record"
               class="act"
               onClick="xajax_window_submit('edit_record', xajax.getFormValues('form_record_{$record['id']}'), 'editor');"
            ><img src="{$images}/silk/font_add.png" border="0"></a>&nbsp;

            <a title="Add DNS record"
               class="act"
               onClick="xajax_window_submit('edit_record', xajax.getFormValues('form_record_{$record['id']}'), 'editor');"
            >Add DNS record</a>&nbsp;
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
                         title="Filter"
                         style="position: relative;
                                display: inline;
                                color: #CACACA;
                                cursor: text;"
                         onClick="this.style.display = 'none'; el('{$form_id}_filter').focus();"
                    >Full IP</div>
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
