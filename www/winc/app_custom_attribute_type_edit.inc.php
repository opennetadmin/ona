<?php



//////////////////////////////////////////////////////////////////////////////
// Function:
//     Display Edit Form
//
// Description:
//     Displays a form for creating/editing custom attribute types.
//     If a custom attribute type id is found in $form it is used to display an existing
//     type for editing.  When "Save" is pressed the save()
//     function is called.
//////////////////////////////////////////////////////////////////////////////
function ws_editor($window_name, $form='') {
    global $conf, $self, $onadb;
    global $font_family, $color, $style, $images;

    // Check permissions
    if (!auth('advanced')) {
        $response = new xajaxResponse();
        $response->script("alert('Permission denied!');");
        return $response;
    }

    // Set a few parameters for the "results" window we're about to create
    $window = array(
        'title' => 'Custom Attribute Editor',
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

    // If we got type, load it for display
    $overwrite = 'no';
    if (is_numeric($form)) {
        list($status, $rows, $record) = db_get_record($onadb,
                                            'custom_attribute_types',
                                            array('id' => $form));
        if (!$status and $rows) { $overwrite = 'yes'; }
    }


    // Escape data for display in html
    foreach(array_keys((array)$record) as $key) {$record[$key] = htmlentities($record[$key], ENT_QUOTES, $conf['php_charset']);}

    // Load some html into $window['html']
    $window['html'] .= <<<EOL

    <!-- Simple class types Edit Form -->
    <form id="custom_attribute_type_edit_form" onSubmit="return false;">
    <input name="id" type="hidden" value="{$record['id']}">
    <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">
        <tr>
            <td align="right">
                Name
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="cust_attrib_type_name"
                    alt="Custom Attribute Type Name"
                    value="{$record['name']}"
                    class="edit"
                    type="text"
                    size="30" maxlength="63"
                >
            </td>
        </tr>

        <tr>
            <td align="right">
                Field Validaiton Rule
            </td>
            <td class="padding" align="left" width="100%">
                <textarea
                    name="field_validation_rule"
                    alt="Field Validaiton Rule"
                    class="edit"
                    rows="2"
                    cols="40"
                >{$record['field_validation_rule']}</textarea>
            </td>
        </tr>

        <tr>
            <td align="right">
                Failed Rule Text
            </td>
            <td class="padding" align="left" width="100%">
                <textarea
                    name="failed_rule_text"
                    alt="Failed Rule Text"
                    class="edit"
                    rows="2"
                    cols="40"
                >{$record['failed_rule_text']}</textarea>
            </td>
        </tr>

        <tr>
            <td align="right">
                Notes
            </td>
            <td class="padding" align="left" width="100%">
                <textarea
                    name="notes"
                    alt="Notes"
                    class="edit"
                    rows="2"
                    cols="40"
                >{$record['notes']}</textarea>
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
                    accesskey=" "
                    onClick="xajax_window_submit('{$window_name}', xajax.getFormValues('custom_attribute_type_edit_form'), 'save');"
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
//     Creates/updates an manufacturer type with the info from the submitted form.
//////////////////////////////////////////////////////////////////////////////
function ws_save($window_name, $form='') {
    global $conf, $self, $onadb;

    // Check permissions
    if (!auth('advanced')) {
        $response = new xajaxResponse();
        $response->script("alert('Permission denied!');");
        return $response;
    }

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = '';


    // Strip whitespace
    // FIXME: (PK) What about SQL injection attacks?  This is a user-entered string...
    $form['cust_attrib_type_name'] = trim($form['cust_attrib_type_name']);

    // Don't insert a string of all white space!
    if($form['cust_attrib_type_name'] == "") {
        $self['error'] = "ERROR => Blank names not allowed.";
        printmsg($self['error'], 0);
        $response->script("alert('{$self['error']}');");
        return $response;
    }


    // If you get a numeric in $form, update the record
    if (is_numeric($form['id'])) {
        // Get the manufacturer record before updating (logging)
        list($status, $rows, $original_manufacturer) = ona_get_custom_attribute_type_record(array('id' => $form['id']));

        if ($form['cust_attrib_type_name'] !== $original_type['name']) {
            list($status, $rows) = db_update_record(
                                         $onadb,
                                         'custom_attribute_types',
                                         array('id' => $form['id']),
                                         array('name' => $form['cust_attrib_type_name'],
                                               'field_validation_rule' => $form['field_validation_rule'],
                                               'failed_rule_text' => $form['failed_rule_text'],
                                               'notes' => $form['notes']
                                              )
                                     );
            if ($status or !$rows) {
                $self['error'] = "ERROR => cust_attrib_type edit update ws_save() failed: " . $self['error'];
                printmsg($self['error'], 0);
                $response->script("alert('{$self['error']}');");
            }
            else {
                // Get the manufacturer record after updating (logging)
                list($status, $rows, $new_type) = ona_get_custom_attribute_type_record(array('id' => $form['id']));

                // Return the success notice
                $self['error'] = "INFO => Custom Attribute Type UPDATED:{$new_type['id']}: {$new_type['name']}";
                printmsg($self['error'], 0);
                $log_msg = "INFO => Custom Attribute Type UPDATED:{$new_type['id']}: name[{$original_type['name']}=>{$new_type['name']}]";
                printmsg($log_msg, 0);
            }
        }
    }
    // If you get nothing in $form, create a new record
    else {
        $id = ona_get_next_id('custom_attribute_types');

        if (!$id) {
            $self['error'] = "ERROR => The ona_get_next_id('custom_attribute_types') call failed!";
            printmsg($self['error'], 0);
        }
        else {
            list($status, $rows) = db_insert_record($onadb,
                                        "custom_attribute_types",
                                        array('id' => $id,
                                              'name' => $form['cust_attrib_type_name'],
                                              'field_validation_rule' => $form['field_validation_rule'],
                                              'failed_rule_text' => $form['failed_rule_text'],
                                              'notes' => $form['notes']
                                             )
                                   );

            if ($status or !$rows) {
                $self['error'] = "ERROR => Custom attribute type add ws_save() failed: " . $self['error'];
                printmsg($self['error'], 0);
            }
            else {
                $self['error'] = "INFO => Custom Attribute Type ADDED: {$form['cust_attrib_type_name']} ";
                printmsg($self['error'], 0);
            }
        }
    }

    // If the module returned an error code display a popup warning
    if ($status or !$rows) {
        $js .= "alert(\"Save failed. ". trim($self['error']) . " (Hint: Does the name you're trying to insert already exist?)\");";
    }
    else {
        $js .= "removeElement('{$window_name}');";
        $js .= "xajax_window_submit('app_custom_attribute_type_list', xajax.getFormValues('app_custom_attribute_type_list_filter_form'), 'display_list');";
    }

    // Return some javascript to the browser
    $response->script($js);
    return $response;
}



?>
