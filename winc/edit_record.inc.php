<?



//////////////////////////////////////////////////////////////////////////////
// Function: ws_editor($window_name, $form)
//
// Description:
//     Displays a form for creating/editing a DNS record.
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
    if (! (auth('record_modify') and auth('record_add')) ) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }

    // If an array in a string was provided, build the array and store it in $form
    $form = parse_options_string($form);

    // Load an existing DNS record (and associated info) if $form is a host_id
    $host = array('fqdn' => '.');
    $interface = array();
    if (is_numeric($form['record_id'])) {
        list($status, $rows, $dns_record) = ona_get_dns_record(array('id' => $form['record_id']));
        if ($rows) {
            // Load associated INTERFACE record(s)
            list($status, $interfaces, $interface) = ona_get_interface_record(array('id' => $dns_record['interface_id']));
            $interface['ip_addr'] = ip_mangle($interface['ip_addr'], 'dotted');
            list($status, $rows, $domain) = ona_get_domain_record(array('id' => $dns_record['domain_id']));
            $dns_record['domain_fqdn'] = $domain['fqdn'];
        }
    }


    // Load a domain record if we got passed a domain_id
    if ($form['domain_id']) {
        list($status, $rows, $domain) = ona_get_domain_record(array('id' => $form['domain_id']));
        $dns_record['domain_fqdn'] = $domain['fqdn'];
    }

    // Set up the types of records we can edit with this form
    $record_types = array('A','CNAME','TXT','NS','MX','AAAA','SRV');
    foreach (array_keys((array)$record_types) as $id) {
        //$device_types[$id] = htmlentities($device_types[$id]);
        $selected = '';
        if ($record_types[$id] == $dns_record['type']) { $selected = 'SELECTED'; }
        $record_type_list .= "<option value=\"{$record_types[$id]}\" {$selected}>{$record_types[$id]}</option>\n";
    }


    // Escape data for display in html
    foreach(array_keys((array)$dns_record) as $key) { $dns_record[$key] = htmlentities($dns_record[$key], ENT_QUOTES); }
    foreach(array_keys((array)$interface) as $key) { $interface[$key] = htmlentities($interface[$key], ENT_QUOTES); }


    // Set the window title:
    $window['title'] = "Add DNS Record";
    if ($dns_record['id']) {
        $window['title'] = "Edit DNS Record";
       // $editdisplay = "display:none";
    }

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
EOL;

    // Define the window's inner html
    $window['html'] = <<<EOL

    <!-- DNS Record Edit Form -->
    <form id="{$window_name}_edit_form" onSubmit="return false;">
    <input type="hidden" name="host" value="{$host['fqdn']}">
    <input type="hidden" name="interface" value="{$interface['id']}">
    <input type="hidden" name="js" value="{$form['js']}">


    <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']};padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">
        <!-- DNS RECORD -->
        <tr>
            <td align="left" nowrap="true">
                <b><u>DNS Record</u></b>&nbsp;
            </td>
            <td class="padding" align="left" width="100%">
                &nbsp;
            </td>
        </tr>
    </table>

    <!-- RECORD TYPE CONTAINER -->
    <div id="type_container" style="{$editdisplay};">
        <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']};padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">
            <tr>
                <td align="right" nowrap="true">
                    DNS record type
                </td>
                <td class="padding" align="left" width="100%">
                    <select
                        id="record_type_select"
                        name="set_type"
                        alt="Record type"
                        class="edit"
                        onChange="var selectBox = el('record_type_select');
                                el('a_container').style.display      = (selectBox.value == 'A') ? '' : 'none';
                                el('nosup_container').style.display  = (selectBox.value == 'TXT') ? '' : 'none';
                                el('cname_container').style.display  = (selectBox.value == 'CNAME') ? '' : 'none';
                                el('nosup_container').style.display  = (selectBox.value == 'NS') ? '' : 'none';
                                el('nosup_container').style.display  = (selectBox.value == 'MX') ? '' : 'none';
                                el('nosup_container').style.display  = (selectBox.value == 'SRV') ? '' : 'none';"
                    >{$record_type_list}</select>
                </td>
            </tr>

        </table>
    </div>

    <!-- A RECORD CONTAINER -->
    <div id="a_container">
        <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">
            <tr>
                <td align="right" nowrap="true">
                    DNS Name
                </td>
                <td class="padding" align="left" width="100%">
                    <input
                        id="set_hostname"
                        name="set_host"
                        alt="Hostname"
                        value="{$dns_record['name']}"
                        class="edit"
                        type="text"
                        size="25" maxlength="64"
                        onkeyup="el('a_info_{$window_name}').innerHTML = el('set_hostname').value + '.' + el('set_domain_{$window_name}').value +' IN ' + el('record_type_select').value + ' ' + el('set_ttl').value + ' ' + el('set_ip_{$window_name}').value;"
                    >
                </td>
            </tr>

            <tr>
                <td align="right" nowrap="true">
                    Domain
                </td>
                <td class="padding" align="left" width="100%">
                    <input
                        id="set_domain_{$window_name}"
                        name="set_domain"
                        alt="Domain name"
                        value="{$dns_record['domain_fqdn']}"
                        class="edit"
                        type="text"
                        size="25" maxlength="64"
                        onchange="el('a_info_{$window_name}').innerHTML = el('set_hostname').value + '.' + el('set_domain_{$window_name}').value +' IN ' + el('record_type_select').value + ' ' + el('set_ttl').value + ' ' + el('set_ip_{$window_name}').value;"
                    >
                    <div id="suggest_set_domain_{$window_name}" class="suggest"></div>
                </td>
            </tr>
