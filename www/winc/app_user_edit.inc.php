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
        $response->script("alert('Permission denied!');");
        return $response;
    }

    // Set a few parameters for the "results" window we're about to create
    $window = array(
        'title' => 'User Editor',
        'html'  => '',
        'js'    => '',
    );

    $window['js'] .= <<<EOL
        el('username').focus();
EOL;

    // If we got a user ID, load it for display
    $overwrite = 'no';
    if (is_string($form) and $form) {
        list($status, $rows, $user) = db_get_record($onadb, 'users', array('id' => $form));
        if (!$status and $rows) { $overwrite = 'yes'; }
    }

    // Load the user's groups
    $user_groups = array();
    list($status, $rows, $records) = db_get_records($onadb, 'group_assignments', array('user_id' => $user['id']));
    foreach ($records as $record) {
        list($status, $rows, $g) = db_get_record($onadb, 'auth_groups', array('id' => $record['group_id']));
        $user_groups[$g['name']] = $g['id'];
    }

    // Get all the groups from the database
    list($status, $rows, $allgroups) = db_get_records($onadb, 'auth_groups', 'id > 0');

    $group_check_list = "";
    foreach ($allgroups as $group) {
        $checked='';
        if ($user_groups[$group['name']]) {$checked='checked';}
        $group_check_list .= <<<EOL
            <input type="checkbox" name="groups[{$group['name']}]" value="{$group['id']}" {$checked}> {$group['name']}<br>
EOL;
    }

    // Escape data for display in html
    foreach(array_keys($user) as $key) { $user[$key] = htmlentities($user[$key], ENT_QUOTES, $conf['php_charset']); }

    // Load some html into $window['html']
    $window['html'] .= <<<EOL

    <!-- Simple User Edit Form -->
    <form id="user_edit_form" onSubmit="return false;" autocomplete="off">
