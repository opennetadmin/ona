<?



//////////////////////////////////////////////////////////////////////////////
// Function: ws_display()
//
// Description:
//   Displays a host record and all associated info in the work_space div.
//////////////////////////////////////////////////////////////////////////////
function ws_display($window_name, $form='') {
    global $conf, $self, $onadb, $base;
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
    $record['ip_address'] = ip_mangle($interface['ip_addr'], 'dotted');
    $interface_style = '';
    if ($interfaces > 1) {
        $interface_style = 'font-weight: bold;';
    }

    // Subnet description
    list($status, $rows, $subnet) = ona_get_subnet_record(array('ID' => $interface['subnet_id']));
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

    // Device serial number and/or asset tag
    $record['serial_number'] = $device['serial_number'];
    $record['asset_tag'] = $device['asset_tag'];

    // Get location_number from the location_id
    list($status, $rows, $location) = ona_get_location_record(array('id' => $record['location_id']));
    // extra stuff to pass to ws_plugins
    $extravars['refresh']=$refresh;
    $extravars['window_name']=$window_name;

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

            <form id="form_host_{$record['id']}"
                ><input type="hidden" name="host_id" value="{$record['id']}"
                ><input type="hidden" name="js" value="{$refresh}"
            ></form>

EOL;

    $wspl = workspace_plugin_loader('host_detail',$record,$extravars);
    $html .= $wspl[0]; $js .= $wspl[1];
    $wspl = workspace_plugin_loader('host_services',$record);
    $html .= $wspl[0]; $js .= $wspl[1];

/* FIXME: MP this needs to be changed to custom attributes
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
*/


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

    $wspl = workspace_plugin_loader('dhcp_entries',$record,$extravars);
    $html .= $wspl[0]; $js .= $wspl[1];

    // Check if there are configs.. if so. load the config archive plugin
    // FIXME: MP: have the module check if it should be displayed or not and hide the workspace_plugin div
    list($status, $total_configs, $tmp) = db_get_records($onadb, 'configurations', array('host_id' => $record['id']), '', 0);
    if($total_configs>0) {
        $wspl = workspace_plugin_loader('config_archives',$record);
        $html .= $wspl[0]; $js .= $wspl[1];
    }


    $html .= <<<EOL
        <!-- END OF SECOND COLUMN OF SMALL BOXES -->
        </td>


        <!-- START OF THIRD COLUMN OF SMALL BOXES -->
        <td valign="top">
EOL;



    // Display the host_action workspace_plugin
    $wspl = workspace_plugin_loader('host_actions',$record);
    $html .= $wspl[0]; $js .= $wspl[1];

    // START MESSAGES BOX
    // $tablename is a reference directly to the table that contains the item
    // we are displaying to the user.
    // It is possible that you can have the same ID in multiple tables, currently.
    $tablename = 'hosts';
    require_once('winc/tooltips.inc.php');
    list($lineshtml, $linesjs) = get_message_lines_html("table_id_ref = {$record['id']} AND table_name_ref LIKE '{$tablename}'");
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
    }
    // END MESSAGES LIST


    $html .= <<<EOL
        </td>
        <!-- END OF THIRD COLUMN OF SMALL BOXES -->
    </tr></table>
    </div>
    <!-- END OF TOP SECTION -->

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
