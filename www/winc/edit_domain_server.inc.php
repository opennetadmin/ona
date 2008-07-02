<?



//////////////////////////////////////////////////////////////////////////////
// Function: ws_editor($window_name, $form)
//
// Description:
//     Displays a form for adding a domain to a DNS server
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

    if ($form['domain']) {
        list($status, $rows, $domain) = ona_find_domain($form['domain']);
    }
    // Escape data for display in html
    foreach(array_keys((array)$host) as $key)  { $host[$key]  = htmlentities($host[$key],  ENT_QUOTES); }



    // Set the window title:
    $window['title'] = "Assign domain to server";

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

        suggest_setup('domain_server_name',  'suggest_domain_server_name');
        suggest_setup('domain_server_edit',  'suggest_domain_server_edit');

EOL;


    // Define the window's inner html
    $window['html'] = <<<EOL

    <!-- DNS server Edit Form -->
    <form id="{$window_name}_form" onSubmit="return false;">
    <input type="hidden" name="js" value="{$form['js']}">
    <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">

        <tr>
            <td align="left" nowrap="true"><b><u>Assign Domain</u></b>&nbsp;</td>
            <td class="padding" align="left" width="100%">&nbsp;</td>
        </tr>

        <tr>
            <td align="right" nowrap="true" style="font-weight: bold;">
                Server
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    id="domain_server_name"
                    name="server"
                    alt="Server name"
                    value="{$host['fqdn']}"
                    class="edit"
                    type="text"
                    size="34" maxlength="255"
                >
               <div id="suggest_domain_server_name" class="suggest"></div>
            </td>
        </tr>


        <tr>
            <td align="right" nowrap="true" style="font-weight: bold;">
                Domain
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    id="domain_server_edit"
                    name="domain"
                    alt="Domain name"
                    value="{$domain['fqdn']}"
                    class="edit"
                    type="text"
                    size="34" maxlength="255"
                >
               <div id="suggest_domain_server_edit" class="suggest"></div>
            </td>
        </tr>

        <tr>
            <td align="right" nowrap="true" style="font-weight: bold;">
                Role
            </td>
            <td class="padding" align="left" width="100%" nowrap="true">
                <select class="edit" name="role" alt="Role">
                    <option value="forward">Forward</option>
                    <option value="master" selected>Master</option>
                    <option value="slave">Slave</option>
                </select>
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
    if (!$form['domain']) {
        $response->addScript("alert('Please select a domain to continue!');");
        return($response->getXML());
    }


    // Decide if we're editing or adding
    $module = 'domain_server_add';

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
    list($status, $output) = run_module('domain_server_del', array('server' => $form['server'], 'domain' => $form['domain'], 'commit' => 'Y'));

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