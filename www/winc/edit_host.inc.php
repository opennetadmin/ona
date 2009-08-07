<?php



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
    $host = array('fqdn' => '.');
    $interface = array();
    if (is_numeric($form['host_id'])) {
        list($status, $rows, $host) = ona_get_host_record(array('id' => $form['host_id']));
        if ($rows) {
            // Load associated INTERFACE record(s)
            list($status, $interfaces, $interface) = ona_get_interface_record(array('host_id' => $host['id']));
            list($status, $rows, $subnet) = ona_get_subnet_record(array('id' => $interface['subnet_id']));
            $interface['ip_addr'] = ip_mangle($interface['ip_addr'], 'dotted');
            if ($interface['mac_addr'])
                $interface['mac_addr'] = mac_mangle($interface['mac_addr'], 2); //FIXME: (PK) should not use numeric format specifier here!
        }
    }
    // If there is no hostid in the form
    else {
        if (strlen($form['ip_addr']) > 1) $interface['ip_addr'] = $form['ip_addr'];
        if (strlen($form['hostname']) > 1) $host['name'] = $form['hostname'];
    }

    // Set the default security level if there isn't one
    if (!array_key_exists('lvl', $host)) $host['lvl'] = $conf['ona_lvl'];

    // Load a subnet record if we got passed a subnet_id
    if ($form['subnet_id'])
        list($status, $rows, $subnet) = ona_get_subnet_record(array('id' => $form['subnet_id']));

    // Load a domain record if we got passed a domain_id
    if ($form['domain_id']) {
        list($status, $rows, $domain) = ona_get_domain_record(array('id' => $form['domain_id']));
        $host['domain_fqdn'] = $domain['fqdn'];
    }


    // Build a device_types list
    list($status, $rows, $records) = db_get_records($onadb, 'device_types', 'id >= 1');
    $device_types = array();
    foreach ($records as $type) {
        list($status, $rows, $model) = ona_get_model_record(array('id' => $type['model_id']));
        list($status, $rows, $role) = ona_get_role_record(array('id' => $type['role_id']));
        list($status, $rows, $manufacturer) = ona_get_manufacturer_record(array('id' => $model['manufacturer_id']));
        $device_types[$type['id']] = "{$manufacturer['name']} {$model['name']} ({$role['name']})";
    }
    asort($device_types);

    list($status, $rows, $device) = ona_get_device_record(array('id' => $host['device_id']));
    list($status, $rows, $location) = ona_get_location_record(array('id' => $device['location_id']));

    $host['location'] = $location['reference'];

    $device_model_list = "<option value=\"\"></option>\n";
    foreach (array_keys((array)$device_types) as $id) {
        $device_types[$id] = htmlentities($device_types[$id]);
        $selected = '';

        if ($id == $device['device_type_id']) { $selected = 'SELECTED'; }
        $device_model_list .= "<option value=\"{$id}\" {$selected}>{$device_types[$id]}</option>\n";
    }
    unset($device_types, $device, $manufacturer, $role, $model, $records);


    // Escape data for display in html
    foreach(array_keys((array)$host) as $key) { $host[$key] = htmlentities($host[$key], ENT_QUOTES); }
    foreach(array_keys((array)$subnet) as $key) { $subnet[$key] = htmlentities($subnet[$key], ENT_QUOTES); }
    foreach(array_keys((array)$interface) as $key) { $interface[$key] = htmlentities($interface[$key], ENT_QUOTES); }


    // Set the window title:
    $window['title'] = "Add Host";
    if ($host['id'])
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

        suggest_setup('set_domain_{$window_name}',    'suggest_set_domain_{$window_name}');
        suggest_setup('set_location_{$window_name}',  'suggest_set_location_{$window_name}');

        /* Setup the Quick Find location icon */

        var _button = el('qf_location_{$window_name}');
        _button.style.cursor = 'pointer';
        _button.onclick =
            function(ev) {
                if (!ev) ev = event;
                /* Create the popup div */
                wwTT(this, ev,
                     'id', 'tt_qf_location_{$window_name}',
                     'type', 'static',
                     'direction', 'south',
                     'delay', 0,
                     'styleClass', 'wwTT_qf',
                     'javascript',
                     "xajax_window_submit('tooltips', '" +
                         "tooltip=>qf_location," +
                         "id=>tt_qf_location_{$window_name}," +
                         "input_id=>set_location_{$window_name}');"
                );
            };

    el('set_host').focus();
EOL;

    // If we are modifying do not allow them to edit/change dns names.  this should only be done when creating a new host
    $hideit='';
    if ($host['id']) $hideit='style="display: none;"';

    // Define the window's inner html
    $window['html'] = <<<EOL

    <!-- Host Edit Form -->
    <form id="{$window_name}_edit_form" onSubmit="return false;">
    <input type="hidden" name="host" value="{$host['fqdn']}">
    <input type="hidden" name="interface" value="{$interface['id']}">
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

        <tr {$hideit}>
            <td class="input_required" align="right" nowrap="true">
                DNS Name
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    id="set_host"
                    name="set_host"
                    alt="Hostname"
                    value="{$host['name']}"
                    class="edit"
                    type="text"
                    size="20" maxlength="64"
                >
            </td>
        </tr>

        <tr {$hideit}>
            <td class="input_required" align="right" nowrap="true">
                Domain
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    id="set_domain_{$window_name}"
                    name="set_domain"
                    alt="Domain name"
                    value="{$host['domain_fqdn']}"
                    class="edit"
                    type="text"
                    size="25" maxlength="64"
                >
                <div id="suggest_set_domain_{$window_name}" class="suggest"></div>
            </td>
        </tr>

        <tr>
            <td class="input_required" align="right" nowrap="true">
                Device type
            </td>
            <td class="padding" align="left" width="100%">
                <select
                    name="set_type"
                    alt="Device type"
                    class="edit"
                >{$device_model_list}</select>
            </td>
        </tr>

        <tr>
            <td align="right" nowrap="true">
                Notes
            </td>
            <td class="padding" align="left" width="100%">
                <textarea name="set_notes" class="edit" cols="40" rows="1">{$host['notes']}</textarea>
            </td>
        </tr>
        <tr>
            <td align="right" nowrap="true">
                Location
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    id="set_location_{$window_name}"
                    name="set_location"
                    alt="Location"
                    value="{$host['location']}"
                    class="edit"
                    type="text"
                    size="7" maxlength="10"
                >
                <div id="suggest_set_location_{$window_name}" class="suggest"></div>
                <span id="qf_location_{$window_name}" title="Location Quick Search"><img src="{$images}/silk/find.png" border="0"/></span>
            </td>
        </tr>
