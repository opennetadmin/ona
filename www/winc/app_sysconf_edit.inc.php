<?php



//////////////////////////////////////////////////////////////////////////////
// Function:
//     Display Edit Form
//
// Description:
//     Displays a form for creating/editing sys config entries.
//     If a entry name is found in $form it is used to display an existing
//     entry for editing.  When "Save" is pressed the save()
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
        'title' => 'Sys Config Editor',
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
        el('conf_name').focus();
EOL;

    // If we got a class type, load it for display
    $overwrite = 'no';
    $edit=0;

    list($status, $rows, $record) = db_get_record($onadb, 'sys_config', array('name' => $form));


    if (!$status and $rows) {
        $overwrite = 'yes';
        $edit=1;
    }



    // Escape data for display in html
    foreach(array_keys((array)$record) as $key) {
        $record[$key] = htmlentities($record[$key], ENT_QUOTES, $conf['php_charset']);
    }

    // Load some html into $window['html']
    $window['html'] .= <<<EOL

    <!-- Simple class types Edit Form -->
    <form id="sysconf_edit_form" onSubmit="return false;" autocomplete="off">
    <input name="id" type="hidden" value="{$record['name']}">
    <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">

        <tr>
            <td class="input_required" align="right">
                Name
            </td>
EOL;

    if (!$edit) {
    $window['html'] .= <<<EOL

            <td class="padding" align="left" width="100%">
                <input
                    id="conf_name"
                    name="name"
                    alt="Name"
                    value="{$record['name']}"
                    class="edit"
                    type="text"
                    size="30" maxlength="30"
                >
            </td>

EOL;
    } else {
    $window['html'] .= <<<EOL
            <td class="padding" align="left" width="100%">
                <input name="name" type="hidden" value="{$record['name']}">{$record['name']}</td>
EOL;
    }

    $window['html'] .= <<<EOL
        </tr>

        <tr>
            <td class="input_required" align="right">
                Value
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="value"
                    alt="Value"
                    value="{$record['value']}"
                    class="edit"
                    type="text"
                    size="30" maxlength="256"
                >
            </td>
        </tr>

        <tr>
            <td class="input_required" align="right">
                Description
            </td>
            <td class="padding" align="left" width="100%">
                <textarea name="description" class="edit" cols="40" rows="2">{$record['description']}</textarea>
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
                    onClick="xajax_window_submit('{$window_name}', xajax.getFormValues('sysconf_edit_form'), 'save');"
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
//     Creates/updates an sys config entry with the info from the submitted form.
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
    $form['value'] = trim($form['value']);
    $form['name']  = trim($form['name']);

    // Don't insert a string of all white space!
    if(trim($form['name']) == "") {
        $self['error'] = "ERROR => Blank names not allowed.";
        printmsg($self['error'], 0);
        $response->script("alert('{$self['error']}');");
        return $response;
    }


    // If you get a numeric in $form, update the record
    if ($form['id']) {

        // Get the record before updating (logging)
        list($status, $rows, $original_sysconf) = ona_get_record(array('name' => $form['id']), 'sys_config');

        // Bail if it is a non editable entry
        if ($original_sysconf['editable'] == 0) {
            $self['error'] = "ERROR => This system config entry is not editable.";
            printmsg($self['error'], 0);
            $response->script("alert('{$self['error']}');");
            return $response;
        }

        if($form['value'] !== $original_sysconf['value'] or $form['description'] !== $original_sysconf['description']) {
            list($status, $rows) = db_update_record(
                                         $onadb,
                                         'sys_config',
                                         array('name' => $form['name']),
                                         array('value' => $form['value'],'description' => $form['description'])
                                     );
            if ($status or !$rows) {
                $self['error'] = "ERROR => sys_config_edit update ws_save() failed: " . $self['error'];
                printmsg($self['error'], 0);
            }
            else {
                // Get the record after updating (logging)
                list($status, $rows, $new_sysconf) = ona_get_record(array('name' => $form['id']), 'sys_config');

                // Return the success notice
                $self['error'] = "INFO => Sys_config UPDATED:{$new_sysconf['name']}: {$new_sysconf['value']}";
                printmsg($self['error'], 0);
                $log_msg = "INFO => Sys_config UPDATED:{$new_sysconf['name']} NAME[{$original_sysconf['name']}]{$original_sysconf['value']}=>{$new_sysconf['value']}";
                printmsg($log_msg, 0);
            }
        } else {
            $self['error'] = "INFO => You have not made a change to the value or description.";
            printmsg($self['error'], 0);
            $response->script("alert('{$self['error']}');");
            return $response;
        }
    }
    // If you get nothing in $form, create a new record
    else {
            // check for an existing entry like this
            list($status, $rows, $test) = ona_get_record(array('name' => $form['name']), 'sys_config');
            if ($rows) {
                $self['error'] = "ERROR => The name you are trying to use already exists.";
                printmsg($self['error'], 0);
                $response->script("alert('{$self['error']}');");
                return $response;
            }

            list($status, $rows) = db_insert_record($onadb,
                                            "sys_config",
                                            array('name' => $form['name'],
                                                  'value' => $form['value'],
                                                  'description' => $form['description'],
                                                  'editable' => 1,
                                                  'deleteable' => 1)
                                            );

            if ($status or !$rows) {
                $self['error'] = "ERROR => Sys_config_edit add ws_save() failed: " . $self['error'];
                printmsg($self['error'], 0);
            }
            else {
                $self['error'] = "INFO => Sys_config ADDED: {$form['name']} ";
                printmsg($self['error'], 0);
            }
   }

    // If the module returned an error code display a popup warning
    if ($status) {
        $js .= "alert(\"Save failed. ". trim($self['error']) . "\");";
    }
    else {
        $js .= "removeElement('{$window_name}');";
        $js .= "xajax_window_submit('app_sysconf_list', xajax.getFormValues('app_sysconf_list_filter_form'), 'display_list');";
    }

    // Return some javascript to the browser
    $response->script($js);
    return $response;
}



?>
