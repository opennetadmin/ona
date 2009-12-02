<?php



//////////////////////////////////////////////////////////////////////////////
// Function: ws_editor()
//
// Description:
//     Displays a form for creating/editing an vlan campus record.
//     If $form is a valid ID, it is used to display an existing
//     record for editing.  "Save" button calls the ws_save() function.
//////////////////////////////////////////////////////////////////////////////
function ws_editor($window_name, $form='') {
    global $conf, $self, $onadb;
    global $font_family, $color, $style, $images;
    $window = array();

    // Check permissions
    if (!auth('vlan_add')) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);

    // Load an existing host record (and associated info) if $form is a vlan_campus_id
    if (is_numeric($form['vlan_campus_id']))
        list($status, $rows, $vlan_campus) = ona_get_vlan_campus_record(array('id' => $form['vlan_campus_id']));
    else
        list($status, $rows, $vlan_campus) = ona_get_vlan_campus_record(array('name' => $form['vlan_campus_name']));


    // Escape data for display in html
    foreach(array_keys((array)$vlan_campus) as $key) { $vlan_campus[$key] = htmlentities($vlan_campus[$key], ENT_QUOTES, $conf['php_charset']); }


    // Set the window title:
    $window['title'] = "Add VLAN campus";
    if ($vlan_campus['id'])
        $window['title'] = "Edit VLAN campus";


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

EOL;

    // Define the window's inner html
    $window['html'] = <<<EOL

    <!-- Vlan Campus Edit Form -->
    <form id="{$window_name}_edit_form" onSubmit="return false;">
    <input type="hidden" name="vlan_campus_id" value="{$vlan_campus['id']}">
    <input type="hidden" name="js" value="{$form['js']}">
    <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">

        <!-- VLAN CAMPUS RECORD -->
        <tr>
            <td align="left" nowrap="true"><b><u>Vlan Campus Record</u></b>&nbsp;</td>
            <td class="padding" align="left" width="100%">&nbsp;</td>
        </tr>

        <tr>
            <td class="input_required" align="right" nowrap="true">
                Vlan Campus Name
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="name"
                    alt="Vlan Campus Name"
                    value="{$vlan_campus['name']}"
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
//     Creates/updates a VLAN record.
//////////////////////////////////////////////////////////////////////////////
function ws_save($window_name, $form='') {
    global $base, $include, $conf, $self, $onadb;

    // Check permissions
    if (! (auth('advanced')) ) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = '';

    // Validate input
    if (!$form['name']) {
        $response->addScript("alert('Please provide a campus name to continue!');");
        return($response->getXML());
    }

    // Decide if we're editing or adding
    $module = 'vlan_campus_add';
    if ($form['vlan_campus_id']) {
        $module = 'vlan_campus_modify';
        $form['set_name'] = $form['name'];
        $form['name'] = $form['vlan_campus_id'];
    }

    // If there's no "refresh" javascript, add a command to view the new host
    if (!preg_match('/\w/', $form['js']))
        $form['js'] = "xajax_window_submit('search_results', 'search_form_id=>vlan_campus_search_form,all_flag=>1');";




//        $form['js'] = "xajax_window_submit('work_space', 'xajax_window_submit(\'display_vlan_campus\', \'vlan_campus_id=>".$form['vlan_campus_id']."\', \'display\')');";

    // Run the module
    list($status, $output) = run_module($module, $form);

    // If the module returned an error code display a popup warning
    if ($status)
        $js .= "alert('Save failed. ". preg_replace('/[\s\']+/', ' ', $self['error']) . "');";
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
//     Deletes an vlan record.  $form should be an array with an 'vlan_id'
//     field.
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
    list($status, $output) = run_module('vlan_campus_del', array('name' => $form['vlan_campus_id'], 'commit' => 'Y'));

    // If the module returned an error code display a popup warning
    if ($status) { $js .= "alert('Delete failed. " . preg_replace('/[\s\']+/', ' ', $self['error']) . "');"; }
    else {
        // If there's (refresh) js, send it to the browser
        if ($form['js']) { $js .= $form['js']; }
    }

    // Return an XML response
    $response->addScript($js);
    return($response->getXML());
}




?>
