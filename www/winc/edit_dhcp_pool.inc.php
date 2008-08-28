<?php



//////////////////////////////////////////////////////////////////////////////
// Function: ws_editor()
//
// Description:
//     Displays a form for creating/editing an dhcp pool record.
//     If $form is a valid pool ID, it is used to display an existing
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


    // if it is a new pool, setup some things
    if (!$form['id']) {
        list($status, $rows, $subnet) = ona_get_subnet_record(array('id' => $form['subnet']));
        // set start and end IP to subnet IP
        $pool['start_ip'] = $pool['end_ip'] = ip_mangle($subnet['ip_addr'], 'dotted');
        // setup defaults for form values-- FIXME use $conf['dhcp_pool'] stuff later
        $pool['lease_length']       = '604800';
        $pool['lease_grace_period'] = '0';
        $pool['lease_rebind_time']  = '0';
        $pool['lease_renewal_time'] = '0';

        $pool['server_name_text'] = 'None';
        $window['title'] = "Add DHCP Pool";
    } else {
        list($status, $rows, $pool) = ona_get_dhcp_pool_record(array('id' => $form['id']));
        $pool['start_ip'] = ip_mangle($pool['ip_addr_start']);
        $pool['end_ip']   = ip_mangle($pool['ip_addr_end']);
        $pool['server_name_text'] = 'None';

        // Load the subnet record and associated info.
        if (is_numeric($form['subnet'])) {
            list($status, $rows, $subnet) = ona_get_subnet_record(array('id' => $form['subnet']));
        }


        // Load the server record and associated info.
        if ($pool['dhcp_failover_group_id'] >= 1) {
            list($status, $rows, $failover) = ona_get_dhcp_failover_group_record(array('id' => $pool['dhcp_failover_group_id']));

            list($status, $rows, $fail_host1) = ona_find_host($failover['primary_server_id']);
            list($status, $rows, $fail_host2) = ona_find_host($failover['secondary_server_id']);
            $pool['server_name_text'] = $fail_host1['fqdn'] . "/" . $fail_host2['fqdn'];
        }

        $window['title'] = "Edit DHCP Pool";

    }


    // Escape data for display in html
    foreach(array_keys((array)$subnet) as $key) { $subnet[$key] = htmlentities($subnet[$key], ENT_QUOTES); }
    foreach(array_keys((array)$failover) as $key) { $failover[$key] = htmlentities($failover[$key], ENT_QUOTES); }
    foreach(array_keys((array)$zone) as $key)  { $zone[$key] = htmlentities($zone[$key], ENT_QUOTES); }
    foreach(array_keys((array)$host) as $key)  { $host[$key] = htmlentities($host[$key], ENT_QUOTES); }
    foreach(array_keys((array)$server) as $key)  { $server[$key] = htmlentities($server[$key], ENT_QUOTES); }




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

        /* Setup the Quick Find pool server icon */
    var _button = el('qf_pool_server_{$window_name}');
    _button.style.cursor = 'pointer';
    _button.onclick =
        function(ev) {
            if (!ev) ev = event;
            /* Create the popup div */
            wwTT(this, ev,
                 'id', 'tt_qf_pool_server_{$window_name}',
                 'type', 'static',
                 'direction', 'south',
                 'delay', 0,
                 'styleClass', 'wwTT_qf',
                 'javascript',
                 "xajax_window_submit('tooltips', '" +
                     "tooltip=>qf_pool_server," +
                     "id=>tt_qf_pool_server_{$window_name}," +
                     "text_id=>pool_server_text_{$window_name}," +
                     "server=>set_pool_server_{$window_name}," +
                     "server_name=>{$pool['server_name_text']}," +
                     "failover_group_id=>{$pool['dhcp_failover_group_id']}," +
                     "failover_group=>set_failover_group_{$window_name}');"
            );
        };


