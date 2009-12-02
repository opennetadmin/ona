<?php



//////////////////////////////////////////////////////////////////////////////
// Function: ws_editor()
//
// Description:
//     Displays a form for creating/editing a subnet record.
//     If $form is a valid subnet_id, it is used to display an existing
//     record for editing.  "Save" button calls the ws_save() function.
//////////////////////////////////////////////////////////////////////////////
function ws_editor($window_name, $form='') {
    global $conf, $self, $onadb;
    global $font_family, $color, $style, $images;
    $window = array();

    // Check permissions
    if (! (auth('subnet_modify') and auth('subnet_add')) ) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);

    // If $form is a number, it's an record ID- so we transform $form into an array
    if (is_numeric($form)) $form = array('subnet_id' => $form);
    $subnet = array();

    // Load an existing record (and associated info) if $form is an id
    if (is_numeric($form['subnet_id'])) {
        list($status, $rows, $subnet) = ona_get_subnet_record(array('id' => $form['subnet_id']));
        if ($rows) {
            $subnet['ip_addr'] = ip_mangle($subnet['ip_addr'], 'dotted');
            $subnet['ip_mask'] = ip_mangle($subnet['ip_mask'], 'dotted');
            $subnet['ip_mask_cidr'] = ip_mangle($subnet['ip_mask'], 'cidr');

            // Vlan Record
            list($status, $rows, $vlan) = ona_get_vlan_record(array('id' => $subnet['vlan_id']));
            $subnet['vlan_desc'] = $vlan['vlan_campus_name'] . ' / ' . $vlan['name'];
        }
    }
    // If there is no subnet id in the form
    else {
        if (strlen($form['ip_addr']) > 1) $subnet['ip_addr'] = ip_mangle($form['ip_addr'], 'dotted');
        if (strlen($form['ip_mask']) > 1) $subnet['ip_mask'] = ip_mangle($form['ip_mask'], 'dotted');
        if (strlen($form['name']) > 1) $subnet['name'] = $form['name'];
    }

    if (!$subnet['vlan_id']) $subnet['vlan_desc'] = 'None';


    // Escape data for display in html
    foreach(array_keys((array)$subnet) as $key) { $subnet[$key] = htmlentities($subnet[$key], ENT_QUOTES, $conf['php_charset']); }

    // Set the window title:
    $window['title'] = "Add Subnet";
    if ($subnet['id']) $window['title'] = "Edit Subnet";


    // Build subnet type list
    list($status, $rows, $subnettypes) = db_get_records($onadb, 'subnet_types', 'id > 0', 'display_name');
    $subnet_type_list = '<option value="">&nbsp;</option>\n';
    $subnettypes['subnet_type_name'] = htmlentities($subnettypes['display_name']);
    foreach ($subnettypes as $record) {
        $selected = "";
        if ($record['id'] == $subnet['subnet_type_id']) { $selected = "SELECTED=\"selected\""; }
        if ($record['id']) {$subnet_type_list .= "<option {$selected} value=\"{$record['id']}\">{$record['display_name']}</option>\n";}
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

    el('{$window_name}_edit_form').onsubmit = function() { return false; };


    /* Setup the Quick Find VLAN icon */
    var _button = el('qf_vlan_{$window_name}');
    _button.style.cursor = 'pointer';
    _button.onclick =
        function(ev) {
            if (!ev) ev = event;
            /* Create the popup div */
            wwTT(this, ev,
                 'id', 'tt_qf_vlan_{$window_name}',
                 'type', 'static',
                 'direction', 'south',
                 'delay', 0,
                 'styleClass', 'wwTT_qf',
                 'javascript',
                 "xajax_window_submit('tooltips', '" +
                     "tooltip=>qf_vlan," +
                     "id=>tt_qf_vlan_{$window_name}," +
                     "text_id=>vlan_text_{$window_name}," +
                     "input_id=>set_vlan_{$window_name}');"
            );
        };

    suggest_setup('masks_{$window_name}', 'suggest_masks_{$window_name}');

    el('set_name').focus();

EOL;

    // Define the window's inner html
    $window['html'] = <<<EOL

    <!-- Subnet Edit Form -->
    <form id="{$window_name}_edit_form" onSubmit="return false;">
    <input type="hidden" name="subnet" value="{$subnet['id']}">
    <input type="hidden" name="js" value="{$form['js']}">
    <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">

        <!-- SUBNET RECORD -->
        <tr>
            <td align="left" nowrap="true"><b><u>Subnet Record</u></b>&nbsp;</td>
            <td class="padding" align="left" width="100%">&nbsp;</td>
        </tr>

        <tr>
            <td align="right" nowrap="true">
                VLAN
            </td>
            <td class="padding" align="left" width="100%" nowrap="true">
                <input
                    type="hidden"
                    id="set_vlan_{$window_name}"
                    name="set_vlan"
                    value="{$subnet['vlan_id']}">

                <span id="qf_vlan_{$window_name}">
                    <a id="vlan_text_{$window_name}"
                       class="nav"
                    >{$subnet['vlan_desc']}</a>
                    <img src="{$images}/silk/find.png" border="0"
                /></span>
            </td>
        </tr>

        <tr>
            <td class="input_required" align="right" nowrap="true">
                Name
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    id="set_name"
                    name="set_name"
                    alt="Subnet name"
                    value="{$subnet['name']}"
                    class="edit"
                    type="text"
                    size="30" maxlength="30"
                >
            </td>
        </tr>

        <tr>
            <td class="input_required" align="right" nowrap="true">
                Subnet type
            </td>
            <td class="padding" align="left" width="100%">
                <select name="set_type" class="edit" accesskey="t">
                    {$subnet_type_list}
                </select>
            </td>
        </tr>

        <tr>
            <td class="input_required" align="right" nowrap="true">
                IP Address
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="set_ip"
                    alt="IP Address"
                    value="{$subnet['ip_addr']}"
                    class="edit"
                    type="text"
                    size="17" maxlength="17"
                >
            </td>
        </tr>

        <tr>
            <td class="input_required" align="right" nowrap="true">
                Netmask
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    id="masks_{$window_name}"
                    name="set_netmask"
                    alt="Netmask (i.e. 255.255.255.0 or /24)"
                    value="{$subnet['ip_mask']}"
                    class="edit"
                    type="text"
                    size="17" maxlength="17"
                >
                <div id="suggest_masks_{$window_name}" class="suggest"></div>
            </td>
        </tr>

EOL;

// Show a "keep adding" checkbox if they are adding records
if (!isset($subnet['id'])) {
        $window['html'] .= <<<EOL
        <tr>
            <td align="right" nowrap="true">
                &nbsp;
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="keepadding"
                    alt="Keep adding more subnets"
                    type="checkbox"
                > Keep adding more subnets
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
//     Creates/updates a subnet record.
//////////////////////////////////////////////////////////////////////////////
function ws_save($window_name, $form='') {
    global $include, $conf, $self, $onadb;

    // Check permissions
    if (! (auth('subnet_modify') and auth('subnet_add')) ) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = '';

    // Validate input
    if ($form['set_name'] == '' or
        $form['set_type'] == '' or
        $form['set_ip'] == '' or
        $form['set_netmask'] == ''
       ) {
        $response->addScript("alert('Please complete all fields to continue!');");
        return($response->getXML());
    }

    // Make sure the IP address specified is valid
    $form['set_ip'] = ip_mangle($form['set_ip'], 'dotted');
    if ($form['set_ip'] == -1) {
        $response->addScript("alert('{$self['error']}');");
        return($response->getXML());
    }
    // Make sure the netmask specified is valid
    $form['set_netmask'] = ip_mangle($form['set_netmask'], 'cidr');
    if ($form['set_netmask'] == -1) {
        $self['error'] = preg_replace('/IP address/i', 'netmask', $self['error']);
        $response->addScript("alert('{$self['error']}');");
        return($response->getXML());
    }

    // Before we go on, we must alert the user if this new subnet would require a new PTR zone.
//     $ipflip = ip_mangle($form['set_ip'],'flip');
//     $octets = explode(".",$ipflip);
//     // Find a pointer zone for this ip to associate with.
//     list($status, $rows, $ptrdomain) = ona_find_domain($ipflip.".in-addr.arpa");
//     if (!$ptrdomain['id']) {
//         $self['error'] = "ERROR => This subnet is the first in the {$octets[3]}.0.0.0 class A range.  You must first create at least the following DNS domain: {$octets[3]}.in-addr.arpa\\n\\nSelect OK to create new DNS domain now.";
//         $response->addScript("var doit=confirm('{$self['error']}');if (doit == true) {xajax_window_submit('edit_domain', 'newptrdomainname=>{$octets[3]}.in-addr.arpa', 'editor');} else {removeElement('{$window_name}');}");
//         return($response->getXML());
//     }


    // Decide if we're editing or adding
    $module = 'modify';
    // If we're adding, re-map some the array names to match what the "add" module wants
    if (!$form['subnet']) {
        $module = 'add';

        // If there's no "refresh" javascript, add a command to view the new subnet
        if (!preg_match('/\w/', $form['js'])) $form['js'] = "xajax_window_submit('work_space', 'xajax_window_submit(\'display_subnet\', \'ip=>{$form['set_ip']}\', \'display\')');";

        // Remap some of the option names for the subnet_add() module
        $form['name'] = $form['set_name'];               unset($form['set_name']);
        $form['type'] = $form['set_type'];               unset($form['set_type']);
        $form['ip'] = $form['set_ip'];                   unset($form['set_ip']);
        $form['netmask'] = $form['set_netmask'];         unset($form['set_netmask']);
        $form['vlan'] = $form['set_vlan'];               unset($form['set_vlan']);
    }
    
    // Run the module to ADD or MODIFY the SUBNET.
    list($status, $output) = run_module('subnet_'.$module, $form);

    // If the module returned an error code display a popup warning
    if ($status)
        $js .= "alert('Save failed.\\n". preg_replace('/[\s\']+/', ' ', $self['error']) . "');";
    else {
        // Update the status to tell them what they just did if they just *added* a subnet and the "keep adding" box is checked.
        // Otherwise just close the edit window.
        if ($form['keepadding'] and $module == 'add')
            $js .= "el('statusinfo_{$window_name}').innerHTML = 'Previously added: {$form['name']}';";
        else
            $js .= "removeElement('{$window_name}');";
        
        // If there is "refresh" javascript, send it to the browser to execute
        // MP: FIXME.. there is an issue that if you add a new subnet, then imidiately modify its IP the JS refresh uses the old ip and fails.  find out why
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
//     Deletes a subnet.  $form should be an array with a 'subnet_id'
//     key defined and optionally a 'js' key with javascript to have the
//     browser run after a successful delete.
//////////////////////////////////////////////////////////////////////////////
function ws_delete($window_name, $form='') {
    global $include, $conf, $self, $onadb;

    // Check permissions
    if (!auth('subnet_del')) {
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
    list($status, $output) = run_module('subnet_del', array('subnet' => $form['subnet_id'], 'commit' => $form['commit']));

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