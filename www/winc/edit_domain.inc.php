<?php



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
        $response->script("alert('Permission denied!');");
        return $response;
    }

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);

    // Load an existing record (and associated info) if $form has an id
    if (is_numeric($form['id'])) {
        list($status, $rows, $domain) = ona_get_domain_record(array('id' => $form['id']));
        list($status, $rows, $parent) = ona_get_domain_record(array('id' => $domain['parent_id']));
        $domain['parent'] = ona_build_domain_name($parent['id']);

        // Set the window title:
        $window['title'] = "Edit Domain";

    } else {
        // Set up default domain information
        $domain['admin_email'] = $conf['dns_admin_email'];
        $domain['primary_master']     = $conf['dns_primary_master'];
        $domain['refresh']     = $conf['dns_refresh'];
        $domain['retry']       = $conf['dns_retry'];
        $domain['expiry']      = $conf['dns_expiry'];
        $domain['minimum']     = $conf['dns_minimum'];
        $domain['default_ttl'] = $conf['dns_default_ttl'];
        $domain['parent']      = $conf['dns_parent'];

        // Set the window title:
        $window['title'] = "Add Domain";

    }

    if ($form['newptrdomainname']) $domain['name'] = $form['newptrdomainname'];

    // Escape data for display in html
    foreach(array_keys((array)$domain) as $key) {$domain[$key] = htmlentities($domain[$key], ENT_QUOTES, $conf['php_charset']);}




    // Javascript to run after the window is built
    $window['js'] = <<<EOL
        suggest_setup('domain_edit',     'suggest_domain_edit');
        el('{$window_name}_edit_form').onsubmit = function() { return false; };
        el('domain_name').focus();

EOL;

    // Define the window's inner html
    $window['html'] = <<<EOL

    <!-- Domain Edit Form -->
    <form id="{$window_name}_edit_form" onSubmit="return false;" autocomplete="off">
    <input type="hidden" name="id" value="{$domain['id']}">
    <input type="hidden" name="domain" value="{$domain['name']}">
    <input type="hidden" name="parent" value="{$domain['id']}">
    <input type="hidden" name="js" value="{$form['js']}">
    <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">

        <!-- DOMAIN RECORD -->
        <tr>
            <td align="left" nowrap="true"><b><u>Domain Record</u></b>&nbsp;</td>
            <td class="padding" align="left" width="100%">&nbsp;</td>
        </tr>

        <tr>
            <td class="input_required" align="right" nowrap="true">
                Domain Name
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    id="domain_name"
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
                    value="{$domain['parent']}"
                    class="edit"
                    type="text"
                    size="30" maxlength="255"
                >
                <div id="suggest_domain_edit" class="suggest"></div>
            </td>
        </tr>

        <tr>
            <td align="right" nowrap="true">
                Primary Master
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="primary_master"
                    alt="Primary Master FQDN"
                    value="{$domain['primary_master']}"
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
                Expiry
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="expiry"
                    alt="Expiry"
                    value="{$domain['expiry']}"
                    class="edit"
                    type="text"
                    size="17" maxlength="255"
                >
            </td>
        </tr>

        <tr>
            <td align="right" nowrap="true">
                Minimum (Neg cache)
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="minimum"
                    alt="Minimum/Negative Cache"
                    value="{$domain['minimum']}"
                    class="edit"
                    type="text"
                    size="17" maxlength="255"
                >
            </td>
        </tr>

        <tr>
            <td align="right" nowrap="true">
                Default TTL
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="default_ttl"
                    alt="Default TTL"
                    value="{$domain['default_ttl']}"
                    class="edit"
                    type="text"
                    size="17" maxlength="10"
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
//     Creates/updates an interface record.
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
    if ($form['name'] == '') {
        $response->script("alert('Please complete the domain name field to continue!');");
        return $response;
    }

    //MP: FIXME: It would be nice to disallow "." in the name.. this would force us to create .com .org .net etc domains.
    //  Not that this is a bad thing but it will require a fix to the sub,sub,sub,sub domain issue when doing searches.

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
        $form['set_primary_master'] = $form['primary_master']; unset($form['primary_master']);
        $form['set_refresh'] = $form['refresh']; unset($form['refresh']);
        $form['set_retry']   = $form['retry']; unset($form['retry']);
        $form['set_expiry']  = $form['expiry']; unset($form['expiry']);
        $form['set_minimum'] = $form['minimum']; unset($form['minimum']);
        $form['set_ttl']     = $form['default_ttl']; unset($form['default_ttl']);
        $form['set_parent']  = $form['parent']; unset($form['parent']);
        $form['set_auth']    = $form['auth']; unset($form['auth']);

        // force it to find the domain using the ID
        $form['domain'] = $form['id'];
    }
    else {
        // use the primary master as the first master server
        $form['server'] = $form['primary'];
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
    $response->script($js);
    return $response;
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
        $response->script("alert('Permission denied!');");
        return $response;
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
    $response->script($js);
    return $response;
}




?>