EOL;


    // Define the window's inner html
    $window['html'] = <<<EOL

    <!-- DHCP pool Edit Form -->
    <form id="{$window_name}_form" onSubmit="return false;">
    <input type="hidden" name="id" value="{$pool['id']}">
    <input type="hidden" name="subnet_id" value="{$form['subnet']}">
    <input type="hidden" name="js" value="{$form['js']}">
    <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">

        <!-- DHCP POOL RECORD -->
        <tr>
            <td align="left" nowrap="true"><b><u>DHCP Pool</u></b>&nbsp;</td>
            <td class="padding" align="left" width="100%">&nbsp;</td>
        </tr>

        <tr>
            <td align="right" nowrap="true" style="font-weight: bold;">
                Subnet
            </td>
            <td class="padding" align="left" width="100%">
                {$subnet['name']}
            </td>
        </tr>


        <tr>
            <td align="right" nowrap="true" style="font-weight: bold;">
                Failover Group
            </td>
            <td class="padding" align="left" width="100%" nowrap="true">
                <input
                    type="hidden"
                    id="set_failover_group_{$window_name}"
                    name="failover_group"
                    value="{$pool['dhcp_failover_group_id']}">

                <span id="qf_pool_server_{$window_name}" title="DHCP Pool Server Quick Select">
                    <a id="pool_server_text_{$window_name}"
                       class="nav"
                    >{$pool['server_name_text']}</a>
                    <img src="{$images}/silk/find.png" border="0"
                /></span>
            </td>
        </tr>


        <!-- TODO: add a qf for IP addresses to list avail ips on subnet -->



        <tr>
            <td align="right" nowrap="true" style="font-weight: bold;">
                IP Start
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="start"
                    alt="IP Start"
                    value="{$pool['start_ip']}"
                    class="edit"
                    type="text"
                    size="15" maxlength="255"
                >
            </td>
        </tr>

        <tr>
            <td align="right" nowrap="true" style="font-weight: bold;">
                IP End
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="end"
                    alt="IP End"
                    value="{$pool['end_ip']}"
                    class="edit"
                    type="text"
                    size="15" maxlength="255"
                >
            </td>
        </tr>

        <tr>
            <td align="right" nowrap="true" style="font-weight: bold;">
                Lease Length
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="llength"
                    alt="Lease Length"
                    value="{$pool['lease_length']}"
                    class="edit"
                    type="text"
                    size="10" maxlength="10"
                >
            </td>
        </tr>

        <tr>
            <td align="right" nowrap="true" style="font-weight: bold;">
                Lease Grace
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="lgrace"
                    alt="Lease Grace"
                    value="{$pool['lease_grace_period']}"
                    class="edit"
                    type="text"
                    size="10" maxlength="10"
                >
            </td>
        </tr>

        <tr>
            <td align="right" nowrap="true" style="font-weight: bold;">
                Lease Renewal
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="lrenewal"
                    alt="Lease Renewal"
                    value="{$pool['lease_renewal_time']}"
                    class="edit"
                    type="text"
                    size="10" maxlength="10"
                >
            </td>
        </tr>


        <tr>
            <td align="right" nowrap="true" style="font-weight: bold;">
                Lease Rebind
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="lrebind"
                    alt="Lease Rebind"
                    value="{$pool['lease_rebind_time']}"
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
//     Creates/updates a dhcp pool record.
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
    if (!$form['start'] and !$form['end']) {
        $response->addScript("alert('Please complete all fields to continue!');");
        return($response->getXML());
    }

    list($status, $rows, $subnet) = ona_find_subnet($form['subnet_id']);
    $start_dec = ip_mangle($form['start'], 'numeric');
    $end_dec   = ip_mangle($form['end'], 'numeric');
    $net_end = ((4294967295 - $subnet['ip_mask']) + $subnet['ip_addr']);

    // check the ips are part of the subnet you are on
    if (($start_dec < $subnet['ip_addr'] or $start_dec > $net_end) or ($end_dec < $subnet['ip_addr'] or $end_dec > $net_end)) {
        $response->addScript("alert('Save failed: ERROR => The pool range you specified is not part of the subnet: {$subnet['name']}!');");
        return($response->getXML());
    }


    // Decide if we're editing or adding
    $module = 'dhcp_pool_add';
    if ($form['id']) {
        $module = 'dhcp_pool_modify';
        $form['pool'] = $form['id'];
        $form['set_start'] = $form['start'];
        $form['set_end']   = $form['end'];
        $form['set_llength'] = $form['llength'];
        $form['set_lgrace'] = $form['lgrace'];
        $form['set_lrenewal'] = $form['lrenewal'];
        $form['set_lrebind'] = $form['lrebind'];
        $form['set_failover_group'] = $form['failover_group'];
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
    list($status, $output) = run_module('dhcp_pool_del', array('id' => $form['id'], 'commit' => 'Y'));

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