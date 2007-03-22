<?



//////////////////////////////////////////////////////////////////////////////
// Function: ws_editor($window_name, $form)
// 
// Description:
//     Displays a form for creating/editing a host record.
// 
// Input:
//     $window_name the name of the "window" to use.
//     $form  A string-based-array or an array or a host ID.
//            The string-based-array would usually look something like this:
//              host_id=>123,js=>some('javascript');
//            If $form is a valid record ID, it is used to display and edit
//            that record.  Otherwise the form will let you add a new record.
//            The "Save" button calls the ws_save() function in this file.
// Notes:
//     If there is a "js" field passed in that contains javascript it will be
//     sent to the browser after the ws_save() function is called.
//////////////////////////////////////////////////////////////////////////////
function ws_editor($window_name, $form='') {
    global $conf, $self, $onadb;
    global $font_family, $color, $style, $images;
    $window = array();
    
    // Check permissions
    if (! (auth('host_modify') and auth('host_add')) ) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }
    
    // If an array in a string was provided, build the array and store it in $form
    $form = parse_options_string($form);
    
    // Load an existing host record (and associated info) if $form is a host_id
    $host = array();
    $host['FQDN'] = '.';
    if (is_numeric($form['host_id'])) {
        list($status, $rows, $host) = ona_get_host_record(array('ID' => $form['host_id']));
        if ($rows) {
            // Load associated ZONE and INTERFACE records
            list($status, $rows, $zone) = ona_get_zone_record(array('ID' => $host['PRIMARY_DNS_ZONE_ID']));
            $host['ZONE_NAME'] = $zone['ZONE_NAME'];
            $host['FQDN'] = $host['PRIMARY_DNS_NAME'] . '.' . $host['ZONE_NAME'];
            list($status, $interfaces, $interface) = ona_get_interface_record(array('HOST_ID' => $host['ID']));
            list($status, $rows, $network) = ona_get_subnet_record(array('ID' => $interface['NETWORK_ID']));
            $interface['IP_ADDRESS'] = ip_mangle($interface['IP_ADDRESS'], 'dotted');
            if ($interface['DATA_LINK_ADDRESS'])
                $interface['DATA_LINK_ADDRESS'] = mac_mangle($interface['DATA_LINK_ADDRESS'], 2);
            // Load associated UNIT record (get unit_number from the unit_id)
            list($status, $rows, $unit) = ona_get_unit_record(array('UNIT_ID' => $host['UNIT_ID']));
            // Unit number is best displayed as 5 digits zero padded
            $host['UNIT_NUMBER'] = str_pad($unit['UNIT_NUMBER'], 5, "0", STR_PAD_LEFT);
        }
    }
    
    // Set the default security level if there isn't one
    if (!array_key_exists('LVL', $host)) $host['LVL'] = $conf['ona_lvl'];
    
    // Load a network record if we got passed a network_id
    if ($form['network_id'])
        list($status, $rows, $network) = ona_get_subnet_record(array('ID' => $form['network_id']));
    
    // Load a zone record if we got passed a zone_id
    if ($form['zone_id']) {
        list($status, $rows, $zone) = ona_get_zone_record(array('ID' => $form['zone_id']));
        $host['ZONE_NAME'] = $zone['ZONE_NAME'];
    }
    
    
    // Build a device model list
    list($status, $rows, $records) = db_get_records($onadb, 'DEVICE_MODELS_B', 'ID >= 1');
    $models = array();
    foreach ($records as $model) {
        list($status, $rows, $manufacturer) = ona_get_manufacturer_record(array('ID' => $model['MANUFACTURER_ID']));
        list($status, $rows, $type) = ona_get_device_type_record(array('ID' => $model['DEVICE_TYPE_ID']));
        $models[$model['ID']] = "{$manufacturer['MANUFACTURER_NAME']} {$model['MODEL_DESCRIPTION']} ({$type['DEVICE_TYPE_DESCRIPTION']})";
    }
    asort($models);
    $device_model_list = '<option value="">&nbsp;</option>\n';
    foreach (array_keys($models) as $id) {
        $models[$id] = htmlentities($models[$id]);
        $selected = '';
        if ($id == $host['DEVICE_MODEL_ID']) { $selected = 'SELECTED'; }
        $device_model_list .= "<option value=\"{$id}\" {$selected}>{$models[$id]}</option>\n";
    }
    unset($models, $model);
    
    
    // Escape data for display in html
    foreach(array_keys($host) as $key) { $host[$key] = htmlentities($host[$key], ENT_QUOTES); }
    foreach(array_keys($network) as $key) { $network[$key] = htmlentities($network[$key], ENT_QUOTES); }
    foreach(array_keys($interface) as $key) { $interface[$key] = htmlentities($interface[$key], ENT_QUOTES); }
    
    
    // Set the window title:
    $window['title'] = "Add Host";
    if ($host['ID'])
        $window['title'] = "Edit Host";
    
    // Javascript to run after the window is built
    $window['js'] = <<<EOL
        /* Put a minimize icon in the title bar */
        el('{$window_name}_title_r').innerHTML = 
            '&nbsp;<a onClick="toggle_window(\'{$window_name}\');" title="Minimize window" style="cursor: pointer;"><img src="{$images}/icon_minimize.gif" border="0" /></a>' +
            el('{$window_name}_title_r').innerHTML;
        
        /* Put a help icon in the title bar */
        el('{$window_name}_title_r').innerHTML = 
            '&nbsp;<a href="{$_ENV['help_url']}{$window_name}" target="null" title="Help" style="cursor: pointer;"><img src="{$images}/silk/help.png" border="0" /></a>' +
            el('{$window_name}_title_r').innerHTML;
        
        suggest_setup('set_zone_{$window_name}',    'suggest_set_zone_{$window_name}');
        
        /* Setup the Quick Find Unit icon */
        var _button = el('qf_unit_{$window_name}');
        _button.style.cursor = 'pointer';
        _button.onclick = 
            function(ev) {
                if (!ev) ev = event;
                /* Create the popup div */
                wwTT(this, ev, 
                     'id', 'tt_qf_unit_{$window_name}', 
                     'type', 'static',
                     'direction', 'south',
                     'delay', 0,
                     'styleClass', 'wwTT_qf',
                     'javascript', 
                     "xajax_window_submit('tooltips', '" + 
                         "tooltip=>qf_unit," + 
                         "id=>tt_qf_unit_{$window_name}," +
                         "input_id=>set_unit_{$window_name}');"
                );
            };
    
