<?php



//////////////////////////////////////////////////////////////////////////////
// Function: ws_editor($window_name, $form)
//
// Description:
//     Displays a form for creating/editing an interface record.
//
// Input:
//     $window_name the name of the "window" to use.
//     $form  A string-based-array or an array or an interface ID.
//            The string-based-array would usually look something like this:
//              interface_id=>123,js=>some('javascript');
//            If $form is a valid record ID, it is used to display and edit
//            that record.  Otherwise the form will let you add a new record.
//            The "Save" button calls the ws_save() function in this file.
// Notes:
//     If there is a "js" field passed in that contains javascript it will be
//     sent to the browser after the ws_save() function is called.
//////////////////////////////////////////////////////////////////////////////
function ws_editor($window_name, $form='') {
    global $conf, $self, $onadb;
    global $font_family, $color, $style, $images, $interface;
    $window = array();

    // Check permissions
    if (!auth('interface_modify')) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }

    // If an array in a string was provided, build the array and store it in $form
    $form = parse_options_string($form);

    // If $form is a number, it's an alias record ID- so we transform $form into an array
    if (is_numeric($form)) {
        $form = array('interface_id' => $form);
    }

    // Load an existing record (and associated info) if we're editing
    if (is_numeric($form['interface_id'])) {
        list($status, $rows, $interface) = ona_get_interface_record(array('id' => $form['interface_id']));
        if ($rows) {
            list($status, $rows, $host) = ona_find_host($interface['host_id']);
            list($status, $rows, $subnet) = ona_get_subnet_record(array('id' => $interface['subnet_id']));
            $interface['ip_addr'] = ip_mangle($interface['ip_addr'], 'dotted');
            if ($interface['mac_addr']) {
                $interface['mac_addr'] = mac_mangle($interface['mac_addr']);
            }
        }
    }

    else {
        // Maybe we didn't get an interface record, but we got a host record (adding an interface)
        // Set it in $interface so it's available below.
        if (is_numeric($form['host_id'])) $interface['host_id'] = $form['host_id'];
        if (is_numeric($form['subnet_id'])) list($status, $rows, $subnet) = ona_get_subnet_record(array('id' => $form['subnet_id']));

        // allow various default items to be set during an add that are passed in from the form
        if (isset($form['ip_addr'])) $interface['ip_addr'] = $form['ip_addr'];
        if (isset($form['name'])) $interface['name'] = $form['name'];
        if (isset($form['description'])) $interface['description'] = $form['description'];
        if (isset($form['mac_addr'])) $interface['mac_addr'] = $form['mac_addr'];

    }

    // Load the host record for display
    if($interface['host_id']) list($status, $rows, $host) = ona_find_host($interface['host_id']);

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

    // Escape data for display in html
    foreach(array_keys((array)$interface) as $key) { $interface[$key] = htmlentities($interface[$key], ENT_QUOTES, $conf['php_charset']); }
    foreach(array_keys((array)$subnet) as $key) { $subnet[$key] = htmlentities($subnet[$key], ENT_QUOTES, $conf['php_charset']); }
    foreach(array_keys((array)$host) as $key) { $host[$key] = htmlentities($host[$key], ENT_QUOTES, $conf['php_charset']); }


    // Set the window title:
    $window['title'] = "Add Interface";
    if ($interface['id'])
        $window['title'] = "Edit Interface";

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
                         "text_id=>associated_subnet_{$window_name}," +
                         "text_value=>" + el('associated_subnet_{$window_name}').innerHTML + "," +
                         "input_id=>set_ip_{$window_name}');"
                );
            };

        suggest_setup('hostname',  'suggest_int_hostname');
        el('hostname').focus();
EOL;

    // Define the window's inner html
    $window['html'] = <<<EOL

    <!-- Interface Edit Form -->
    <form id="{$window_name}_edit_form" onSubmit="return false;">
    <input type="hidden" name="interface_id" value="{$interface['id']}">
EOL;
    if($host['fqdn']) {
        $window['html'] .= <<<EOL
       <input type="hidden" name="host" value="{$interface['host_id']}">
       <input type="hidden" name="hostfqdn" value="{$host['fqdn']}">
EOL;
    }

    $window['html'] .= <<<EOL
    <input type="hidden" name="js" value="{$form['js']}">
    <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">

        <!-- INTERFACE RECORD -->
        <tr>
            <td align="left" nowrap="true"><b><u>Interface Record</u></b>&nbsp;</td>
            <td class="padding" align="left" width="100%">&nbsp;</td>
        </tr>

        <tr>

