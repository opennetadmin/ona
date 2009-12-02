<?php



//////////////////////////////////////////////////////////////////////////////
// Function: ws_editor($window_name, $form)
//
// Description:
//     Displays a form for adding a subnet to a DHCP server
//
// Input:
//     $window_name the name of the "window" to use.
//     $form  A string-based-array or an array
//            The string-based-array would usually look something like this:
//              server_id=>123,js=>some('javascript');
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
    if (!auth('advanced')) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }

    // If an array in a string was provided, build the array and store it in $form
    $form = parse_options_string($form);

    if ($form['server']) {
        list($status, $rows, $host) = ona_find_host($form['server']);
    }

    if ($form['subnet']) {
        $subnet['name'] = $form['subnet'];
    }
    // Escape data for display in html
    foreach(array_keys((array)$host) as $key)  { $host[$key]  = htmlentities($host[$key],  ENT_QUOTES, $conf['php_charset']); }



    // Set the window title:
    $window['title'] = "Assign subnet to server";

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

        suggest_setup('dhcp_server_name',  'suggest_dhcp_server_name');
        suggest_setup('dhcp_subnet_name',  'suggest_dhcp_subnet_name');
        el('dhcp_server_name').focus();
EOL;


    // Define the window's inner html
    $window['html'] = <<<EOL

    <!-- DHCP server Edit Form -->
    <form id="{$window_name}_form" onSubmit="return false;">
    <input type="hidden" name="js" value="{$form['js']}">
    <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">

        <tr>
            <td align="left" nowrap="true"><b><u>Assign Subnet</u></b>&nbsp;</td>
            <td class="padding" align="left" width="100%">&nbsp;</td>
        </tr>

        <tr>
            <td class="input_required" align="right" nowrap="true">
                Server
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    id="dhcp_server_name"
                    name="server"
                    alt="Server name"
                    value="{$host['fqdn']}"
                    class="edit"
                    type="text"
                    size="34" maxlength="255"
                >
               <div id="suggest_dhcp_server_name" class="suggest"></div>
            </td>
        </tr>


        <tr>
            <td class="input_required" align="right" nowrap="true">
                Subnet
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    id="dhcp_subnet_name"
                    name="subnet"
                    alt="Subnet name"
                    value="{$subnet['name']}"
                    class="edit"
                    type="text"
                    size="34" maxlength="255"
                >
               <div id="suggest_dhcp_subnet_name" class="suggest"></div>
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
                    accesskey=" "
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
//     Creates/updates an alias record.
//////////////////////////////////////////////////////////////////////////////
function ws_save($window_name, $form='') {
    global $base, $include, $conf, $self, $onadb;

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

    // Validate input
    if (!$form['subnet']) {
        $response->addScript("alert('Please select a subnet to continue!');");
        return($response->getXML());
    }

    // Validate subnet is valid
    list($status, $rows, $subnet)  = ona_get_subnet_record(array('name'  => $form['subnet']));
    if ($status or !$rows) {
        $response->addScript("alert('Invalid subnet!');");
        return($response->getXML());
    }

    // Decide if we're editing or adding
    $module = 'dhcp_server_add';

    // Run the module
    list($status, $output) = run_module($module, $form);

    // If the module returned an error code display a popup warning
    if ($status) {
        $js .= "alert('Save failed. ". preg_replace('/[\s\']+/', ' ', $self['error']) . "');";
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
//     Removes a zone from a server in zone_servers_b record.  $form should be an array with an 'server'
//     and 'zone' fields.
//////////////////////////////////////////////////////////////////////////////
function ws_delete($window_name, $form='') {
    global $base, $include, $conf, $self, $onadb;

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
    list($status, $output) = run_module('dhcp_server_del', array('server' => $form['server_id'], 'subnet' => $form['subnet_id'], 'commit' => 'Y'));

    // If the module returned an error code display a popup warning
    if ($status) { $js .= "alert('Delete failed. " . preg_replace('/[\s\']+/', ' ', $self['error']) . "');"; }
    else {
        // If there's JS, add it to $js so we'll send it to the browser later.
        if ($form['js']) { $js .= $form['js']; }
    }

    // Return an XML response
    $response->addScript($js);
    return($response->getXML());
}



?>