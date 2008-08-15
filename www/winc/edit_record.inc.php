<?



//////////////////////////////////////////////////////////////////////////////
// Function: ws_editor($window_name, $form)
//
// Description:
//     Displays a form for creating/editing a DNS record.
//
// Input:
//     $window_name the name of the "window" to use.
//     $form  A string-based-array or an array or a dns record ID.
//            The string-based-array would usually look something like this:
//              dns_record_id=>123,js=>some('javascript');
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
    $window['js'] = '';
    $typedisable = '';

    // Check permissions
    if (! (auth('dns_record_modify') and auth('dns_record_add')) ) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }

    // If an array in a string was provided, build the array and store it in $form
    $form = parse_options_string($form);

    // Load an existing DNS record (and associated info) if $form is a dns_record_id
    $host = array('fqdn' => '.');
    $interface = array();
    if (is_numeric($form['dns_record_id'])) {
        list($status, $rows, $dns_record) = ona_get_dns_record(array('id' => $form['dns_record_id']));
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

    // Load a interface record if we got passed a interface_id
    if ($form['interface_id']) {
        list($status, $rows, $int) = ona_get_interface_record(array('id' => $form['interface_id']));
        $int['ip_addr_text'] = ip_mangle($int['ip_addr'], 'dotted');
        $window['js'] .= "el('set_ip_{$window_name}').value = '{$int['ip_addr_text']}'";
        $form['js'] = "xajax_window_submit('work_space', 'xajax_window_submit(\'display_host\', \'host=>{$int['host_id']}\', \'display\')');";
    }

    // Set up the types of records we can edit with this form
    //$record_types = array('A','CNAME','TXT','NS','MX','AAAA','SRV');
    // FIXME: MP cool idea here-- support the loc record and have a google map popup to search for the location then have it populate the coords from that.
    // FIXME: MP it would probably be much better to use ajax to pull back the right form content than all this other javascript crap.
    $record_types = array('A','CNAME','MX','NS','SRV','TXT');
    foreach (array_keys((array)$record_types) as $id) {
        $record_types[$id] = htmlentities($record_types[$id]);
        $selected = '';
        if ($record_types[$id] == $dns_record['type']) { $selected = 'SELECTED'; }
        $record_type_list .= "<option value=\"{$record_types[$id]}\" {$selected}>{$record_types[$id]}</option>\n";
    }


    // Escape data for display in html
    foreach(array_keys((array)$dns_record) as $key) { $dns_record[$key] = htmlentities($dns_record[$key], ENT_QUOTES); }
    foreach(array_keys((array)$interface) as $key) { $interface[$key] = htmlentities($interface[$key], ENT_QUOTES); }


    // If its a CNAME, get the dns name for the A record it points to
    if ($dns_record['type'] == 'CNAME' or $dns_record['type'] == 'MX' or $dns_record['type'] == 'NS' or $dns_record['type'] == 'SRV') {
        list($status, $rows, $existinga_data) = ona_get_dns_record(array('id' => $dns_record['dns_id']));
        $dns_record['existinga_data'] = $existinga_data['fqdn'];
    }


    // If its an A record,check to se if it has a PTR associated with it
    //FIXME: MP dont forget that if you change the ip of an A record that you must also update any PTR records reference to that interface
    $ptr_readonly = '';
    if ($dns_record['type'] == 'A') {
        list($status, $rows, $hasptr) = ona_get_dns_record(array('interface_id' => $dns_record['interface_id'],'type' => 'PTR'));
        if ($rows) {
            $hasptr_msg = '<- Already has PTR record';
            $ptr_readonly = 'disabled="1"';
        }
    }

    $ttl_style = '';
    $editdisplay = '';

    // Set the window title:
    if ($dns_record['id']) {
        $typedisable = 'disabled="1"';
        $window['title'] = "Edit DNS Record";
        $window['js'] .= "el('record_type_select').onchange('fake event');updatednsinfo('{$window_name}');";
        // If you are editing and there is no ttl set, use the one from the domain.
        if (!$dns_record['ttl']) {
            $ttl_style = 'style="font-style: italic;" title="Using TTL from domain"';
        }
    } else {
        $window['title'] = "Add DNS Record";
        $dns_record['srv_pri'] = 0;
        $dns_record['srv_weight'] = 0;
    }

    // Javascript to run after the window is built
    $window['js'] .= <<<EOL
        /* Put a minimize icon in the title bar */
        el('{$window_name}_title_r').innerHTML =
            '&nbsp;<a onClick="toggle_window(\'{$window_name}\');" title="Minimize window" style="cursor: pointer;"><img src="{$images}/icon_minimize.gif" border="0" /></a>' +
            el('{$window_name}_title_r').innerHTML;

        /* Put a help icon in the title bar */
        el('{$window_name}_title_r').innerHTML =
            '&nbsp;<a href="{$_ENV['help_url']}{$window_name}" target="null" title="Help" style="cursor: pointer;"><img src="{$images}/silk/help.png" border="0" /></a>' +
            el('{$window_name}_title_r').innerHTML;

        suggest_setup('set_domain_{$window_name}',    'suggest_set_domain_{$window_name}');
        suggest_setup('set_a_record_{$window_name}',  'suggest_set_a_record_{$window_name}');
EOL;

    // Define the window's inner html
    $window['html'] = <<<EOL

    <!-- DNS Record Edit Form -->
    <form id="{$window_name}_edit_form" onSubmit="return false;">
    <input type="hidden" name="dns_id" value="{$dns_record['id']}">
    <input type="hidden" name="name" value="{$host['fqdn']}">
    <input type="hidden" name="js" value="{$form['js']}">
EOL;

    // If we are editing and thus disabling the type selector, we need to put a hidden input field
    if ($typedisable) {
        $window['html'] .= "<input type=\"hidden\" name=\"set_type\" value=\"{$dns_record['type']}\">";
    }

    $window['html'] .= <<<EOL
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
    <div id="type_container">
        <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']};padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">
            <tr>
                <td align="right" nowrap="true">
                    DNS record type
                </td>
                <td class="padding" align="left" width="100%">
                    <select {$typedisable}
                        id="record_type_select"
                        name="set_type"
                        alt="Record type"
                        class="edit"
                        onchange="var selectBox = el('record_type_select');
                                el('info_{$window_name}').innerHTML = '';
                                el('ptr_info_{$window_name}').innerHTML = '';
                                el('a_container').style.display     = (selectBox.value == 'A') ? '' : 'none';
                                el('autoptr_container').style.display   = (selectBox.value == 'A') ? '' : 'none';
                                el('mx_container').style.display   = (selectBox.value == 'MX') ? '' : 'none';
                                el('srv_container').style.display   = (selectBox.value == 'SRV') ? '' : 'none';
                                el('txt_container').style.display   = (selectBox.value == 'TXT') ? '' : 'none';
                                el('name_container').style.display     = (selectBox.value == 'NS') ? 'none' : '';
                                el('existing_a_container').style.display = (selectBox.value == 'MX' || selectBox.value == 'CNAME' || selectBox.value == 'NS' || selectBox.value == 'SRV') ? '' : 'none';"
                    >{$record_type_list}</select>
                </td>
            </tr>

        </table>
    </div>

    <!-- COMMON CONTAINER -->
    <div id="common_container" style="background-color: #F2F2F2;">
        <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">
            <tr id="name_container">
                <td align="right" nowrap="true">
                    Host Name
                </td>
                <td class="padding" align="left" width="100%">
                    <input
                        id="set_hostname_{$window_name}"
                        name="set_name"
                        alt="Hostname"
                        value="{$dns_record['name']}"
                        class="edit"
                        type="text"
                        size="25" maxlength="64"
                        onblur="updatednsinfo('{$window_name}');"
                    />
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
                        onblur="updatednsinfo('{$window_name}');"
                    />
                    <div id="suggest_set_domain_{$window_name}" class="suggest"></div>
                </td>
            </tr>
EOL;

    // If there is a ttl in the record then display it instead of the domain setting message
    $ttlrow_style = '';
    if ($dns_record['ttl'] == 0) {
        $ttlrow_style = 'style="display:none;"';
        $window['html'] .= <<<EOL

            <tr id="ttlrowdesc">
                <td align="right" nowrap="true">
                    TTL
                </td>
                <td class="padding" align="left" width="100%" nowrap="true">
                    &nbsp;Defaults to domain setting,<br>
                    <a onclick="el('ttlrowdesc').style.display = 'none';el('ttlrow').style.display = '';">click here to override</a>
                </td>
            </tr>
EOL;
    }

    $window['html'] .= <<<EOL
            <tr id="ttlrow" {$ttlrow_style}>
                <td align="right" nowrap="true">
                    TTL
                </td>
                <td class="padding" align="left" width="100%">
                    <input {$ttl_style}
                        id="set_ttl"
                        name="set_ttl"
                        alt="TTL"
                        value="{$dns_record['ttl']}"
                        class="edit"
                        type="text"
                        size="20" maxlength="20"
                        onblur="updatednsinfo('{$window_name}');"
                        onfocus="updatednsinfo('{$window_name}');"
                    />
                </td>
            </tr>

            <!-- A RECORD CONTAINER -->
            <tr id="a_container">
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
                        onblur="updatednsinfo('{$window_name}');"
                    />
                </td>
            </tr>

            <tr id="autoptr_container">
                <td align="right" nowrap="true">
                    Auto create PTR
                </td>
                <td class="padding" align="left" width="100%" nowrap>
                    <input
                        id="set_auto_ptr"
                        name="set_addptr"
                        alt="Automaticaly create PTR record"
                        type="checkbox"
                        checked="1"
                        {$ptr_readonly}
                        onchange="updatednsinfo('{$window_name}');"
                    />{$hasptr_msg}
                </td>
            </tr>

            <!-- TXT CONTAINER -->
            <tr id="txt_container" style="display:none;">
                <td align="right" nowrap="true">
                    TXT value
                </td>
                <td class="padding" align="left" width="100%">
                    <input
                        id="set_txt_{$window_name}"
                        name="set_txt"
                        alt="TXT value"
                        value="{$dns_record['txt']}"
                        class="edit"
                        type="text"
                        size="25" maxlength="55"
                        onblur="updatednsinfo('{$window_name}');"
                    />
                </td>
            </tr>

            <!-- MX CONTAINER -->
            <tr id="mx_container" style="display:none;">
                <td align="right" nowrap="true">
                    MX Preference
                </td>
                <td class="padding" align="left" width="100%">
                    <input
                        id="set_mx_preference_{$window_name}"
                        name="set_mx_preference"
                        alt="MX preference"
                        value="{$dns_record['mx_preference']}"
                        class="edit"
                        type="text"
                        size="5" maxlength="5"
                        onblur="updatednsinfo('{$window_name}');"
                    />
                </td>
            </tr>

            <!-- SRV CONTAINER -->
            <tr id="srv_container" style="display:none;">
                <td align="right" nowrap="true">
                    Priority<br>Weight<br>Port
                </td>
                <td class="padding" align="left" width="100%">
                    <input
                        style="margin-bottom:3px;"
                        id="set_srv_pri_{$window_name}"
                        name="set_srv_pri"
                        alt="SRV Priority"
                        value="{$dns_record['srv_pri']}"
                        class="edit"
                        type="text"
                        size="5" maxlength="5"
                        onblur="updatednsinfo('{$window_name}');"
                    /><br />
                    <input
                        style="margin-bottom:3px;"
                        id="set_srv_weight_{$window_name}"
                        name="set_srv_weight"
                        alt="SRV Weight"
                        value="{$dns_record['srv_weight']}"
                        class="edit"
                        type="text"
                        size="5" maxlength="5"
                        onblur="updatednsinfo('{$window_name}');"
                    /><br />
                    <input
                        id="set_srv_port_{$window_name}"
                        name="set_srv_port"
                        alt="SRV Port"
                        value="{$dns_record['srv_port']}"
                        class="edit"
                        type="text"
                        size="5" maxlength="5"
                        onblur="updatednsinfo('{$window_name}');"
                    />
                </td>
            </tr>

            <!-- CNAME CONTAINER -->
            <tr id="existing_a_container" style="display:none;">
                <td align="right" nowrap="true">
                    Existing A record
                </td>
                <td class="padding" align="left" width="100%">
                    <input
                        id="set_a_record_{$window_name}"
                        name="set_pointsto"
                        alt="Points to existing A record"
                        value="{$dns_record['existinga_data']}"
                        class="edit"
                        type="text"
                        size="25" maxlength="64"
                        onblur="updatednsinfo('{$window_name}');"
                    />
                    <div id="suggest_set_a_record_{$window_name}" class="suggest"></div>
                </td>
            </tr>

            <!-- NOTES CONTAINER -->
            <tr id="notes_container">
                <td align="right" nowrap="true">
                    Notes
                </td>
                <td class="padding" align="left" width="100%">
                    <input
                        id="set_notes_{$window_name}"
                        name="set_notes"
                        alt="Notes"
                        value="{$dns_record['notes']}"
                        class="edit"
                        type="text"
                        size="25" maxlength="64"
                    />
                </td>
            </tr>

            <!-- RECORD INFO -->
            <tr>
                <td colspan="2" class="padding" align="center" width="100%" nowrap="true">
                <span id="info_{$window_name}" style="color: green;font-family: monospace;"></span>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="padding" align="center" width="100%" nowrap="true">
                <span id="ptr_info_{$window_name}" style="color: green;font-family: monospace;"></span>
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
                    onfocus="updatednsinfo('{$window_name}');"
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
//     Creates/updates a dns record.
//////////////////////////////////////////////////////////////////////////////
function ws_save($window_name, $form='') {
    global $include, $conf, $self, $onadb;

    // Check permissions
    if (! (auth('dns_record_modify') and auth('dns_record_add')) ) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = '';



    // Validate input
//     if ($form['set_domain'] == '' or
//         $form['set_type'] == ''
//        ) {
//         $response->addScript("alert('Please complete all fields to continue!');");
//         return($response->getXML());
//     }

    // we need to do a little validation here to make sure things
    // have a good chance of working!

    // If the name we were passed has a leading . in it then remove the dot.
    $form['set_name'] = preg_replace("/^\./", '', $form['set_name']);

    // Validate the "set_name" name is valid
    if ($form['set_name'] and ($form['set_type'] != 'NS')) {
        $form['set_name'] = sanitize_hostname($form['set_name']);
        if (!$form['set_name']) {
            $response->addScript("alert('Invalid hostname!');");
            return($response->getXML());
        }
    }

    // Make sure the IP address specified is valid
    if ($form['set_name'] != '.' and $form['set_ip']) {
        $form['set_ip'] = ip_mangle($form['set_ip'], 'dotted');
        if ($form['set_ip'] == -1) {
            $response->addScript("alert('{$self['error']}');");
            return($response->getXML());
        }
    }



    // Decide if we're editing or adding
    $module = 'modify';
    // If we're adding, re-map some the array names to match what the "add" module wants
    if (!$form['dns_id']) {
        $module = 'add';

        // options
        $form['name'] = $form['set_name'] . '.' . $form['set_domain']; unset($form['set_name']); unset($form['set_domain']);
        $form['type'] = $form['set_type']; unset($form['set_type']);
        $form['notes'] = $form['set_notes']; unset($form['set_notes']);
        $form['ip'] = $form['set_ip']; unset($form['set_ip']);
        $form['ttl'] = $form['set_ttl']; unset($form['set_ttl']);
        $form['addptr'] = $form['set_addptr'];

        // if this is a cname. then set the pointsto option
        if ($form['type'] == 'CNAME' or $form['type'] == 'MX' or $form['type'] == 'NS' or $form['type'] == 'SRV') $form['pointsto'] = $form['set_pointsto'];
        if ($form['type'] == 'MX')  $form['mx_preference'] = $form['set_mx_preference'];
        if ($form['type'] == 'TXT') $form['txt'] = $form['set_txt'];

        if ($form['type'] == 'SRV') $form['srv_pri'] = $form['set_srv_pri'];
        if ($form['type'] == 'SRV') $form['srv_weight'] = $form['set_srv_weight'];
        if ($form['type'] == 'SRV') $form['srv_port'] = $form['set_srv_port'];

        // If it is an NS record, blank the name out
        //if ($form['type'] == 'NS') $form['name'] = $form['set_domain'];

        // If there's no "refresh" javascript, add a command to view the new dns record
        if (!preg_match('/\w/', $form['js'])) $form['js'] = "xajax_window_submit('work_space', 'xajax_window_submit(\'display_host\', \'host=>{$form['name']}\', \'display\')');";
    }
    else {
        $form['set_name'] .= '.' . $form['set_domain'];
        //FIXME: MP temporary kludge to get around not having a proper find_dns_record module.. ID is the only way to find a record now and it is done via the name field
        $form['name'] = $form['dns_id'];

        // if this is a cname. then set the pointsto option
        if ($form['set_type'] != 'CNAME') $form['set_pointsto'] == '';
    }

    // Run the module to ADD the DNS record, or MODIFY THE DNS record.
    list($status, $output) = run_module('dns_record_'.$module, $form);

    // If the module returned an error code display a popup warning
    if ($status)
        $js .= "alert('Save failed.\\n". preg_replace('/[\s\']+/', ' ', $self['error']) . "');";
    else {
        // if they have checked the keep adding records box then dont remove the window
        if (!$form['keepadding'])
            $js .= "removeElement('{$window_name}');";
        else {
            $js .= "el('statusinfo_{$window_name}').innerHTML = 'Previously added:<br>{$form['name']} Type: {$form['type']}';";
        }

        if ($form['js']) $js .= $form['js'];
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
//     Deletes a dns record.  $form should be an array with a 'dns_record_id'
//     key defined and optionally a 'js' key with javascript to have the
//     browser run after a successful delete.
//////////////////////////////////////////////////////////////////////////////
function ws_delete($window_name, $form='') {
    global $include, $conf, $self, $onadb, $onadb;

    // Check permissions
    if (!auth('dns_record_del')) {
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
    list($status, $output) = run_module('dns_record_del', array('name' => $form['dns_record_id'], 'commit' => $form['commit']));

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




//////////////////////////////////////////////////////////////////////////////
// Function:
//     Make primary dns function
//
// Description:
//     makes a dns record primary for the given host.  $form should be an array with a 'dns_record_id'
//     key defined and optionally a 'js' key with javascript to have the
//     browser run after a successful update.
//////////////////////////////////////////////////////////////////////////////
function ws_makeprimary($window_name, $form='') {
    global $include, $conf, $self, $onadb, $onadb;

    // Check permissions
    if (!auth('dns_record_modify')) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }

    // If an array in a string was provided, build the array and store it in $form
    $form = parse_options_string($form);

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = '';

    $SET = array();
    $SET['primary_dns_id'] = $form['dns_record_id'];

    // Do the actual update
    list($status, $rows) = db_update_record($onadb, 'hosts', array('id' => $form['host_id']), $SET);
    if ($status or !$rows) {
        $self['error'] = "ERROR => makeprimary() SQL Query failed to update host: " . $self['error'];
        printmsg($self['error'], 0);
        $js .= "alert('Makeprimary failed. " . preg_replace('/[\s\']+/', ' ', $self['error']) . "');";
    } else if ($form['js']) {
        // Hardcoding so that it always refreshes the display host page.
        $js .= "xajax_window_submit('work_space', 'xajax_window_submit(\'display_host\',\'host_id=>{$form['host_id']}\', \'display\')');";
    }

    // Return an XML response
    $response->addScript($js);
    return($response->getXML());
}


?>
