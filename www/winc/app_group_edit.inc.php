<?php



//////////////////////////////////////////////////////////////////////////////
// Function:
//     Display Edit Form
//
// Description:
//     Displays a form for creating/editing a group.
//     If a group id is found in $form it is used to display an existing
//     group for editing.  When "Save" is pressed the save()
//     function is called.
//////////////////////////////////////////////////////////////////////////////
function ws_editor($window_name, $form='') {
    global $conf, $self, $onadb;
    global $font_family, $color, $style, $images;

    // Check permissions
    if (!auth('user_admin')) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }

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

    // Set a few parameters for the "results" window we're about to create
    $window = array(
        'title' => 'Group Editor',
        'html'  => '',
        'js'    => '',
    );

    // If we got a group ID, load it for display
    if (is_string($form) and $form) {
        list($status, $rows, $record) = db_get_record($onadb, 'groups', array('id' => $form));
    }

    // Build some html for selecting the groups the group is in
    $group_check_list = "";
// FIXME: MP: this is strange.. why not get the group list from the database?


//     foreach (array_keys($_SESSION['ona']['auth']['groups']) as $group) {
//         $group_id = $_SESSION['ona']['auth']['groups'][$group];
//         $group_check_list .= <<<EOL
//             <input type="checkbox" name="groups[]" value="{$group}" checked="true"> {$group}<br>
// EOL;
//     }

    // Escape data for display in html
    foreach(array_keys($record) as $key) { $record[$key] = htmlentities($record[$key], ENT_QUOTES); }

    // Load some html into $window['html']
    $window['html'] .= <<<EOL

    <!-- Simple Group Edit Form -->
    <form id="group_edit_form" onSubmit="return false;">
    <input type="hidden" name="id" value="{$record['id']}">
    <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">
        <tr>
            <td align="right">
                Group
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="name"
                    alt="Group name"
                    value="{$record['name']}"
                    class="edit"
                    type="text"
                    size="20" maxlength="32"
                >
            </td>
        </tr>

        <tr>
            <td align="right">
                Description
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="description"
                    alt="Group description"
                    value="{$record['description']}"
                    class="edit"
                    type="text"
                    size="32" maxlength="128"
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
                    onClick="xajax_window_submit('{$window_name}', xajax.getFormValues('group_edit_form'), 'save');"
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
//     Creates/updates a group with the info from the submitted form.
//////////////////////////////////////////////////////////////////////////////
function ws_save($window_name, $form='') {
    global $conf, $self, $onadb;

    // Check permissions
    if (!auth('user_admin')) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = '';

    // Validate input
    if (!$form['name']) {
        $js .= "alert('Error! All fields are required!');";
        $response->addScript($js);
        return($response->getXML());
    }
    if (!preg_match('/^[A-Za-z0-9.\-_]+$/', $form['name'])) {
        $js .= "alert('Invalid group name! Valid characters: A-Z 0-9 .-_');";
        $response->addScript($js);
        return($response->getXML());
    }

    //MP: zero out the level for now
    //TODO: fix or remove level at some point
    $form['level'] = 0;

    // Create a new record?
    if (!$form['id']) {
        list ($status, $rows) = db_insert_record(
            $onadb,
            'groups',
            array(
                'name' => $form['name'],
                'description' => $form['description'],
                'level' => $form['level']
            )
        );

        if ($status or !$rows) {
            $self['error'] = "ERROR => group_edit add ws_save()  SQL Query failed: " . $self['error'];
            printmsg($self['error'], 0);
        }
        else {
            $self['error'] = "INFO => Group ADDED: {$form['name']} ";
            printmsg($self['error'], 0);
        }

    }

    // Update an existing record?
    else {
        list($status, $rows, $record) = db_get_record($onadb, 'groups', array('id' => $form['id']));
        if ($rows != 1 or $record['id'] != $form['id']) {
            $js .= "alert('Error! The record requested could not be loaded from the database!');";
            $response->addScript($js);
            return($response->getXML());
        }

        list ($status, $rows) = db_update_record(
            $onadb,
            'groups',
            array(
                'id'     => $form['id'],
            ),
            array(
                'name'        => $form['name'],
                'description' => $form['description']
            )
        );

        if ($status or !$rows) {
            $self['error'] = "ERROR => group_edit update ws_save()  SQL Query failed: " . $self['error'];
            printmsg($self['error'], 0);
        }
        else {
            list($status, $rows, $new_record) = db_get_record($onadb, 'groups', array('id' => $form['id']));

            // Return the success notice
            $self['error'] = "INFO => Group UPDATED:{$record['id']}: {$record['name']}";

            $log_msg = "INFO => Group UPDATED:{$record['id']}: ";
            $more="";
            foreach(array_keys($record) as $key) {
                if($record[$key] != $new_record[$key]) {
                    $log_msg .= $more . $key . "[" .$record[$key] . "=>" . $new_record[$key] . "]";
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

    // If the module returned an error code display a popup warning
    if ($status) {
        $js .= "alert('Save failed. Contact the webmaster if this problem persists.');";
    }
    else {
        $js .= "removeElement('{$window_name}');";
        $js .= "xajax_window_submit('app_group_list', xajax.getFormValues('app_group_list_filter_form'), 'display_list');";
    }

    // Insert the new table into the window
    $response->addScript($js);
    return($response->getXML());
}


?>