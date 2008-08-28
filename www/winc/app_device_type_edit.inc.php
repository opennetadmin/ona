<?php



//////////////////////////////////////////////////////////////////////////////
// Function:
//     Display Edit Form
//
// Description:
//     Displays a form for creating/editing device types.
//     If a device type id is found in $form it is used to display an existing
//     device type for editing.  When "Save" is pressed the save()
//     function is called.
//////////////////////////////////////////////////////////////////////////////
function ws_editor($window_name, $form='') {
    global $conf, $self, $onadb;
    global $font_family, $color, $style, $images;

    // Check permissions
    if (!auth('advanced')) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }

    // Set a few parameters for the "results" window we're about to create
    $window = array(
        'title' => 'Device Type Editor',
        'html'  => '',
        'js'    => '',
    );

    $window['js'] .= <<<EOL
        /* Put a minimize icon in the title bar */
        el('{$window_name}_title_r').innerHTML =
            '&nbsp;<a onClick="toggle_window(\'{$window_name}\');" title="Minimize window" style="cursor: pointer;"><img src="{$images}/icon_minimize.gif" border="0" /></a>' +
            el('{$window_name}_title_r').innerHTML;

        /* Put a help icon in the title bar */
        el('{$window_name}_title_r').innerHTML =
            '&nbsp;<a href="{$_ENV['help_url']}{$window_name}" target="null" title="Help" style="cursor: pointer;"><img src="{$images}/silk/help.png" border="0" /></a>' +
            el('{$window_name}_title_r').innerHTML;
EOL;

    // If we got a device type, load it for display
    $overwrite = 'no';
    if (is_numeric($form)) {
        list($status, $rows, $record) = db_get_record($onadb, 'device_types', array('id' => $form));
        if (!$status and $rows) { $overwrite = 'yes'; }
    }

    // Build model list
    // TODO: this needs to be made more efficent
    list($status, $rows, $model) = db_get_records($onadb, 'manufacturers b, models a','a.manufacturer_id = b.id and a.id >= 1', 'b.name, a.name');
    $model['name'] = htmlentities($model['name']);
    foreach ($model as $entry) {
        $selected = "";
        list($status, $rows, $manufacturer) = ona_get_manufacturer_record(array('id' => $entry['manufacturer_id']));
        $entry['manufacturer_name'] = $manufacturer['name'];
        // If this entry matches the record you are editing, set it to selected
        if ($entry['id'] == $record['model_id']) { $selected = "SELECTED=\"selected\""; }
        if ($entry['id']) {$model_list .= "<option {$selected} value=\"{$entry['id']}\">{$entry['manufacturer_name']}, {$entry['name']}</option>\n";}
    }

    // Build role list
    // TODO: this needs to be made more efficent
    list($status, $rows, $role) = db_get_records($onadb, 'roles','id >= 1', 'name');
    $role['name'] = htmlentities($role['name']);
    foreach ($role as $entry) {
        $selected = "";
        // If this entry matches the record you are editing, set it to selected
        if ($entry['id'] == $record['role_id']) { $selected = "SELECTED=\"selected\""; }
        if ($entry['id']) {$role_list .= "<option {$selected} value=\"{$entry['id']}\">{$entry['name']}</option>\n";}
    }

    // Escape data for display in html
    foreach(array_keys((array)$record) as $key) {$record[$key] = htmlentities($record[$key], ENT_QUOTES);}

    // Load some html into $window['html']
    $window['html'] .= <<<EOL

    <!-- Simple device types Edit Form -->
    <form id="device_type_edit_form" onSubmit="return false;">
    <input name="id" type="hidden" value="{$record['id']}">
    <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">
        <tr>
            <td nowrap="yes" align="right">
                Device model
            </td>
            <td class="padding" align="left" width="100%">
                <select id="model_id" name="model_id" class="edit" accesskey="m">
                    {$model_list}
                </select>
            </td>
        </tr>

        <tr>
            <td nowrap="yes" align="right">
                Device role
            </td>
            <td class="padding" align="left" width="100%">
                <select id="role_id" name="role_id" class="edit" accesskey="r">
                    {$role_list}
                </select>
            </td>
        </tr>

        <tr>
            <td align="right" valign="top">
                &nbsp;
            </td>
            <td class="padding" align="right" width="100%">
                <input type="hidden" name="overwrite" value="{$overwrite}">
                <input class="edit" type="button" name="cancel" value="Cancel" onClick="removeElement('{$window_name}');">
                <input class="edit" type="button"
                    name="submit"
                    value="Save"
                    onClick="xajax_window_submit('{$window_name}', xajax.getFormValues('device_type_edit_form'), 'save');"
                >
            </td>
        </tr>

    </table>
    </form>

EOL;


    // Lets build a window and display the results
    return(window_open($window_name, $window));

}







//////////////////////////////////////////////////////////////////////////////
// Function:
//     Save Form
//
// Description:
//     Creates/updates an device type with the info from the submitted form.
//////////////////////////////////////////////////////////////////////////////
function ws_save($window_name, $form='') {
    global $conf, $self, $onadb;

    // Check permissions
    if (!auth('advanced')) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = '';

    // If you get a numeric in $form, update the record
    if (is_numeric($form['id'])) {

        // Get the device type record before updating (logging)
        list($status, $rows, $original_type) = ona_get_device_type_record(array('id' => $form['id']));

        list($status, $rows) = db_update_record(
                                     $onadb,
                                     'device_types',
                                     array('id' => $form['id']),
                                     array('model_id' => $form['model_id'],
                                           'role_id' => $form['role_id'])
                                 );

        if ($status or !$rows) {
            $self['error'] = "ERROR => device_type_edit update ws_save()  SQL Query failed: " . $self['error'];
            printmsg($self['error'], 0);
        }
        else {
            // Return the success notice
            $self['error'] = "INFO => Device Type UPDATED:{$original_type['id']}";
            printmsg($self['error'], 0);
          //  $self['error'] = "INFO => Device Type UPDATED:{$original_type['id']}: DEVICE_TYPE_DESCRIPTION[{$original_type['DEVICE_TYPE_DESCRIPTION']}=>{$form['device_type_description']}]";
          //  printmsg($self['error'], 0);
        }

    }
    // If you get nothing in $form, create a new record
    else {
        $id = ona_get_next_id('device_types');
        if (!$id) {
            $self['error'] = "ERROR => The ona_get_next_id() call failed!";
            printmsg($self['error'], 0);
        }
        else {
            printmsg("DEBUG => id for new device type: $id", 3);
            list($status, $rows) = db_insert_record($onadb, 'device_types', array('id' => $id, 'model_id' => $form['model_id'], 'role_id' => $form['role_id']));
            if ($status or !$rows) {
                $self['error'] = "ERROR => device_type_edit add ws_save()  SQL Query failed: " . $self['error'];
                printmsg($self['error'], 0);
            }
            else {
                $self['error'] = "INFO => Device Type ADDED: {$form['id']} ";
                printmsg($self['error'], 0);
            }
        }
    }

    // If the module returned an error code display a popup warning
    if ($status) {
        $js .= "alert('Save failed. ". trim($self['error']) . " (Hint: All fields are required!)');";
    }
    else {
        $js .= "removeElement('{$window_name}');";
        $js .= "xajax_window_submit('app_device_type_list', xajax.getFormValues('app_device_type_list_filter_form'), 'display_list');";
    }

    // Return some javascript to the browser
    $response->addScript($js);
    return($response->getXML());
}



?>