EOL;
    
    // Define the window's inner html
    $window['html'] = <<<EOL
    
    <!-- Host Edit Form -->
    <form id="{$window_name}_edit_form" onSubmit="return false;">
    <input type="hidden" name="host" value="{$host['FQDN']}">
    <input type="hidden" name="interface" value="{$interface['ID']}">
    <input type="hidden" name="js" value="{$form['js']}">
    <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">
        
        <!-- HOST RECORD -->
        <tr>
            <td align="left" nowrap="true">
                <b><u>Host Record</u></b>&nbsp;
            </td>
            <td class="padding" align="left" width="100%">
                &nbsp;
            </td>
        </tr>
        
        <tr>
            <td align="right" nowrap="true">
                DNS Name
            </td>
            <td class="padding" align="left" width="100%">
                <input 
                    name="set_host" 
                    alt="Hostname"
                    value="{$host['PRIMARY_DNS_NAME']}"
                    class="edit" 
                    type="text" 
                    size="20" maxlength="64" 
                >
            </td>
        </tr>
        
        <tr>
            <td align="right" nowrap="true">
                Subdomain (zone)
            </td>
            <td class="padding" align="left" width="100%">
                <input 
                    id="set_zone_{$window_name}"
                    name="set_zone" 
                    alt="Zone name"
                    value="{$host['ZONE_NAME']}"
                    class="edit" 
                    type="text" 
                    size="25" maxlength="64" 
                >
                <div id="suggest_set_zone_{$window_name}" class="suggest"></div>
            </td>
        </tr>
        
        <tr>
            <td align="right" nowrap="true">
                Device model
            </td>
            <td class="padding" align="left" width="100%">
                <select 
                    name="set_type" 
                    alt="Device model"
                    class="edit"
                >{$device_model_list}</select>
            </td>
        </tr>
        
        <tr>
            <td align="right" nowrap="true">
                Security level
            </td>
            <td class="padding" align="left" width="100%">
                <input 
                    name="set_security_level" 
                    alt="Security level"
                    value="{$host['LVL']}"
                    class="edit" 
                    type="text" 
                    size="2" maxlength="3" 
                >
            </td>
        </tr>
        
        <tr>
            <td align="right" nowrap="true">
                Notes
            </td>
            <td class="padding" align="left" width="100%">
                <textarea name="set_notes" class="edit" cols="40" rows="1">{$host['NOTES']}</textarea>
            </td>
        </tr>
        
        <tr>
            <td align="right" nowrap="true">
                Unit number
            </td>
            <td class="padding" align="left" width="100%">
                <input 
                    id="set_unit_{$window_name}"
                    name="set_unit" 
                    alt="Unit number"
                    value="{$host['UNIT_NUMBER']}"
                    class="edit" 
                    type="text" 
                    size="7" maxlength="10" 
                >
                <span id="qf_unit_{$window_name}" title="Unit Quick Search"><img src="{$images}/silk/find.png" border="0"/></span>
            </td>
        </tr>