<!--
            <tr>
                <td align="right" nowrap="true">
                    Subnet
                </td>
                <td class="padding" align="left" width="100%" nowrap="true">
                    <span id="associated_subnet_{$window_name}"
                    >{$subnet['name']}</span>
                </td>
            </tr>-->

            <tr>
                <td align="right" nowrap="true">
                    IP Address
                </td>
                <td class="padding" align="left" width="100%" nowrap="true">
                    <input
                        id="set_ip_{$window_name}"
                        name="set_ip"
                        alt="IP Address"
                        value="{$interface['ip_addr']}"
                        class="edit"
                        type="text"
                        size="25" maxlength="64"
                        onkeyup="el('a_info_{$window_name}').innerHTML = el('set_hostname').value + '.' + el('set_domain_{$window_name}').value +' IN ' + el('record_type_select').value + ' ' + el('set_ttl').value + ' ' + el('set_ip_{$window_name}').value;"
                    >
                </td>
            </tr>

            <tr>
                <td align="right" nowrap="true">
                    TTL
                </td>
                <td class="padding" align="left" width="100%">
                    <input
                        id="set_ttl"
                        name="set_ttl"
                        alt="TTL"
                        value="{$dns_record['ttl']}"
                        class="edit"
                        type="text"
                        size="20" maxlength="20"
                        onkeyup="el('a_info_{$window_name}').innerHTML = el('set_hostname').value + '.' + el('set_domain_{$window_name}').value +' IN ' + el('record_type_select').value + ' ' + el('set_ttl').value + ' ' + el('set_ip_{$window_name}').value;"
                   >
                </td>
            </tr>
            <tr>
                <td colspan="2" class="padding" align="center" width="100%" nowrap="true">
                <span id="a_info_{$window_name}" style="color: green;"></span>
                </td>
            </tr>
        </table>
    </div>


    <!-- CNAME CONTAINER -->
    <div id="cname_container" style="display:none;">
        <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">
            <tr>
                <td align="right" nowrap="true">
                    Existing A record
                </td>
                <td class="padding" align="left" width="100%">
                    <input
                        id="set_pointsto_{$window_name}"
                        name="set_pointsto"
                        alt="Points to existing A record"
                        value="{$dns_record['dns_id']}"
                        class="edit"
                        type="text"
                        size="25" maxlength="64"
                    >
                    <div id="suggest_set_pointsto_{$window_name}" class="suggest"></div>
                </td>
            </tr>
        </table>
    </div>

    <!-- NO SUPPORT CONTAINER -->
    <div id="nosup_container" style="display:none;">
        <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">
            <tr>
                <td align="right" nowrap="true">
                    &nbsp;
                </td>
                <td class="padding" align="left" width="100%">
                    <pre>Sorry, this record type not yet supported</pre>
                </td>
            </tr>
        </table>
    </div>

    <table cellspacing="0" border="0" cellpadding="0" width="100%" style="background-color: {$color['window_content_bg']}; padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">

EOL;


    if (!$dns_record['id']) {
        $window['html'] .= <<<EOL
        <tr>
            <td align="right" nowrap="true">
                &nbsp;
            </td>
            <td class="padding" align="left" width="100%" nowrap="true">
                <input
                    name="keepadding"
                    alt="Keep adding more DNS records"
                    type="checkbox"
                > Keep adding more DNS records
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
            <td colspan="2" class="padding" align="right" width="100%">
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
        $form['set_domain'] == '' or
        $form['set_type'] == '' or
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
    // Validate domain is valid
    list($status, $rows, $domain) = ona_get_domain_record(array('name' => $form['set_domain']));
    if ($status or !$rows) {
        $response->addScript("alert('Invalid domain!');");
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
        $form['host'] = $form['set_host'] . '.' . $form['set_domain'];
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
