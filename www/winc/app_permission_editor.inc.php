<?php





//////////////////////////////////////////////////////////////////////////////
// Function:
//     Display Edit Form
//
// Description:
//     Displays a form for creating/editing a user.
//     If a user id is found in $form it is used to display an existing
//     user for editing.  When "Save" is pressed the save()
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

    // If an array in a string was provided, build the array and store it in $form
    $form = parse_options_string($form);

    // Set a few parameters for the "results" window we're about to create
    $window = array(
        'title' => 'Permission Editor',
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

    // If we got a group ID, load it and it's permissions
    if ($form['group_id']) {
        list($status, $rows, $group)  = db_get_record($onadb, 'groups', array('id' => $form['group_id']));
        list($status, $rows, $perms) = db_get_records($onadb, 'permission_assignments', array('group_id' => $group['id']));
        $form['type'] = 'group_id';
        $form['id'] = $group['id'];
    }
    else if ($form['user_id']) {
        list($status, $rows, $user)  = db_get_record($onadb, 'users', array('id' => $form['user_id']));
        list($status, $rows, $perms) = db_get_records($onadb, 'permission_assignments', array('user_id' => $user['id']));
        $form['type'] = 'user_id';
        $form['id'] = $user['id'];
    }

    $assigned = array();
    foreach ($perms as $perm) {
        $assigned[$perm['perm_id']] = 1;
    }

    // Select every permission so we can build a checklist of them
    list($status, $rows, $permissions) = db_get_records($onadb, 'permissions', 'id > 0', 'name');


    // Load some html into $window['html']
    $window['html'] .= <<<EOL

    <!-- Simple Permission Edit Form -->
    <form id="permission_edit_form" onSubmit="return false;">
    <input type="hidden" name="id" value="{$form['id']}">
    <input type="hidden" name="type" value="{$form['type']}">
    <table width="100%" cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding: 4px 20px;">

EOL;

    // Build the html for selecting the permissions the user/group has
    foreach ($permissions as $permission) {
        $checked = '';
        if ($assigned[$permission['id']] == 1) { $checked = 'CHECKED'; }
        // Escape data for display in html
        foreach(array_keys($permission) as $key) { $permission[$key] = htmlentities($permission[$key], ENT_QUOTES); }
        $window['html'] .= <<<EOL
        <tr><td align="left" nowrap="true">
            <input type="checkbox" name="perms[{$permission['id']}]" value="{$permission['name']}" {$checked}> {$permission['name']}
        </td><td>
            {$permission['description']}
        </td></tr>
EOL;
    }

    $window['html'] .= <<<EOL
            </td>
        </tr>

        <tr>
            <td align="right">
                &nbsp;
            </td>
            <td class="padding" align="right" width="100%">
                <input type="hidden" name="overwrite" value="{$overwrite}">
                <input class="edit" type="button" name="cancel" value="Cancel" onClick="removeElement('{$window_name}');">
                <input class="edit" type="button"
                    name="submit"
                    value="Save"
                    onClick="xajax_window_submit('{$window_name}', xajax.getFormValues('permission_edit_form'), 'save');"
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
//     Creates/updates a user or group's permissions with the info from the submitted form.
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
    $exit_status = 0;

    // This is a bit tricky because we want to make sure the user has all the permissions
    // that are checked in the form, but no others.  And of course we want to make as
    // few sql queries as possible.  It's tricky because the form only submits us the
    // permissions that were checked.

    // Get a list of every permission
    list($status, $rows, $permissions) = db_get_records($onadb, 'permissions', 'id > 0');

    // Loop through each permission
    foreach ($permissions as $permission) {
        // Get the user/group permissions for the current perm_id
        list($status, $rows, $p) = db_get_record($onadb, 'permission_assignments', array($form['type'] => $form['id'], 'perm_id' => $permission['id']));
        $exit_status += $status;

        // If the user/group is supposed to have this permission, make sure she does.
        if ($form['perms'][$permission['id']]) {
            if ($status == 0 and $rows == 0) {
                list($status, $rows) = db_insert_record($onadb, 'permission_assignments', array($form['type'] => $form['id'], 'perm_id' => $permission['id']));
                $exit_status += $status;
            }
        }
        // Otherwise, make sure she doesn't have that permission
        else {
            if ($status == 0 and $rows == 1) {
                list($status, $rows) = db_delete_records($onadb, 'permission_assignments', array($form['type'] => $form['id'], 'perm_id' => $permission['id']));
                $exit_status += $status;
            }
        }
    }


    // If the module returned an error code display a popup warning
    if ($status) {
        $js .= "alert('Save failed: " . trim($self['error']) . "');";
    }
    else {
        $js .= "removeElement('{$window_name}');";
    }

    // Return some javascript to the browser
    $response->addScript($js);
    return($response->getXML());
}



?>