EOL;
    
    // Display an interface edit section if it's a new host or there were exactly one interface.
    if (!$interfaces or $interfaces == 1) {
        $window['js'] .= <<<EOL
        
        /* Setup the Quick Find FREE IP icon */
        var _button = el('qf_free_ip_{$window_name}');
        _button.style.cursor = 'pointer';
        _button.onclick = 
            function(ev) {
                if (!ev) ev = event;
                /* Create the popup div */
                wwTT(this, ev, 
                     'id', 'tt_qf_free_ip_{$window_name}', 
                     'type', 'static',
                     'direction', 'south',
                     'delay', 0,
                     'styleClass', 'wwTT_qf',
                     'javascript', 
                     "xajax_window_submit('tooltips', '" + 
                         "tooltip=>qf_free_ip," + 
                         "id=>tt_qf_free_ip_{$window_name}," +
                         "text_id=>associated_network_{$window_name}," +
                         "text_value=>" + el('associated_network_{$window_name}').innerHTML + "," +
                         "input_id=>set_ip_{$window_name}');"
                );
            };
        
EOL;
        
        $window['html'] .= <<<EOL
        
        <!-- FIRST INTERFACE -->
        <tr>
            <td align="left" nowrap="true">
                <b><u>Interface</u></b>&nbsp;
            </td>
            <td class="padding" align="left" width="100%">
                &nbsp;
            </td>
        </tr>
        
        <tr>
            <td align="right" nowrap="true">
                Network
            </td>
            <td class="padding" align="left" width="100%" nowrap="true">
                <span id="associated_network_{$window_name}"
                >{$network['DESCRIPTION']}</span>
            </td>
        </tr>
        
        <tr>
            <td align="right" nowrap="true">
                IP Address
            </td>
            <td class="padding" align="left" width="100%">
                <input 
                    id="set_ip_{$window_name}"
                    name="set_ip" 
                    alt="IP Address"
                    value="{$interface['IP_ADDRESS']}"
                    class="edit" 
                    type="text" 
                    size="25" maxlength="64" 
                >
                <span id="qf_free_ip_{$window_name}" title="Available IP Quick Search"><img src="{$images}/silk/find.png" border="0"/></span>
                <div id="suggest_set_ip_{$window_name}" class="suggest"></div>
            </td>
        </tr>
        
        <tr>
            <td align="right" nowrap="true">
                MAC Address
            </td>
            <td class="padding" align="left" width="100%">
                <input 
                    name="set_mac"
                    alt="MAC Address"
                    value="{$interface['DATA_LINK_ADDRESS']}"
                    class="edit" 
                    type="text" 
                    size="17" maxlength="17" 
                >
                <a class="nav" onClick="this.style.display = 'none'; el('force_{$window_name}').style.display = browser.isIE ? 'block' : 'table-row';">More >></a>
            </td>
        </tr>
        
        <tr id="force_{$window_name}" style="display: none;">
            <td align="right" nowrap="true">
                &nbsp;
            </td>
            <td class="padding" align="left" width="100%">
                <input 
                    name="force" 
                    alt="Allow duplicate MAC addresses"
                    type="checkbox" 
                > Allow duplicate MAC addresses
            </td>
        </tr>
        
        <tr>
            <td align="right" nowrap="true">
                Interface name
            </td>
            <td class="padding" align="left" width="100%">
                <input 
                    name="set_name" 
                    alt="Interface name"
                    value="{$interface['INTERFACE_NAME']}"
                    class="edit" 
                    type="text" 
                    size="17" maxlength="17" 
                >
            </td>
        </tr>
EOL;
    }
    
    if (!$host['ID']) {
        $window['html'] .= <<<EOL
        <tr>
            <td align="right" nowrap="true">
                &nbsp;
            </td>
            <td class="padding" align="left" width="100%">
                <input 
                    name="keepadding" 
                    alt="Keep adding more hosts"
                    type="checkbox" 
                > Keep adding more hosts
            </td>
        </tr>

        <tr>
            <td colspan="2" class="padding" align="center" width="100%">
            <span id="statusinfo_{$window_name}" style="color: green;" ></span>
            </td>
        </tr>

EOL;
    }    
    
    $window['html'] .= <<<EOL
        
        <tr>
            <td align="right" valign="top" nowrap="true">
                &nbsp;
            </td>
            <td class="padding" align="right" width="100%">
                <input class="edit" type="button" name="cancel" value="Cancel" onClick="removeElement('{$window_name}');">
                <input class="edit" type="button" 
                    name="submit" 
                    value="Save" 
                    onClick="xajax_window_submit('{$window_name}', xajax.getFormValues('{$window_name}_edit_form'), 'save');"
                >
            </td>
        </tr>
    
    </table>
    </form>        
