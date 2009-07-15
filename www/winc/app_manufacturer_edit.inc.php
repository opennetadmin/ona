<?php



//////////////////////////////////////////////////////////////////////////////
// Function:
//     Display Edit Form
//
// Description:
//     Displays a form for creating/editing manufacturer types.
//     If a manufacturer type id is found in $form it is used to display an existing
//     manufacturer type for editing.  When "Save" is pressed the save()
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
        'title' => 'Manufacturer Editor',
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

    // If we got a manufacturer, load it for display
    $overwrite = 'no';
    if (is_numeric($form)) {
        list($status, $rows, $record) = db_get_record($onadb, 
                                            'manufacturers', 
                                            array('id' => $form));
        if (!$status and $rows) { $overwrite = 'yes'; }
    }


    // Escape data for display in html
    foreach(array_keys((array)$record) as $key) {$record[$key] = htmlentities($record[$key], ENT_QUOTES);}

    // Load some html into $window['html']
    $window['html'] .= <<<EOL

    <!-- Simple class types Edit Form -->
    <form id="manufacturer_edit_form" onSubmit="return false;">
    <input name="id" type="hidden" value="{$record['id']}">
    <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">
        <tr>
            <td align="right">
                Name
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="manufacturer_name"
                    alt="Manufacturer Name"
                    value="{$record['name']}"
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
                    accesskey=" "
                    onClick="xajax_window_submit('{$window_name}', xajax.getFormValues('manufacturer_edit_form'), 'save');"
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
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }
        
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = '';


    // Strip whitespace
    // FIXME: (PK) What about SQL injection attacks?  This is a user-entered string...
    $form['manufacturer_name'] = trim($form['manufacturer_name']);
    
    // Don't insert a string of all white space!
    if(trim($form['manufacturer_name']) == "") {
        $self['error'] = "ERROR => Blank names not allowed.";
        printmsg($self['error'], 0);
        $response->addScript("alert('{$self['error']}');");
        return($response->getXML());
    }


    // If you get a numeric in $form, update the record
    if (is_numeric($form['id'])) {
        // Get the manufacturer record before updating (logging)
        list($status, $rows, $original_manufacturer) = ona_get_manufacturer_record(array('id' => $form['id']));

        if ($form['manufacturer_name'] !== $original_manufacturer['name']) {
            list($status, $rows) = db_update_record(
                                         $onadb,
                                         'manufacturers',
                                         array('id' => $form['id']),
                                         array('name' => $form['manufacturer_name'])
                                     );
            if ($status or !$rows) {
                $self['error'] = "ERROR => manufacturer_edit update ws_save() failed: " . $self['error'];
                printmsg($self['error'], 0);
                $response->addScript("alert('{$self['error']}');");
            }
            else {
                // Get the manufacturer record after updating (logging)
                list($status, $rows, $new_manufacturer) = ona_get_manufacturer_record(array('id' => $form['id']));
    
                // Return the success notice
                $self['error'] = "INFO => Manufacturer UPDATED:{$new_manufacturer['id']}: {$new_manufacturer['name']}";
                printmsg($self['error'], 0);
                $log_msg = "INFO => Manufacturer UPDATED:{$new_manufacturer['id']}: name[{$original_manufacturer['name']}=>{$new_manufacturer['name']}]";
                printmsg($log_msg, 0);
            }
        }
    }
    // If you get nothing in $form, create a new record
    else {
        $id = ona_get_next_id('manufacturers');

        if (!$id) {
            $self['error'] = "ERROR => The ona_get_next_id() call failed!";
            printmsg($self['error'], 0);
        }
        else {
            printmsg("DEBUG => id for new manufacturer record: $id", 3);
            list($status, $rows) = db_insert_record($onadb, 
                                        "manufacturers", 
                                        array('id' => $id, 
                                        'name' => trim($form['manufacturer_name'])));

            if ($status or !$rows) {
                $self['error'] = "ERROR => manufacturer_edit add ws_save() failed: " . $self['error'];
                printmsg($self['error'], 0);
            }
            else {
                $self['error'] = "INFO => Manufacturer ADDED: {$form['manufacturer_name']} ";
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
        $js .= "xajax_window_submit('app_manufacturer_list', xajax.getFormValues('app_manufacturer_list_filter_form'), 'display_list');";
    }

    // Return some javascript to the browser
    $response->addScript($js);
    return($response->getXML());
}



?>