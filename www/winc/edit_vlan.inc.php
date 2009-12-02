<?php



//////////////////////////////////////////////////////////////////////////////
// Function: ws_editor()
//
// Description:
//     Displays a form for creating/editing an vlan record.
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

    // Load an existing vlan if $form is a
    if (is_array($form)) {
        list($status, $rows, $record) = ona_get_vlan_record(array('id' => $form['vlan_id']));
        if ($rows) {
            list($status, $rows, $vlan_campus) = ona_get_vlan_campus_record(array('id' => $record['vlan_campus_id']));
            $record['vlan_campus_id']   = $vlan_campus['id'];
            $record['vlan_campus_name'] = $vlan_campus['name'];
        }
        else
            $record['vlan_campus_name'] = $form['vlan_campus_name'];
    }


    // Escape data for display in html
    foreach(array_keys((array)$record) as $key) { $record[$key] = htmlentities($record[$key], ENT_QUOTES, $conf['php_charset']); }



    // Set the window title:
    $window['title'] = "Add VLAN";
    if ($record['id'])
        $window['title'] = "Edit VLAN";

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

        suggest_setup('vlan_edit',     'suggest_vlan_edit');
        el('{$window_name}_edit_form').onsubmit = function() { return false; };

EOL;

    // Define the window's inner html
    $window['html'] = <<<EOL

    <!-- Vlan Edit Form -->
    <form id="{$window_name}_edit_form" onSubmit="return false;">
    <input type="hidden" name="vlan_id" value="{$record['id']}">
    <input type="hidden" name="vlan_campus_name" value="{$form['vlan_campus_name']}">
    <input type="hidden" name="js" value="{$form['js']}">
    <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">

        <!-- VLAN CAMPUS RECORD -->
        <tr>
            <td align="left" nowrap="true"><b><u>Vlan Record</u></b>&nbsp;</td>
            <td class="padding" align="left" width="100%">&nbsp;</td>
        </tr>


        <tr>
            <td class="input_required" align="right" nowrap="true">
                Vlan Campus
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    id="vlan_edit"
                    name="campus"
                    alt="Vlan Campus"
                    value="{$record['vlan_campus_name']}"
                    class="edit"
                    type="text"
                    size="27" maxlength="255"
                >
                <div id="suggest_vlan_edit" class="suggest"></div>
            </td>
        </tr>

        <tr>
            <td class="input_required" align="right" nowrap="true">
                Vlan Name
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="name"
                    alt="Vlan Name"
                    value="{$record['name']}"
                    class="edit"
                    type="text"
                    size="27" maxlength="255"
                >
            </td>
        </tr>

        <tr>
            <td class="input_required" align="right" nowrap="true">
                Vlan Number
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="number"
                    alt="Vlan Number"
                    value="{$record['number']}"
                    class="edit"
                    type="text"
                    size="6" maxlength="10"
                >
            </td>
        </tr>
EOL;

    if (!$record['id']) {
        $window['html'] .= <<<EOL
        <tr>
            <td align="right" nowrap="true">
                &nbsp;
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="keepadding"
                    alt="Keep adding more VLANS"
                    type="checkbox"
                > Keep adding more VLANS
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
    if (!$form['campus'] or !$form['name'] or !$form['number']) {
        $response->addScript("alert('Please complete all fields to continue!');");
        return($response->getXML());
    }
    // Validate zone is valid
    list($status, $rows, $campus)  = ona_get_vlan_campus_record(array('name'  => $form['campus']));
    if ($status or !$rows) {
        $response->addScript("alert('Invalid VLAN campus!');");
        return($response->getXML());
    }

    // Decide if we're editing or adding
    $module = 'vlan_add';
    if ($form['vlan_id']) {
        $module = 'vlan_modify';
        $form['set_name'] = $form['name'];
        $form['set_campus'] = $form['campus'];
        $form['set_number'] = $form['number'];
        $form['vlan'] = $form['vlan_id'];
    }

    // If there's no "refresh" javascript, add a command to view the new host
    if (!preg_match('/\w/', $form['js']))
        $form['js'] = "xajax_window_submit('work_space', 'xajax_window_submit(\'display_vlan_campus\', \'vlan_campus_id=>{$campus['id']}\', \'display\')');";

    // Run the module
    list($status, $output) = run_module($module, $form);

    // If the module returned an error code display a popup warning
    if ($status)
        $js .= "alert('Save failed. ". preg_replace('/[\s\']+/', ' ', $self['error']) . "');";
    else {
        // if they have checked the keep adding box then dont remove the window
        if (!$form['keepadding'])
            $js .= "removeElement('{$window_name}');";
        else {
            $js .= "el('statusinfo_{$window_name}').innerHTML = 'Previously added:<br>\'{$form['name']}\' to campus: {$form['campus']}';";
        }

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
    list($status, $output) = run_module('vlan_del', array('vlan' => $form['vlan_id'], 'commit' => 'Y'));

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
