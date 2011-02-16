<?php



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
        $interface['ip_addr'] = $int['ip_addr_text'];
        $window['js'] .= "el('set_ip_{$window_name}').value = '{$int['ip_addr_text']}'";
        $form['js'] = "xajax_window_submit('work_space', 'xajax_window_submit(\'display_host\', \'host=>{$int['host_id']}\', \'display\')');";
    }


    // Escape data for display in html
    foreach(array_keys((array)$dns_record) as $key) { $dns_record[$key] = htmlentities($dns_record[$key], ENT_QUOTES, $conf['php_charset']); }
    foreach(array_keys((array)$interface) as $key) { $interface[$key] = htmlentities($interface[$key], ENT_QUOTES, $conf['php_charset']); }


    // If its a CNAME, get the dns name for the A record it points to
    if ($dns_record['type'] == 'CNAME' or $dns_record['type'] == 'MX' or $dns_record['type'] == 'PTR' or $dns_record['type'] == 'NS' or $dns_record['type'] == 'SRV') {
        list($status, $rows, $existinga_data) = ona_get_dns_record(array('id' => $dns_record['dns_id']));
        $dns_record['existinga_data'] = $existinga_data['fqdn'];
    }

    // If its a PTR we need to build the hostname part
    if ($dns_record['type'] == 'PTR') {

        // Flip the IP address
        $dns_record['name'] = ip_mangle($interface['ip_addr'],'flip');

        // strip down the IP to just the "host" part as it relates to the domain its in
        $domain_part = preg_replace("/.in-addr.arpa$/", '', $dns_record['domain_fqdn']);
        $dns_record['name'] = preg_replace("/.$domain_part$/", '', $dns_record['name']);

        // Disable the edit boxes related to the A record info
        $window['js'] .= "el('set_hostname_{$window_name}').disabled='1';el('set_domain_{$window_name}').disabled='1';el('set_a_record_{$window_name}').disabled='1';el('set_ip_{$window_name}').disabled='1';";
    }


    // If its an A record,check to se if it has a PTR associated with it
    //FIXME: MP dont forget that if you change the ip of an A record that you must also update any PTR records reference to that interface
    $ptr_readonly = '';
    if ($dns_record['type'] == 'A' or $dns_record['type'] == 'AAAA') {
        list($status, $rows, $hasptr) = ona_get_dns_record(array('interface_id' => $dns_record['interface_id'],'type' => 'PTR'));
        if ($rows) {
            $hasptr_msg = '<- Already has PTR record';
            $ptr_readonly = 'disabled="1"';
        }
    }

    $ttl_style = '';
    $editdisplay = '';
    $record_types = array();

    // Set the window title:
    if ($dns_record['id']) {
        $typedisable = 'disabled="1"';
        if ($dns_record['dns_id']) $viewdisable = 'disabled="1"';
        $auto_ptr_checked = '';
        $window['title'] = "Edit DNS Record";
        $window['js'] .= "el('record_type_select').onchange('fake event');updatednsinfo('{$window_name}');el('set_hostname_{$window_name}').focus();";
        // If you are editing and there is no ttl set, use the one from the domain.
        if (!$dns_record['ttl']) {
            $ttl_style = 'style="font-style: italic;" title="Using TTL from domain"';
        }
        // add PTR type as an editable option to the record_types array
        array_push($record_types, "PTR");
        // if we are passing in default values for a record, set them here from form data.
        if (strlen($form['ip_addr']) > 1) $interface['ip_addr'] = ip_mangle($form['ip_addr'], 'dotted');
        if (strlen($form['hostname']) > 1) $dns_record['name'] = $form['hostname'];
    } else {
        $auto_ptr_checked = 'checked="1"';
        $window['title'] = "Add DNS Record";
        $dns_record['srv_pri'] = 0;
        $dns_record['srv_weight'] = 0;
        $dns_record['ebegin']=date('Y-m-j G:i:s',time());
        $window['js'] .= "el('record_type_select').onchange('fake event');updatednsinfo('{$window_name}');el('set_hostname_{$window_name}').focus();";
        // if we are passing in default values for a new record, set them here from form data.
        if (strlen($form['ip_addr']) > 1) $interface['ip_addr'] = ip_mangle($form['ip_addr'], 'dotted');
        if (strlen($form['hostname']) > 1) $dns_record['name'] = $form['hostname'];
    }

    // Set up the types of records we can edit with this form
    //$record_types = array('A','CNAME','TXT','NS','MX','AAAA','SRV');
    // FIXME: MP cool idea here-- support the loc record and have a google map popup to search for the location then have it populate the coords from that.
    // FIXME: MP it would probably be much better to use ajax to pull back the right form content than all this other javascript crap.
    array_push($record_types,'A','AAAA','CNAME','MX','NS','SRV','TXT','PTR');
    foreach (array_keys((array)$record_types) as $id) {
        $record_types[$id] = htmlentities($record_types[$id]);
        $selected = '';
        if ($record_types[$id] == $dns_record['type']) { $selected = 'SELECTED'; }
        $record_type_list .= "<option value=\"{$record_types[$id]}\" {$selected}>{$record_types[$id]}</option>\n";
    }


    //Get the list of DNS views
    if ($conf['dns_views']) {
        list($status, $rows, $dnsviews) = db_get_records($onadb, 'dns_views','id >= 0', 'name');

        foreach ($dnsviews as $entry) {
            $selected = '';
            $dnsviews['name'] = htmlentities($dnsviews['name']);
            // If this entry matches the record you are editing, set it to selected
            if ($dns_record['id'] and $entry['id'] == $dns_record['dns_view_id']) {
                $selected = "SELECTED=\"selected\"";
            } elseif (!$dns_record['id'] and $entry['id'] == 0) {
                // Otherwise use the default record if we are adding a new entry
                $selected = "SELECTED=\"selected\"";
            }
            $dns_view_list .= "<option {$selected} value=\"{$entry['id']}\">{$entry['name']}</option>\n";
        }
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

        el('set_hostname_{$window_name}').focus();
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

    // If we are editing and thus disabling the view selector, we need to put a hidden input field
    if ($viewdisable) {
        $window['html'] .= "<input type=\"hidden\" name=\"set_view\" value=\"{$dns_record['dns_view_id']}\">";
    }


    $window['html'] .= <<<EOL
    <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']};padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;" width="100%">
        <!-- DNS RECORD -->
        <tr>
            <td align="right" nowrap="true">
                <b><u>DNS Record</u></b>&nbsp;
            </td>
            <td class="padding" align="left" width="100%">
                &nbsp;
            </td>
        </tr>

    </table>

    <!-- RECORD TYPE CONTAINER -->
    <div id="type_container">
        <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']};padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;" width="100%">
EOL;

    // Print a dns view selector
    if ($conf['dns_views']) {
      $window['html'] .= <<<EOL
        <tr>
            <td align="right" nowrap="true">
                DNS View
            </td>
            <td class="padding" align="left" width="100%">
                <select {$viewdisable}
                    id="dns_view_select"
                    name="set_view"
                    alt="DNS View"
                    class="edit"
                >{$dns_view_list}</select>
            </td>
        </tr>

EOL;
    }

    $window['html'] .= <<<EOL
            <tr>
                <td class="input_required" align="right" nowrap="true">
                    DNS Record Type
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
                                el('a_container').style.display     = (selectBox.value == 'AAAA' || selectBox.value == 'A' || selectBox.value == 'PTR') ? '' : 'none';
                                el('autoptr_container').style.display   = (selectBox.value == 'AAAA' || selectBox.value == 'A') ? '' : 'none';
                                el('mx_container').style.display   = (selectBox.value == 'MX') ? '' : 'none';
                                el('srv_container').style.display   = (selectBox.value == 'SRV') ? '' : 'none';
                                el('txt_container').style.display   = (selectBox.value == 'TXT') ? '' : 'none';
                                el('name_container').style.display     = (selectBox.value == 'NS' || selectBox.value == 'PTR') ? 'none' : '';
                                el('domain_name_container').style.display  = (selectBox.value == 'PTR') ? 'none' : '';
                                el('existing_a_container').style.display = (selectBox.value == 'MX' || selectBox.value == 'PTR'|| selectBox.value == 'CNAME' || selectBox.value == 'NS' || selectBox.value == 'SRV') ? '' : 'none';"
                    >{$record_type_list}</select>
                </td>
            </tr>

        </table>
    </div>

    <!-- COMMON CONTAINER -->
    <div id="common_container" style="background-color: #F2F2F2;">
        <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">
            <tr id="name_container">
                <td class="input_required" align="right" nowrap="true">
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

            <tr id="domain_name_container">
                <td class="input_required" align="right" nowrap="true">
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
                    &nbsp;Defaults to domain setting,&nbsp;
                    <a onclick="el('ttlrowdesc').style.display = 'none';el('ttlrow').style.display = '';">override</a>
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

            <tr id="ebeginrow">
                <td align="right" nowrap="true">
                    Begin
                </td>
                <td class="padding" align="left" width="100%">
EOL;

    if ((strtotime($dns_record['ebegin']) < time()) && (strtotime($dns_record['ebegin']) > 1)) {
        $window['html'] .= <<<EOL
                    <input id="ebegin_input" style="display:none;"
                        id="set_ebegin"
                        name="set_ebegin"
                        alt="Set a future begin time"
                        value="{$dns_record['ebegin']}"
                        class="edit"
                        type="text"
                        size="16" maxlength="30"
                    />
                    <img
                        id="ebegin_clock"
                        style="margin-top: -6px;"
                        src='{$images}/silk/clock.png'
                        border='0'
                        title="Set a future begin time"
                        onclick="el('ebegin_clock').style.display = 'none';el('ebegin_input').style.display = '';"
                    > -or-
                    <input
                        name="disable"
                        alt="Disable this DNS entry"
                        type="checkbox"
                    > Disable
EOL;
    } else {
        $ebegin_clockstyle = '';
        $ebegin_style = 'style="display:none;"';
        // If record is disabled, then check the box and hide the input box.  Also set up a fake ebegin for now() in case they re-enable
        if (strtotime($dns_record['ebegin']) < 0) {
            $ebegin_disabled = 'checked="1"';
            $dns_record['ebegin']=date('Y-m-j G:i:s',time());
        }
        if (strtotime($dns_record['ebegin']) > time()) {
            $ebegin_clockstyle = 'display:none;';
            $ebegin_style = '';
        }
        $window['html'] .= <<<EOL
                    <input id="ebegin_input"
                        {$ebegin_style}
                        id="set_ebegin"
                        name="set_ebegin"
                        alt="TTL"
                        value="{$dns_record['ebegin']}"
                        class="edit"
                        type="text"
                        size="16" maxlength="30"
                    />
                    <img 
                        id="ebegin_clock"
                        style="margin-top: -6px;{$ebegin_clockstyle}"
                        src='{$images}/silk/clock.png'
                        border='0' 
                        title="Set a future begin time"
                        onclick="el('ebegin_clock').style.display = 'none';el('ebegin_input').style.display = '';"
                    > -or-
                    <input
                        name="disable"
                        alt="Disable this DNS entry"
                        type="checkbox"
                        {$ebegin_disabled}
                    > Disable
EOL;
    }

    $window['html'] .= <<<EOL
                </td>
            </tr>

            <!-- A RECORD CONTAINER -->
            <tr id="a_container">
                <td class="input_required" align="right" nowrap="true">
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
                    Create PTR
                </td>
                <td class="padding" align="left" width="100%" nowrap>
                    <input
                        id="set_auto_ptr"
                        name="set_addptr"
                        alt="Automaticaly create PTR record"
                        type="checkbox"
                        {$auto_ptr_checked}
                        {$ptr_readonly}
                        onchange="updatednsinfo('{$window_name}');"
                    />{$hasptr_msg}
                </td>
            </tr>

            <!-- TXT CONTAINER -->
            <tr id="txt_container" style="display:none;">
                <td class="input_required" align="right" nowrap="true">
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
                        size="25" maxlength="255"
                        onblur="updatednsinfo('{$window_name}');"
                    />
                </td>
            </tr>

            <!-- MX CONTAINER -->
            <tr id="mx_container" style="display:none;">
                <td class="input_required" align="right" nowrap="true">
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
                <td class="input_required" align="right" nowrap="true">
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
                <td class="input_required" align="right" nowrap="true">
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
    $form['set_name'] = preg_replace("/^\./", '', trim($form['set_name']));
    $form['set_ip'] = trim($form['set_ip']);

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

    $form['set_addptr'] = sanitize_YN($form['set_addptr'], 'N');

    // Set the effective date to 0 to disable
    if ($form['disable']) $form['set_ebegin'] = 0;

    // Decide if we're editing or adding
    $module = 'modify';
    // If we're adding, re-map some the array names to match what the "add" module wants
    if (!$form['dns_id']) {
        $module = 'add';

        // options
        $form['domain'] = $form['set_domain'];
        $form['name'] = $form['set_name'] . '.' . $form['set_domain']; unset($form['set_name']); unset($form['set_domain']);
        $form['type'] = $form['set_type']; unset($form['set_type']);
        $form['ebegin'] = $form['set_ebegin']; unset($form['set_ebegin']);
        $form['notes'] = $form['set_notes']; unset($form['set_notes']);
        $form['ip'] = $form['set_ip']; unset($form['set_ip']);
        $form['ttl'] = $form['set_ttl']; unset($form['set_ttl']);
        $form['addptr'] = $form['set_addptr']; unset($form['set_addptr']);
        $form['view'] = $form['set_view']; unset($form['set_view']);

        // if this is a cname. then set the pointsto option
        if ($form['type'] == 'CNAME' or $form['type'] == 'MX' or $form['type'] == 'NS' or $form['type'] == 'SRV') $form['pointsto'] = $form['set_pointsto'];
        if ($form['type'] == 'MX')  $form['mx_preference'] = $form['set_mx_preference'];
        if ($form['type'] == 'TXT') $form['txt'] = $form['set_txt'];

        if ($form['type'] == 'SRV') $form['srv_pri'] = $form['set_srv_pri'];
        if ($form['type'] == 'SRV') $form['srv_weight'] = $form['set_srv_weight'];
        if ($form['type'] == 'SRV') $form['srv_port'] = $form['set_srv_port'];

        // If it is an NS record, blank the name out
        //if ($form['type'] == 'NS') $form['name'] = $form['set_domain'];

        // If we are adding a PTR.. switch existing a record to name
        if ($form['type'] == 'PTR') $form['name'] = $form['set_pointsto'];

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
    global $include, $conf, $self, $onadb;

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






//////////////////////////////////////////////////////////////////////////////
// Function:
//     Make primary dns function
//
// Description:
//     makes a dns record primary for the given host.  $form should be an array with a 'dns_record_id'
//     key defined and optionally a 'js' key with javascript to have the
//     browser run after a successful update.
//////////////////////////////////////////////////////////////////////////////
function ws_enablerecord($window_name, $form='') {
    global $include, $conf, $self, $onadb;

    // Check permissions
    if (!auth('dns_record_modify')) {
        $response = new xajaxResponse();
        $response->addScript("alert('Failed to enable record: Permission denied!');");
        return($response->getXML());
    }

    // If an array in a string was provided, build the array and store it in $form
    $form = parse_options_string($form);

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = '';

    $SET = array();
    $SET['ebegin'] = date('Y-m-j G:i:s',time());

    // Do the actual update
    list($status, $rows) = db_update_record($onadb, 'dns', array('id' => $form['dns_record_id']), $SET);
    if ($status or !$rows) {
        $self['error'] = "ERROR => enablerecord() SQL Query failed to update dnsrecord: " . $self['error'];
        printmsg($self['error'], 0);
        $js .= "alert('Enable DNS record failed. " . preg_replace('/[\s\']+/', ' ', $self['error']) . "');";
    } else if ($form['js']) {
        // Hardcoding so that it always refreshes the display host page.
        //$js .= "xajax_window_submit('work_space', 'xajax_window_submit(\'{$window_name}\',\'host_id=>{$form['host_id']}\', \'display\')');";
        $js .= $form['js'];
    }

    // Return an XML response
    $response->addScript($js);
    return($response->getXML());
}




?>
