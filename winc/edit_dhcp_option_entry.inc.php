<?



//////////////////////////////////////////////////////////////////////////////
// Function: ws_editor()
//
// Description:
//     Displays a form for creating/editing an dhcp entry record.
//     If $form is a valid dhcp_entry_id, it is used to display an existing
//     record for editing.  "Save" button calls the ws_save() function.
//////////////////////////////////////////////////////////////////////////////
function ws_editor($window_name, $form='') {
    global $conf, $self, $onadb;
    global $font_family, $color, $style, $images;
    $window = array();

    // Check permissions
    if (!auth('advanced')) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }

    // If an array in a string was provided, build the array and store it in $form
    $form = parse_options_string($form);

    // If $form is a number, it's an dhcp entry record id- so we transform $form into an array
    if ($form['id']) {
        list($status, $rows, $dhcp_entry) = ona_get_dhcp_option_entry_record(array('id' => $form['id']));
        $window['title'] = "Edit DHCP Entry";
    }
    else {
        $window['title'] = "Add DHCP Entry";
    }

    // Load the subnet record and associated info.
    if (is_numeric($form['subnet_id'])) {
        list($status, $rows, $subnet) = ona_get_subnet_record(array('id' => $form['subnet_id']));
        // Setup a title description for this edit type
        $window['edit_type'] = "Subnet";
        $window['edit_type_value'] = "{$subnet['name']}";
    }

    // If they are adding a new DHCP entry they will usually pass a host_id in
    if (is_numeric($form['host_id'])) {
        list($status, $rows, $host)  = ona_find_host($form['host_id']);
        // Setup a title description for this edit type
        $window['edit_type'] = "Host";
        $window['edit_type_value'] = $host['fqdn'];
    }

    // Escape data for display in html
    foreach(array_keys((array)$subnet) as $key) { $subnet[$key] = htmlentities($subnet[$key], ENT_QUOTES); }
    foreach(array_keys((array)$zone) as $key)  { $zone[$key] = htmlentities($zone[$key], ENT_QUOTES); }
    foreach(array_keys((array)$host) as $key)  { $host[$key] = htmlentities($host[$key], ENT_QUOTES); }
    foreach(array_keys((array)$server) as $key)  { $server[$key] = htmlentities($server[$key], ENT_QUOTES); }



    // Build dhcp option list
    list($status, $rows, $dhcpoptions) = db_get_records($onadb, 'dhcp_options', 'id >= 1', 'display_name');
    $dhcp_option_list = '<option value="">&nbsp;</option>\n';
    $dhcpoptions['dhcp_options'] = htmlentities($dhcpoptions['display_name']);
    foreach ($dhcpoptions as $record) {
        $selected = "";
        if ($record['id'] == $dhcp_entry['dhcp_option_id']) { $selected = "SELECTED=\"selected\""; }
        if ($record['id']) {$dhcp_option_list .= "<option {$selected} value=\"{$record['id']}\">{$record['display_name']} ({$record['number']})</option>\n";}
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

        el('{$window_name}_form').onsubmit = function() { return false; };
EOL;


    // Define the window's inner html
    $window['html'] = <<<EOL

    <!-- DHCP entry Edit Form -->
    <form id="{$window_name}_form" onSubmit="return false;">
    <input type="hidden" name="host" value="{$host['id']}">
    <input type="hidden" name="subnet" value="{$subnet['id']}">
    <input type="hidden" name="server" value="{$server['id']}">
    <input type="hidden" name="id" value="{$dhcp_entry['id']}">
    <input type="hidden" name="js" value="{$form['js']}">
    <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">

        <!-- DHCP ENTRY RECORD -->
        <tr>
            <td align="left" nowrap="true"><b><u>DHCP Entry Record</u></b>&nbsp;</td>
            <td class="padding" align="left" width="100%">&nbsp;</td>
        </tr>

        <tr>
            <td align="right" nowrap="true" style="font-weight: bold;">
                {$window['edit_type']}
            </td>
            <td class="padding" align="left" width="100%">
                {$window['edit_type_value']}
            </td>
        </tr>

        <tr>
            <td align="right" nowrap="true" style="font-weight: bold;">
                DHCP Option
            </td>
            <td class="padding" align="left" width="100%">
                <select id="option" name="option" class="edit" accesskey="l">
                    {$dhcp_option_list}
                </select>
            </td>
        </tr>

        <tr>
            <td align="right" nowrap="true" style="font-weight: bold;">
                Value
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="value"
                    alt="Value"
                    value="{$dhcp_entry['value']}"
                    class="edit"
                    type="text"
                    size="31" maxlength="255"
                >
            </td>
        </tr>



        <tr>
            <td align="right" valign="top" nowrap="true">
                &nbsp;
            </td>
            <td class="padding" align="right" width="100%">
                <input type="hidden" name="overwrite" value="{$overwrite}">
                <input class="edit" type="button" name="cancel" value="Cancel" onClick="removeElement('{$window_name}');">
                <input class="edit" type="button"
                    name="submit"
                    value="Save"
                    onClick="xajax_window_submit('{$window_name}', xajax.getFormValues('{$window_name}_form'), 'save');"
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
//     Creates/updates a dhcp entry record.
//////////////////////////////////////////////////////////////////////////////
function ws_save($window_name, $form='') {
    global $base, $include, $conf, $self, $onadb;

    // Check permissions
    if (!auth('advanced')) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = '';

    // Validate input
    if (!$form['type'] and !$form['value']) {
        $response->addScript("alert('Please complete all fields to continue!');");
        return($response->getXML());
    }

    // Validate the host is valid
    // FIXME: we should do this, but since it's not an editable field it's not too big of a deal

    // Decide if we're editing or adding
    $module = 'dhcp_entry_add';
    if ($form['id']) {
        $module = 'dhcp_entry_modify';
        $form['set_option'] = $form['option'];
        $form['set_value'] = $form['value'];
    }

    // Run the module
    list($status, $output) = run_module($module, $form);

    // If the module returned an error code display a popup warning
    if ($status) {
        $js .= "alert('Save failed: ". preg_replace('/[\s\']+/', ' ', $self['error']) . "');";
    }
    else {
        $js .= "removeElement('{$window_name}');";
        // If there's JS, add it to $js so we'll send it to the browser later.
        if ($form['js']) { $js .= $form['js']; }
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
//     Deletes an alias record.
//////////////////////////////////////////////////////////////////////////////
function ws_delete($window_name, $form='') {
    global $base, $include, $conf, $self, $mysql, $onadb;

    // Check permissions
    if (!auth('advanced')) {
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
    list($status, $output) = run_module('dhcp_entry_del', array('id' => $form['id'], 'commit' => 'Y'));

    // If the module returned an error code display a popup warning
    if ($status) { $js .= "alert('Delete failed. ". preg_replace('/[\s\']+/', ' ', $self['error']) . "');"; }
    else {
        // If there's JS, add it to $js so we'll send it to the browser later.
        if ($form['js']) { $js .= $form['js']; }
    }

    // Return an XML response
    $response->addScript($js);
    return($response->getXML());

}



?>