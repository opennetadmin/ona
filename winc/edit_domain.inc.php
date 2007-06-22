<?



//////////////////////////////////////////////////////////////////////////////
// Function: ws_editor()
// 
// Description:
//     Displays a form for creating/editing a domain record.
//     If $form is a valid domain ID, it is used to display an existing
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

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);
    
    // Load an existing host record (and associated info) if $form is a host_id
    if ($form['id']) {
        list($status, $rows, $domain) = ona_get_domain_record(array('id' => $form['id']));
        list($status, $rows, $parent) = ona_get_domain_record(array('id' => $domain['parent_id']));
        $domain['parent'] = $parent['name'];

        // Set the window title:
        $window['title'] = "Edit Domain";

        // Prepare some stuff for displaying checkboxes
        if ($domain['POINTER_DOMAIN'] != 'N') { $domain['POINTER_DOMAIN'] = 'CHECKED'; }


    } else {
        // Set up default domain information  * FIXME: *
        $domain['admin_email'] = $conf['dns']['admin'];
        $domain['ptr']         = $conf['dns']['ptr'] ;
        $domain['ns_fqdn']     = $conf['dns']['origin']; // this is NOT used currently as origin, it is primary master
        $domain['refresh']     = $conf['dns']['refresh'];
        $domain['retry']       = $conf['dns']['retry'];
        $domain['expire']      = $conf['dns']['expire'];
        $domain['minimum']     = $conf['dns']['minimum'];
        $domain['parent']      = $conf['dns']['parent'];
        $domain['AUTH']        = 'Y';    // is server authoritative for this domain

        // Set the window title:
        $window['title'] = "Add Domain";

    }
    

    // Escape data for display in html
    foreach(array_keys((array)$domain) as $key) {$domain[$key] = htmlentities($domain[$key], ENT_QUOTES);}
    
    
    

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
        
        suggest_setup('domain_edit',     'suggest_domain_edit');
        el('{$window_name}_edit_form').onsubmit = function() { return false; };

EOL;

    // Define the window's inner html
    $window['html'] = <<<EOL
    
    <!-- Domain Edit Form -->
    <form id="{$window_name}_edit_form" onSubmit="return false;">
    <input type="hidden" name="id" value="{$domain['id']}">
    <input type="hidden" name="domain" value="{$domain['name']}">
    <input type="hidden" name="parent" value="{$domain['id']}">
    <input type="hidden" name="output_file" value=" ">
    <input type="hidden" name="auth" value="{$domain['AUTH']}">
    <input type="hidden" name="js" value="{$form['js']}">
    <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">
        
        <!-- DOMAIN RECORD -->
        <tr>
            <td align="left" nowrap="true"><b><u>Domain Record</u></b>&nbsp;</td>
            <td class="padding" align="left" width="100%">&nbsp;</td>
        </tr>
        
        <tr>
            <td align="right" nowrap="true">
                Domain Name
            </td>
            <td class="padding" align="left" width="100%">
                <input 
                    name="name"
                    alt="Domain name"
                    value="{$domain['name']}"
                    class="edit" 
                    type="text" 
                    size="30" maxlength="255" 
                >
            </td>
        </tr>
        
        <tr>
            <td align="right" nowrap="true">
                Parent Domain
            </td>
            <td class="padding" align="left" width="100%">
                <input 
                    id="domain_edit"
                    name="parent" 
                    alt="Parent Domain"
                    value="{$parent['parent']}"
                    class="edit" 
                    type="text" 
                    size="30" maxlength="255" 
                >
                <div id="suggest_domain_edit" class="suggest"></div>
            </td>
        </tr>
        
        <tr>
            <td align="right" nowrap="true">
                Pointer Domain
            </td>
            <td class="padding" align="left" width="100%">
                <input 
                    name="ptr"
                    alt="Pointer Domain"
                    {$domain['POINTER_DOMAIN']}
                    class="edit" 
                    type="checkbox" 
                >
            </td>
        </tr>

        <tr>
            <td align="right" nowrap="true">
                Origin (PRI server)
            </td>
            <td class="padding" align="left" width="100%">
                <input 
                    name="origin" 
                    alt="Origin"
                    value="{$domain['ns_fqdn']}"
                    class="edit" 
                    type="text" 
                    size="30" maxlength="255" 
                >
            </td>
        </tr>

        <tr>
            <td align="right" nowrap="true">
                Domain Admin
            </td>
            <td class="padding" align="left" width="100%">
                <input 
                    name="admin"
                    alt="Domain Admin"
                    value="{$domain['admin_email']}"
                    class="edit" 
                    type="text" 
                    size="30" maxlength="255" 
                >
            </td>
        </tr>