EOL;

    // Display an interface edit section if it's a new host or there were exactly one interface.
    if (!$interfaces) {
        $window['js'] .= <<<EOL

        /* Setup the Quick Find for available IPs */
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
                         "text_id=>associated_subnet_{$window_name}," +
                         "text_value=>" + el('associated_subnet_{$window_name}').innerHTML + "," +
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
                Subnet
            </td>
            <td class="padding" align="left" width="100%" nowrap="true">
                <span id="associated_subnet_{$window_name}"
                >{$subnet['name']}</span>
            </td>
        </tr>

        <tr>
            <td class="input_required" align="right" nowrap="true">
                IP Address
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    id="set_ip_{$window_name}"
                    name="set_ip"
                    alt="IP Address"
                    value="{$interface['ip_addr']}"
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
                    value="{$interface['mac_addr']}"
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
                    value="{$interface['name']}"
                    class="edit"
                    type="text"
                    size="17" maxlength="255"
                >
            </td>
        </tr>

        <tr>
            <td align="right" nowrap="true">
                Interface description
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="set_description"
                    alt="Interface description"
                    value="{$interface['description']}"
                    class="edit"
                    type="text"
                    size="25" maxlength="255"
                >
            </td>
        </tr>
EOL;
    }

    if (!$host['id']) {
        $window['html'] .= <<<EOL
        <td align="right" nowrap="true">
            Auto create PTR
        </td>
        <td class="padding" align="left" width="100%" nowrap>
            <input
                id="set_addptr"
                name="set_addptr"
                alt="Automaticaly create PTR record"
                type="checkbox"
                checked="1"
            />
        </td>
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
                    accesskey=" "
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
    if ($form['set_type'] == '' or
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
    $form['set_host'] = sanitize_hostname(trim($form['set_host']));
    if (!$form['set_host']) {
        $response->addScript("alert('Invalid hostname!');");
        return($response->getXML());
    }
    // Validate domain is valid
//     list($status, $rows, $domain) = ona_find_domain($form['set_domain'],0);
//     if ($status or !$rows) {
//         $response->addScript("alert('Invalid domain!');");
//         return($response->getXML());
//     }
    // Make sure the IP address specified is valid
    if ($form['host'] != '.' and $form['set_ip']) {
        $form['set_ip'] = ip_mangle($form['set_ip'], 'dotted');
        if ($form['set_ip'] == -1) {
            $response->addScript("alert('{$self['error']}');");
            return($response->getXML());
        }
    }

    if ($form['set_addptr'] == '') $form['set_addptr'] = 'N';

    // FIXME: If we're editing, validate the $form['host'] is valid
    // FIXME: If we're editing, validate the $form['interface'] is valid
    // FIXME: Verify that the device "type" ID is valid (not a big risk since they select from a drop-down)

    // If no location is passed, make sure the value is 0
   // if (array_key_exists('set_location', $form)) $form['set_location'] = 0;

    // Decide if we're editing or adding
    $module = 'modify';
    // If we're adding, re-map some the array names to match what the "add" module wants
    if ($form['host'] == '.') {
        $module = 'add';

        // Device options
        $form['type'] = $form['set_type'];              unset($form['set_type']);
        $form['location'] = $form['set_location'];      unset($form['set_location']);

        // Host options
        $form['domain'] = $form['set_domain'];
        $form['host'] = $form['set_host'] . '.' . $form['set_domain'];          unset($form['set_host']); unset($form['set_domain']);
        $form['notes'] = $form['set_notes'];                                    unset($form['set_notes']);
        $form['description'] = $form['set_description'];                        unset($form['set_description']);

        // Interface options
        $form['ip'] = $form['set_ip'];                  unset($form['set_ip']);
        $form['mac'] = $form['set_mac'];                unset($form['set_mac']);
        $form['name'] = $form['set_name'];              unset($form['set_name']);
        $form['addptr'] = $form['set_addptr'];          unset($form['set_addptr']);
        // If there's no "refresh" javascript, add a command to view the new host
        if (!preg_match('/\w/', $form['js'])) $form['js'] = "xajax_window_submit('work_space', 'xajax_window_submit(\'display_host\', \'host=>{$form['host']}\', \'display\')');";
    }
    else {
        $form['set_host'] .= '.' . $form['set_domain'];
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
    global $include, $conf, $self, $onadb;

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
        $js .= "alert('Delete failed. " . preg_replace('/[\s\']+/', ' ', $output) . "');";
    else if ($form['js'])
        $js .= $form['js'];  // usually js will refresh the window we got called from

    // Return an XML response
    $response->addScript($js);
    return($response->getXML());
}



?>