EOL;
    
    return(window_open($window_name, $window));
}








//////////////////////////////////////////////////////////////////////////////
// Function:
//     Save Form
// 
// Description:
//     Creates/updates a host record.
//////////////////////////////////////////////////////////////////////////////
function ws_save($window_name, $form='') {
    global $include, $conf, $self, $onadb;
    
    // Check permissions
    if (! (auth('host_modify') and auth('host_add')) ) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }
    
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = '';
    
    // Validate input
    if ($form['set_host'] == '' or 
        $form['set_zone'] == '' or 
        $form['set_type'] == '' or
        $form['set_security_level'] == '' or
        $form['set_unit'] == '' or
        /* Interface input: required only if adding a host */
        ($form['host'] == '.' and $form['set_ip'] == '')
       ) {
        $response->addScript("alert('Please complete all fields to continue!');");
        return($response->getXML());
    }
    
    // Since we're adding two records (host and an interface)
    // we need to do a little validation here to make sure things
    // have a good chance of working!
    
    // Validate the "set_host" name is valid
    $form['set_host'] = sanitize_hostname($form['set_host']);
    if (!$form['set_host']) {
        $response->addScript("alert('Invalid hostname!');");
        return($response->getXML());
    }
    // Validate zone is valid
    list($status, $rows, $zone) = ona_get_zone_record(array('ZONE_NAME' => $form['set_zone']));
    if ($status or !$rows) {
        $response->addScript("alert('Invalid zone!');");
        return($response->getXML());
    }
    // Sanitize the security level
    $form['set_security_level'] = sanitize_security_level($form['set_security_level']);
    if ($form['set_security_level'] == -1) {
        $response->addScript("alert('{$self['error']}');");
        return($response->getXML());
    }
    // Make sure the IP address specified is valid
    if ($form['host'] != '.' and $form['set_ip']) {
        $form['set_ip'] = ip_mangle($form['set_ip'], 'dotted');
        if ($form['set_ip'] == -1) {
            $response->addScript("alert('{$self['error']}');");
            return($response->getXML());
        }
    }
    
    // FIXME: If we're editing, validate the $form['host'] is valid
    // FIXME: If we're editing, validate the $form['interface'] is valid
    // FIXME: Verify that the device "type" ID is valid (not a big risk since they select from a drop-down)
    
    
    
    // Decide if we're editing or adding
    $module = 'modify';
    // If we're adding, re-map some the array names to match what the "add" module wants
    if ($form['host'] == '.') {
        $module = 'add';
        
        // Host options
        $form['host'] = $form['set_host'] . '.' . $form['set_zone'];
        $form['type'] = $form['set_type'];
        $form['unit'] = $form['set_unit'];
        $form['security_level'] = $form['set_security_level'];
        $form['notes'] = $form['set_notes'];
        
        // Interface options
        $form['ip'] = $form['set_ip'];
        $form['mac'] = $form['set_mac'];
        $form['name'] = $form['set_name'];
        
        // If there's no "refresh" javascript, add a command to view the new host
        if (!preg_match('/\w/', $form['js'])) $form['js'] = "xajax_window_submit('work_space', 'xajax_window_submit(\'display_host\', \'host=>{$form['host']}\', \'display\')');";
    }
    else {
        $form['set_host'] .= '.' . $form['set_zone'];
    }
    
    // Run the module to ADD the HOST AND INTERFACE, or MODIFY THE HOST.
    list($status, $output) = run_module('host_'.$module, $form);
    
    // If the module returned an error code display a popup warning
    if ($status)
        $js .= "alert('Save failed.\\n". preg_replace('/[\s\']+/', ' ', $self['error']) . "');";
    else {
        // Run the module to MODIFY THE INTERFACE if we need to
        if ($module == 'modify' and $form['set_ip']) {
            list($status, $output) = run_module('interface_'.$module, $form);
        }
        // If the module returned an error code display a popup warning
        if ($status and $module == 'modify' and $form['set_ip'])
            $js .= "alert('Interface update failed.\\n". preg_replace('/[\s\']+/', ' ', $self['error']) . "');";
        else {
            // if they have checked the keep adding hosts box then dont remove the window
            if (!$form['keepadding'])
                $js .= "removeElement('{$window_name}');";
            else {
                $js .= "el('statusinfo_{$window_name}').innerHTML = 'Previously added:<br>{$form['host']} => {$form['ip']}';";
            }
            
            if ($form['js']) $js .= $form['js'];
        }
    }
    
    // Insert the new table into the window
    $response->addScript($js);
    return($response->getXML());
}







