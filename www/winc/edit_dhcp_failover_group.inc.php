<?php



//////////////////////////////////////////////////////////////////////////////
// Function: ws_editor()
//
// Description:
//     Displays a form for creating/editing a dhcp failover group record.
//     If $form is a valid dhcp_failover_group_id, it is used to display an existing
//     record for editing.  "Save" button calls the ws_save() function.
//////////////////////////////////////////////////////////////////////////////
function ws_editor($window_name, $form='') {
    global $conf, $self, $onadb;
    global $font_family, $color, $style, $images;
    $window = array();

    // Check permissions
    if (!auth('advanced')) {
        $response = new xajaxResponse();
        $response->script("alert('Permission denied!');");
        return $response;
    }

    // If an array in a string was provided, build the array and store it in $form
    $form = parse_options_string($form);


    // Load an existing host record (and associated info) if $form is a host_id
    if ($form['id']) {
        list($status, $rows, $failovergroup) = ona_get_dhcp_failover_group_record(array('id' => $form['id']));
        if (!$rows) {
            $self['error'] = "ERROR => Unable to find the DHCP failover record using {$form['id']}!";
            return(array(4, $self['error']. "\n"));
        }

        list($status, $rows, $pri_server) = ona_find_host($failovergroup['primary_server_id']);
        list($status, $rows, $sec_server) = ona_find_host($failovergroup['secondary_server_id']);
        $failovergroup['pri_server_name'] = $pri_server['fqdn'];
        $failovergroup['sec_server_name'] = $sec_server['fqdn'];

        // Set the window title:
        $window['title'] = "Edit DHCP Failover Group";

    } else {
        // Set up default failover information
         $failovergroup['max_response_delay']      = '60';
         $failovergroup['max_unacked_updates']     = '10';
         $failovergroup['max_load_balance']        = '3';
         $failovergroup['primary_port']            = '647';
         $failovergroup['peer_port']               = '847';
         $failovergroup['mclt']                    = '1800';
         $failovergroup['split']                   = '255';

        // Set the window title:
        $window['title'] = "Add DHCP Failover Group";

    }


    // Escape data for display in html
    foreach(array_keys((array)$failovergroup) as $key) { $failovergroup[$key] = htmlentities($failovergroup[$key], ENT_QUOTES, $conf['php_charset']); }




    // Javascript to run after the window is built
    $window['js'] = <<<EOL
        suggest_setup('failover_pri_hostname', 'suggest_failover_pri_hostname');
        suggest_setup('failover_sec_hostname', 'suggest_failover_sec_hostname');

        el('{$window_name}_edit_form').onsubmit = function() { return false; };

        el('failover_pri_hostname').focus();
EOL;

    // Define the window's inner html
    $window['html'] = <<<EOL

    <!-- DHCP Failover Group Edit Form -->
    <form id="{$window_name}_edit_form" onSubmit="return false;" autocomplete="off">
    <input type="hidden" name="id" value="{$failovergroup['id']}">
    <input type="hidden" name="js" value="{$form['js']}">
    <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">

        <!-- DHCP FAILOVER GROUP RECORD -->
        <tr>
            <td align="left" nowrap="true"><b><u>DHCP Failover Group Record</u></b>&nbsp;</td>
            <td class="padding" align="left" width="100%">&nbsp;</td>
        </tr>

        <tr>
            <td class="input_required" align="right" nowrap="true">
                Primary Server
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    id="failover_pri_hostname"
                    name="pri_server"
                    alt="Primary Server"
                    value="{$failovergroup['pri_server_name']}"
                    class="edit"
                    type="text"
                    size="30" maxlength="255"
                >
                <div id="suggest_failover_pri_hostname" class="suggest"></div>
            </td>
        </tr>

        <tr>
            <td class="input_required" align="right" nowrap="true">
                Secondary Server
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    id="failover_sec_hostname"
                    name="sec_server"
                    alt="Secondary Server"
                    value="{$failovergroup['sec_server_name']}"
                    class="edit"
                    type="text"
                    size="30" maxlength="255"
                >
                <div id="suggest_failover_sec_hostname" class="suggest"></div>
            </td>
        </tr>

        <tr>
            <td align="right" nowrap="true">
                Max Response Delay
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="response_delay"
                    alt="Max Response Delay"
                    value="{$failovergroup['max_response_delay']}"
                    class="edit"
                    type="text"
                    size="10" maxlength="10"
                >
            </td>
        </tr>

        <tr>
            <td align="right" nowrap="true">
                Max Unacked Updates
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="unacked_updates"
                    alt="Max Unacked Updates"
                    value="{$failovergroup['max_unacked_updates']}"
                    class="edit"
                    type="text"
                    size="10" maxlength="10"
                >
            </td>
        </tr>

        <tr>
            <td align="right" nowrap="true">
                Max Load Balance
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="max_balance"
                    alt="Load Balance"
                    value="{$failovergroup['max_load_balance']}"
                    class="edit"
                    type="text"
                    size="10" maxlength="10"
                >
            </td>
        </tr>

        <tr>
            <td align="right" nowrap="true">
                Primary Port Num
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="priport"
                    alt="Primary Port Num"
                    value="{$failovergroup['primary_port']}"
                    class="edit"
                    type="text"
                    size="10" maxlength="10"
                >
            </td>
        </tr>

        <tr>
            <td align="right" nowrap="true">
                Peer Port Num
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="peerport"
                    alt="Peer Port Num"
                    value="{$failovergroup['peer_port']}"
                    class="edit"
                    type="text"
                    size="10" maxlength="10"
                >
            </td>
        </tr>

        <tr>
            <td align="right" nowrap="true">
                MCLT
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="mclt"
                    alt="MCLT"
                    value="{$failovergroup['mclt']}"
                    class="edit"
                    type="text"
                    size="10" maxlength="10"
                >
            </td>
        </tr>

        <tr>
            <td align="right" nowrap="true">
                Split
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="split"
                    alt="split"
                    value="{$failovergroup['split']}"
                    class="edit"
                    type="text"
                    size="10" maxlength="10"
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
                <button type="submit"
                    name="submit"
                    accesskey=" "
                    onClick="xajax_window_submit('{$window_name}', xajax.getFormValues('{$window_name}_edit_form'), 'save');"
                >Save</button>
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
//     Creates/updates a record.
//////////////////////////////////////////////////////////////////////////////
function ws_save($window_name, $form='') {
    global $include, $conf, $self, $onadb;

    // Check permissions (there is no interface_add, it's merged with host_add)
    if (!auth('advanced')) {
        $response = new xajaxResponse();
        $response->script("alert('Permission denied!');");
        return $response;
    }

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = '';

    // Validate input
    if ($form['pri_server'] == '' or $form['sec_server'] == '' ) {
        $response->script("alert('Please complete all required fields to continue!');");
        return $response;
    }

    // Decide if we're editing or adding
    $module = 'dhcp_failover_group_add';
    // If we're modifying, re-map some the array names to match what the "modify" module wants
    if ($form['id']) {
        $module = 'dhcp_failover_group_modify';
        $form['set_pri_server']       = $form['pri_server'];      unset($form['pri_server']);
        $form['set_sec_server']       = $form['sec_server'];      unset($form['sec_server']);
        $form['set_response_delay']   = $form['response_delay'];  unset($form['response_delay']);
        $form['set_unacked_updates']  = $form['unacked_updates']; unset($form['unacked_updates']);
        $form['set_max_balance']      = $form['max_balance'];     unset($form['max_balance']);
        $form['set_priport']          = $form['priport'];         unset($form['priport']);
        $form['set_peerport']         = $form['peerport'];        unset($form['peerport']);
        $form['set_mclt']             = $form['mclt'];            unset($form['mclt']);
        $form['set_split']            = $form['split'];           unset($form['split']);

    }

    // Run the module
    list($status, $output) = run_module($module, $form);

    // If the module returned an error code display a popup warning
    if ($status)
        $js .= "alert('Save failed.\\n". preg_replace('/[\s\']+/', ' ', $self['error']) . "');";
    else {
        $js .= "removeElement('{$window_name}');";
        if ($form['js']) $js .= $form['js'];
    }

    // Insert the new table into the window
    $response->script($js);
    return $response;
}







//////////////////////////////////////////////////////////////////////////////
// Function:
//     Delete Form
//
// Description:
//     Deletes a dhcp failover group record.  $form should be an array with a 'id'
//     key defined and optionally a 'js' key with javascript to have the
//     browser run after a successful delete.
//////////////////////////////////////////////////////////////////////////////
function ws_delete($window_name, $form='') {
    global $include, $conf, $self, $mysql, $onadb;

    // Check permissions
    if (!auth('advanced')) {
        $response = new xajaxResponse();
        $response->script("alert('Permission denied!');");
        return $response;
    }

    // If an array in a string was provided, build the array and store it in $form
    $form = parse_options_string($form);

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = '';

    // Run the module
    list($status, $output) = run_module('dhcp_failover_group_del', array('id' => $form['id'], 'commit' => 'Y'));

    // If the module returned an error code display a popup warning
    if ($status)
        $js .= "alert('Delete failed. " . preg_replace('/[\s\']+/', ' ', $self['error']) . "');";
    else if ($form['js'])
        $js .= $form['js'];  // usually js will refresh the window we got called from

    // Return an XML response
    $response->script($js);
    return $response;
}




?>