EOL;
    if($host['fqdn']) {
        $window['html'] .= <<<EOL
            <td align="right" nowrap="true">
                Host:
            </td>
            <td class="padding" align="left" width="100%">
                {$host['fqdn']}&nbsp;
            </td>
EOL;
    }
    else {
        $window['html'] .= <<<EOL
            <td class="input_required" align="right" nowrap="true">
                Existing Host
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    id="hostname"
                    name="host"
                    alt="Hostname"
                    value="{$host['name']}"
                    class="edit"
                    type="text"
                    size="20" maxlength="64"
                >
                <div id="suggest_int_hostname" class="suggest"></div>
            </td>
EOL;
    }
    $window['html'] .= <<<EOL
        </tr>

        <tr>
            <td align="right" nowrap="true">
                Subnet:
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
            <td class="padding" align="left" width="100%" nowrap="true">
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
            <td nowrap class="padding" align="left" width="100%">
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

// Show a "keep adding" checkbox if they are adding records
if (!isset($interface['id'])) {

    // Print a dns view selector
    if ($conf['dns_views']) {
      $window['html'] .= <<<EOL
        <tr>
            <td align="right" nowrap="true">
                DNS View for PTR
            </td>
            <td class="padding" align="left" width="100%">
                <select
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
            <td nowrap class="padding" align="left" width="100%">
                <input
                    name="keepadding"
                    alt="Keep adding more interfaces"
                    type="checkbox"
                > Keep adding more interfaces
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
//     Creates/updates an interface record.
//////////////////////////////////////////////////////////////////////////////
function ws_save($window_name, $form='') {
    global $include, $conf, $self, $onadb;

    // Check permissions (there is no interface_add, it's merged with host_add)
    if (! (auth('interface_modify') and auth('host_add')) ) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }
    // If an array in a string was provided, build the array and store it in $form
    $form = parse_options_string($form);
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = '';

    // Validate input
    if ($form['set_ip'] == '') {
        $response->addScript("alert('Please complete the IP address field to continue!');");
        return($response->getXML());
    }
    // set_create_a and set_create_ptr should both be set!
    if (!$form['set_addptr']) $form['set_addptr'] = 'N';


    // Decide if we're editing or adding
    $module = 'interface_modify';
    // If we're adding, re-map some the array names to match what the "add" module wants
    if (!$form['interface_id']) {
        $module = 'interface_add';
        $form['ip'] = $form['set_ip']; unset($form['set_ip']);
        $form['mac'] = $form['set_mac']; unset($form['set_mac']);
        $form['name'] = $form['set_name']; unset($form['set_name']);
        $form['description'] = $form['set_description']; unset($form['set_description']);
        $form['addptr'] = $form['set_addptr']; unset($form['set_addptr']);
        $form['view'] = $form['set_view']; unset($form['set_view']);
    }
    else {
        $form['interface'] = $form['interface_id']; unset($form['interface_id']);
    }

    // Do a pre check of the ptr domain so we can prompt the user properly
    if ($module == 'interface_add') {
        $ipflip = ip_mangle($form['ip'],'flip');
        $octets = explode(".",$ipflip);
        list($status, $rows, $ptrdomain) = ona_find_domain($ipflip.".in-addr.arpa");
        if (!$ptrdomain['id']) {
            printmsg("ERROR => This operation tried to create a PTR record that is the first in the {$octets[3]}.0.0.0 class A range.  You must first create at least the following DNS domain: {$octets[3]}.in-addr.arpa",3);
            $self['error'] = "ERROR => This operation tried to create a PTR record that is the first in the {$octets[3]}.0.0.0 class A range.  You must first create at least the following DNS domain: {$octets[3]}.in-addr.arpa.  You could also create domains for class B or class C level reverse zones.  Click OK to open add domain dialog";
            $response->addScript("alert('{$self['error']}');xajax_window_submit('edit_domain', 'newptrdomainname=>{$octets[3]}.in-addr.arpa', 'editor');");
            return($response->getXML());
        }
    }

    // Run the module
    list($status, $output) = run_module($module, $form);

    // If the module returned an error code display a popup warning
    if ($status)
        $js .= "alert('Save failed.\\n". preg_replace('/[\s\']+/', ' ', $self['error']) . "');";
    else {
        // If the module returned an error code display a popup warning
        if ($status and $module == 'modify' and $form['set_ip'])
            $js .= "alert('Interface update failed.\\n". preg_replace('/[\s\']+/', ' ', $self['error']) . "');";
        else {
            // Update the status to tell them what they just did if they just *added* a subnet and the "keep adding" box is checked.
            // Otherwise just close the edit window.
            if ($form['keepadding'] and $module == 'interface_add')
                $js .= "el('statusinfo_{$window_name}').innerHTML = 'Previously added: {$form['ip']}';";
            else
                $js .= "removeElement('{$window_name}');";

            // If there is "refresh" javascript, send it to the browser to execute
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
//     Deletes a host record.  $form should be an array with a 'interface_id'
//     key defined and optionally a 'js' key with javascript to have the
//     browser run after a successful delete.
//////////////////////////////////////////////////////////////////////////////
function ws_delete($window_name, $form='') {
    global $include, $conf, $self, $onadb;

    // Check permissions
    if (!auth('interface_del')) {
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
    list($status, $output) = run_module('interface_del', array('interface' => $form['interface_id'], 'commit' => $form['commit']));

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