//////////////////////////////////////////////////////////////////////////////
// Function:
//     Delete Form
// 
// Description:
//     Deletes a host record.  $form should be an array with a 'host_id' 
//     key defined and optionally a 'js' key with javascript to have the
//     browser run after a successful delete.
//////////////////////////////////////////////////////////////////////////////
function ws_delete($window_name, $form='') {
    global $include, $conf, $self, $onadb, $onadb;
    
    // Check permissions
    if (!auth('host_del')) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }
    
    // If an array in a string was provided, build the array and store it in $form
    $form = parse_options_string($form);
    
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = '';
    
    // Run the module
    list($status, $output) = run_module('host_del', array('host' => $form['host_id'], 'commit' => $form['commit']));
    
    // If commit was N, display the confirmation dialog box
    if (!$form['commit']) {
        $build_commit_html = 1;
        $commit_function = 'delete';
        include(window_find_include('module_results'));
        return(window_open("{$window_name}_results", $window));
    }
    
    // If the module returned an error code display a popup warning
    if ($status)
        $js .= "alert('Delete failed. " . preg_replace('/[\s\']+/', ' ', $self['error']) . "');";
    else if ($form['js']) 
        $js .= $form['js'];  // usually js will refresh the window we got called from
    
    // Return an XML response
    $response->addScript($js);
    return($response->getXML());
}



?>