EOL;
    if ($overwrite == 'yes') {
	    $window['html'] .= <<<EOL
    <input type="hidden" name="user_id" value="{$user['id']}">
EOL;
    }

    $window['html'] .= <<<EOL
    <input id="password" type="hidden" name="password" value="{$user['password']}">
    <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">
        <tr>
            <td align="right">
                Username
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    id="username"
                    name="username"
                    alt="Username"
                    value="{$user['username']}"
                    class="edit"
                    type="text"
                    size="20" maxlength="32"
                >
            </td>
        </tr>

        <tr>
            <td align="right">
                Password
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    id="pass"
                    name="pass"
                    alt="pass"
                    value=""
                    class="edit"
                    type="password"
                    size="20" maxlength="32"
                >
            </td>
        </tr>

        <tr>
            <td align="right" valign="top">
                Groups
            </td>
            <td class="padding" align="left" width="100%">
                {$group_check_list}
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
                    onClick="if (el('pass').value != '') { el('password').value = make_md5(el('pass').value); } xajax_window_submit('{$window_name}', xajax.getFormValues('user_edit_form'), 'save');"
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
//     Creates/updates a user with the info from the submitted form.
//////////////////////////////////////////////////////////////////////////////
function ws_save($window_name, $form='') {
    global $conf, $self, $onadb;

    // Check permissions
    if (!auth('user_admin')) {
        $response = new xajaxResponse();
        $response->script("alert('Permission denied!');");
        return $response;
    }

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = '';
    $exit_status = 0;

    // Validate input
    if (!$form['username']) {
        $js .= "alert('Error! All fields are required!');";
        $response->script($js);
        return $response;
    }
    if (!preg_match('/^[A-Za-z0-9.\-_]+$/', $form['username'])) {
        $js .= "alert('Invalid username! Valid characters: A-Z 0-9 .-_');";
        $response->script($js);
        return $response;
    }

    // Create a new record?
    if (!$form['user_id']) {
        list ($status, $rows) = db_insert_record(
            $onadb,
            'users',
            array(
                'username' => $form['username'],
                'password' => $form['password'],
                'ctime'    => date('Y-m-j G:i:s',time()),
            )
        );
        if ($status or !$rows) {
            $self['error'] = "ERROR => user_edit_add ws_save()  SQL Query failed: " . $self['error'];
            printmsg($self['error'], 0);
        }
        else {
            $self['error'] = "INFO => User ADDED: {$form['username']} ";
            printmsg($self['error'], 0);
        }

    }

    // Update an existing record?
    else {
        list($status, $rows, $user) = db_get_record($onadb, 'users', array('id' => $form['user_id']));
        if ($rows != 1 or $user['id'] != $form['user_id']) {
            $js .= "alert('Error! The record requested could not be loaded from the database!');";
            $response->script($js);
            return $response;
        }

        list ($status, $rows) = db_update_record(
            $onadb,
            'users',
            array(
                'id'       => $user['id'],
            ),
            array(
                'username' => $form['username'],
                'password' => $form['password'],
            )
        );
        if ($status ) {
            $self['error'] = "ERROR => user_edit update ws_save()  SQL Query failed: " . $self['error'];
            printmsg($self['error'], 0);
        }
        else {
            list($status, $rows, $new_record) = db_get_record($onadb, 'users', array('id' => $user['id']));

            // Return the success notice
            $self['error'] = "INFO => User UPDATED:{$user['id']}: {$new_record['username']}";

            $log_msg = "INFO => User UPDATED:{$user['id']}: ";
            $more="";
            foreach(array_keys($user) as $key) {
                if($user[$key] != $new_record[$key]) {
                    $log_msg .= $more . $key . "[" .$user[$key] . "=>" . $new_record[$key] . "]";
                    $more= ";";
                }
            }

        }
    }


    // Make sure we can load the user record from the db
    list($status, $rows, $user) = db_get_record($onadb, 'users', array('username' => $form['username']));
    if ($status or $rows != 1) {
        $js .= "alert('Save failed: " . trim($self['error']) . "');";
        // Return some javascript to the browser
        $response->script($js);
        return $response;
    }


    // This is a bit tricky because we want to make sure the user has all the groups
    // that are checked in the form, but no others.  And of course we want to make as
    // few sql queries as possible.  It's tricky because the form only submits us the
    // groups that are checked.


    // Get a list of every group
    list($status, $rows, $groups) = db_get_records($onadb, 'auth_groups', 'id > 0');

    // Loop through each group
    foreach ($groups as $group) {
        // See if the user is assigned to this group or not
        list($status, $rows, $tmp) = db_get_record($onadb, 'group_assignments', array('user_id' => $user['id'], 'group_id' => $group['id']));
        $exit_status += $status;

        // If the user is supposed to be assigned to this group, make sure she is.
        if (isset($form['groups']) and array_key_exists($group['name'], $form['groups'])) {
            if ($status == 0 and $rows == 0) {
                list($status, $rows) = db_insert_record($onadb, 'group_assignments', array('user_id' => $user['id'], 'group_id' => $group['id']));
                $log_msg .= $more . "group_add[" . $group['name'] . "]";
                $more= ";";
                $exit_status += $status;
            }
        }
        // Otherwise, make sure she doesn't have that permission
        else {
            if ($status == 0 and $rows == 1) {
                list($status, $rows) = db_delete_records($onadb, 'group_assignments', array('user_id' => $user['id'], 'group_id' => $group['id']));
                $log_msg .= $more . "group_del[" . $group['name'] . "]";
                $more= ";";
                $exit_status += $status;
            }
        }
    }


    // If the module returned an error code display a popup warning
    if ($status) {
        $js .= "alert('Save failed: " . trim($self['error']) . "');";
    }
    else {
        // only print to logfile if a change has been made to the record
        if($more != '') {
            printmsg($self['error'], 0);
            printmsg($log_msg, 0);
        }
        $js .= "removeElement('{$window_name}');";
        $js .= "xajax_window_submit('app_user_list', xajax.getFormValues('app_user_list_filter_form'), 'display_list');";
    }

    // Return some javascript to the browser
    $response->script($js);
    return $response;
}




?>
