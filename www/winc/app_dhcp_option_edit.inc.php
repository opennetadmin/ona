<?php



//////////////////////////////////////////////////////////////////////////////
// Function:
//     Display Edit Form
//
// Description:
//     Displays a form for creating/editing dhcp options.
//     If a dhcp option id is found in $form it is used to display an existing
//     dhcp option for editing.  When "Save" is pressed the save()
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
        'title' => 'DHCP Option Editor',
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

    // If we got an option, load it for display
    $overwrite = 'no';
    if (is_numeric($form)) {
        list($status, $rows, $record) = db_get_record($onadb, 'dhcp_options', array('id' => $form));
        if (!$status and $rows) { $overwrite = 'yes'; }
    }

    // Internal tag type array, there is no table for this
    $type = array("L"=> "IP Address List",
                      "S"=> "String",
                      "N"=> "Numeric",
                      "I"=> "IP Address",
                      "B"=> "Boolean");


    // Build tag type list
    while ($tag = current($type)) {
        $selected = "";
        // If this entry matches the record you are editing, set it to selected
        if (key($type) == $record['type']) { $selected = "SELECTED=\"selected\""; }
        if (key($type)) {$type_list .= "<option {$selected} value=\"". key($type)."\">{$tag}</option>\n";}
        next($type);
    }

    // Escape data for display in html
    foreach(array_keys((array)$record) as $key) {$record[$key] = htmlentities($record[$key], ENT_QUOTES, $conf['php_charset']);}

    // Load some html into $window['html']
    $window['html'] .= <<<EOL

    <!-- Simple Edit Form -->
    <form id="dhcp_option_edit_form" onSubmit="return false;">
    <input name="id" type="hidden" value="{$record['id']}">
    <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">
        <tr>
            <td align="right" nowrap="true">
                Display Name
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="display_name"
                    alt="Description"
                    value="{$record['display_name']}"
                    class="edit"
                    type="text"
                    size="30" maxlength="30"
                >
            </td>
        </tr>

        <tr>
            <td align="right">
                Option Name
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="name"
                    alt="Name"
                    value="{$record['name']}"
                    class="edit"
                    type="text"
                    size="30" maxlength="30"
                >
            </td>
        </tr>

        <tr>
            <td align="right">
                Number
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="number"
                    alt="DHCP Number"
                    value="{$record['number']}"
                    class="edit"
                    type="text"
                    size="5" maxlength="10"
                >
            </td>
        </tr>

        <tr>
            <td nowrap="yes" align="right">
                Option type
            </td>
            <td class="padding" align="left" width="100%">
                <select id="type" name="type" class="edit" accesskey="t">
                    {$type_list}
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
                    accesskey=" "
                    onClick="xajax_window_submit('{$window_name}', xajax.getFormValues('dhcp_option_edit_form'), 'save');"
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
//     Creates/updates an option with the info from the submitted form.
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

    if (!is_numeric($form['number'])) {
        $self['error'] = "ERROR => dhcp_option_editor() Number value required";
        printmsg($self['error'], 0);
    }

    // If you get a numeric in $form, update the record
    if (is_numeric($form['id'])) {

        // Get the option record before updating (logging)
        list($status, $rows, $original_option) = ona_get_dhcp_option_record(array('id' => $form['id']));

        list($status, $rows) = db_update_record(
                                     $onadb,
                                     'dhcp_options',
                                     array('id' => $form['id']),
                                     array('display_name' => $form['display_name'],
                                           'type' => $form['type'],
                                           'number' => $form['number'],
                                           'name' => $form['name'])
                                 );

        if ($status or !$rows) {
            $self['error'] = "ERROR => dhcp_option update ws_save()  SQL Query failed: " . $self['error'];
            printmsg($self['error'], 0);
        }
        else {
            // Get the record after updating (logging)
            list($status, $rows, $new_option) = ona_get_dhcp_option_record(array('id' => $form['id']));

            // Return the success notice
            $self['error'] = "INFO => DHCP Option UPDATED:{$new_option['id']}: {$new_option['name']}";

            $log_msg = "INFO => DHCP Option UPDATED:{$new_option['id']}: ";
            $more="";
            foreach(array_keys($original_option) as $key) {
                if($original_option[$key] != $new_option[$key]) {
                    $log_msg .= $more . $key . "[" .$original_option[$key] . "=>" . $new_option[$key] . "]";
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
        $id = ona_get_next_id('dhcp_options');
        if (!$id) {
            $self['error'] = "ERROR => The ona_get_next_id() call failed!";
            printmsg($self['error'], 0);
        }
        else {
            printmsg("DEBUG => ID for new dhcp option: $id", 3);
            list($status, $rows) = db_insert_record($onadb,
                                                "dhcp_options",
                                                array('id' => $id,
                                                      'display_name' => $form['display_name'],
                                                      'type' => $form['type'],
                                                      'number' => $form['number'],
                                                      'name' => $form['name'])
                                               );
            if ($status or !$rows) {
                $self['error'] = "ERROR => dhcp_option_edit add ws_save()  SQL Query failed: " . $self['error'];
                printmsg($self['error'], 0);
            }
            else {
                $self['error'] = "INFO => DHCP Option ADDED: {$form['name']} ";
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
        $js .= "xajax_window_submit('app_dhcp_option_list', xajax.getFormValues('app_dhcp_option_list_filter_form'), 'display_list');";
    }

    // Return some javascript to the browser
    $response->addScript($js);
    return($response->getXML());
}



?>
