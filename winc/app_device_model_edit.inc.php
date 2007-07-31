<?



//////////////////////////////////////////////////////////////////////////////
// Function:
//     Display Edit Form
//
// Description:
//     Displays a form for creating/editing device models.
//     If a device model id is found in $form it is used to display an existing
//     device model for editing.  When "Save" is pressed the save()
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
        'title' => 'Device Model Editor',
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

    // If we got a device model, load it for display
    $overwrite = 'no';
    if (is_numeric($form)) {
        list($status, $rows, $record) = db_get_record($onadb, 'models', array('id' => $form));
        if (!$status and $rows) { $overwrite = 'yes'; }
    }

    // Build manufacturer list
    // TODO: this needs to be made more efficent
    list($status, $rows, $manufacturer) = db_get_records($onadb, 'manufacturers','id >= 1', 'name');
    $manufacturer_list = '<option value="">&nbsp;</option>\n';
    $manufacturer['name'] = htmlentities($manufacturer['name']);
    foreach ($manufacturer as $entry) {
        $selected = "";
        // If this entry matches the record you are editing, set it to selected
        if ($entry['id'] == $record['manufacturer_id']) { $selected = "SELECTED=\"selected\""; }
        if ($entry['id']) {$manufacturer_list .= "<option {$selected} value=\"{$entry['id']}\">{$entry['name']}</option>\n";}
    }


    // Escape data for display in html
    foreach(array_keys((array)$record) as $key) {
        $record[$key] = htmlentities($record[$key], ENT_QUOTES);
    }

    // Load some html into $window['html']
    $window['html'] .= <<<EOL

    <!-- Simple device models Edit Form -->
    <form id="device_model_edit_form" onSubmit="return false;">
    <input name="id" type="hidden" value="{$record['id']}">
    <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">

        <tr>
            <td nowrap="yes" align="right">
                Manufacturer
            </td>
            <td class="padding" align="left" width="100%">
                <select id="manufacturer_id" name="manufacturer_id" class="edit" accesskey="m">
                    {$manufacturer_list}
                </select>
            </td>
        </tr>

        <tr>
            <td nowrap="yes" align="right">
                Model
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="model_description"
                    alt="Device Model Description"
                    value="{$record['name']}"
                    class="edit"
                    type="text"
                    size="30" maxlength="30"
                >
            </td>
        </tr>

        <tr>
            <td nowrap="yes" align="right">
                SNMP sysobjectid
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="snmp_sysobjectid"
                    alt="SNMP sysobjectid"
                    value="{$record['snmp_sysobjectid']}"
                    class="edit"
                    type="text"
                    size="30" maxlength="30"
                >
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
                    onClick="xajax_window_submit('{$window_name}', xajax.getFormValues('device_model_edit_form'), 'save');"
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
//     Creates/updates an device model with the info from the submitted form.
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

        // Get the model record before updating (logging)
        list($status, $rows, $original_model) = ona_get_model_record(array('id' => $form['id']));

        list($status, $rows) = db_update_record(
                                     $onadb,
                                     'models',
                                     array('id' => $form['id']),
                                     array('name' => $form['model_description'],
                                           'snmp_sysobjectid' => $form['snmp_sysobjectid'],
                                           'manufacturer_id' => $form['manufacturer_id']
                                     )
                                 );

        if ($status or !$rows) {
            $self['error'] = "ERROR => device_model_edit update ws_save()  SQL Query failed: " . $self['error'];
            printmsg($self['error'], 0);
        }
        else {
            // Get the model record after updating (logging)
            list($status, $rows, $new_model) = ona_get_model_record(array('id' => $form['id']));

            // Return the success notice
            $self['error'] = "INFO => Device Model UPDATED:{$new_model['id']}: {$new_model['name']}";

            $log_msg = "INFO => Device Model UPDATED:{$new_model['id']}: ";
            $more="";
            foreach(array_keys((array)$original_model) as $key) {
                if($original_model[$key] != $new_model[$key]) {
                    $log_msg .= $more . $key . "[" .$original_model[$key] . "=>" . $new_model[$key] . "]";
                    $more= ";";
                }
            }

            // only print to logfile if a change has been made to the record
            if($more != '') {
                printmsg($self['error'], 0);
                printmsg($log_msg, 0);
            }
        }
    }
    // If you get nothing in $form, create a new record
    else {
        $id = ona_get_next_id('models');
        if (!$id) {
            $self['error'] = "ERROR => The ona_get_next_id() call failed!";
            printmsg($self['error'], 0);
        }
        else {
            printmsg("DEBUG => ID for new device model: $id", 3);
            list($status, $rows) = db_insert_record($onadb,
                                                    "models",
                                                    array('id' => $id,
                                                        'name' => $form['model_description'],
                                                        'snmp_sysobjectid' => $form['snmp_sysobjectid'],
                                                        'manufacturer_id' => $form['manufacturer_id']
                                                        )
                                                    );
            if ($status or !$rows) {
                $self['error'] = "ERROR => device_model_edit add ws_save()  SQL Query failed: " . $self['error'];
                printmsg($self['error'], 0);
            }
            else {
                $self['error'] = "INFO => Device Model ADDED: {$form['model_description']} ";
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
        $js .= "xajax_window_submit('app_device_model_list', xajax.getFormValues('app_device_model_list_filter_form'), 'display_list');";
    }

    // Return some javascript to the browser
    $response->addScript($js);
    return($response->getXML());
}



?>