EOL;
    // if we are editing an existing domain
    // FIXME: need to ensure serial is converted from 32-bit binary
    // (MP)commented out for not since it is NOT yet converted from binary
//     if($form['id']) {
// 
//         $window['html'] .= <<<EOL
//         <tr>
//             <td align="right" nowrap="true">
//                 Serial Number
//             </td>
//             <td class="padding" align="left" width="100%">
//                 <input
//                     name="serial"
//                     alt="Serial Number"
//                     value="{$domain['serial']}"
//                     class="edit"
//                     type="text"
//                     size="17" maxlength="255"
//                 >
//             </td>
//         </tr>
// EOL;
//     }

    $window['html'] .= <<<EOL
        <tr>
            <td align="right" nowrap="true">
                Refresh
            </td>
            <td class="padding" align="left" width="100%">
                <input 
                    name="refresh" 
                    alt="Refresh"
                    value="{$domain['refresh']}"
                    class="edit" 
                    type="text" 
                    size="17" maxlength="255" 
                >
            </td>
        </tr>

        <tr>
            <td align="right" nowrap="true">
                Retry
            </td>
            <td class="padding" align="left" width="100%">
                <input 
                    name="retry" 
                    alt="Retry"
                    value="{$domain['retry']}"
                    class="edit" 
                    type="text" 
                    size="17" maxlength="255" 
                >
            </td>
        </tr>

        <tr>
            <td align="right" nowrap="true">
                Expire
            </td>
            <td class="padding" align="left" width="100%">
                <input 
                    name="expire" 
                    alt="Expire"
                    value="{$domain['expire']}"
                    class="edit" 
                    type="text" 
                    size="17" maxlength="255" 
                >
            </td>
        </tr>

        <tr>
            <td align="right" nowrap="true">
                Minimum
            </td>
            <td class="padding" align="left" width="100%">
                <input 
                    name="minimum" 
                    alt="Minimum"
                    value="{$domain['minimum']}"
                    class="edit" 
                    type="text" 
                    size="17" maxlength="255" 
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
    if (!auth('advanced')) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }
    
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = '';
    
    // Validate input
    if ($form['name'] == '') {
        $response->addScript("alert('Please complete the domain name field to continue!');");
        return($response->getXML());
    }
    
    if (!$form['ptr']) $form['ptr'] = 'N';

    // Decide if we're editing or adding
    $module = 'domain_add';
    // If we're modifying, re-map some the array names to match what the "modify" module wants
    if ($form['id']) {
        $module = 'domain_modify';
        $form['set_name']    = $form['name']; unset($form['name']);
        $form['set_server']  = $form['server']; unset($form['server']);
        $form['set_admin']   = $form['admin']; unset($form['admin']);
        $form['set_ptr']     = $form['ptr']; unset($form['ptr']);
        $form['set_origin']  = $form['origin']; unset($form['origin']);
        $form['set_refresh'] = $form['refresh']; unset($form['refresh']);
        $form['set_retry']   = $form['retry']; unset($form['retry']);
        $form['set_expire']  = $form['expire']; unset($form['expire']);
        $form['set_minimum'] = $form['minimum']; unset($form['minimum']);
        $form['set_parent']  = $form['parent']; unset($form['parent']);
        $form['set_auth']    = $form['auth']; unset($form['auth']);
        $form['set_serial']  = $form['serial']; unset($form['serial']);
    }
    else {
        // use the origin(primary master) as the first master server
        $form['server'] = $form['origin'];
        unset($form['output_file']);
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
    $response->addScript($js);
    return($response->getXML());
}







//////////////////////////////////////////////////////////////////////////////
// Function:
//     Delete Form
// 
// Description:
//     Deletes a domain record.  $form should be an array with a 'domain_id'
//     key defined and optionally a 'js' key with javascript to have the
//     browser run after a successful delete.
//////////////////////////////////////////////////////////////////////////////
function ws_delete($window_name, $form='') {
    global $include, $conf, $self, $mysql, $onadb;
    
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
    list($status, $output) = run_module('domain_del', array('domain' => $form['id'], 'commit' => 'Y'));
    
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
