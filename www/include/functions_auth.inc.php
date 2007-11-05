<?php


function get_authentication($login_name='guest', $login_password='') {
    global $conf, $self, $onadb;

    $js = "el('loginmsg').innerHTML = '<span style=\"color: green;\">Success!</span>'; setTimeout('removeElement(\'tt_loginform\')',1000);";
    $exit_status = 0;

    // Validate the userid was passed and is "clean"
    if (!preg_match('/^[A-Za-z0-9.\-_]+$/', $login_name)) {
        $js = "el('loginmsg').innerHTML = 'Bad username format';";
        printmsg("ERROR => Login failure for {$login_name}: Bad username format", 0);
        return(array(1, $js));
    }

    list($status, $rows, $user) = db_get_record($onadb, 'users', "username LIKE '{$login_name}'");

    if (!$rows) {
        $js = "el('loginmsg').innerHTML = 'Unknown user';";
        printmsg("ERROR => Login failure for {$login_name}: Unknown user", 0);
        return(array(1, $js));
    }


    if ($user['password'] != $login_password) {
        $js = "el('loginmsg').innerHTML = 'Password incorrect';";
        printmsg("ERROR => Login failure for {$login_name}: Password incorrect", 0);
        return(array(1, $js));
    }

    // If the password is good.. return success.
    printmsg("INFO => Authentication Successful for {$login_name}", 0);
    return(array($exit_status, $js));
}




function get_perms($login_name='') {
    global $conf, $self, $onadb;

    //
    // Login Page
    // Basic flow:
    //   check to see if they have login info
    //   check to see if they have a local user ID
    //     else use guest ID
    //   check to see what local and upstream groups they're a part of
    //   load user permissions
    //   load group permissions
    //   redirect to home page
    //

    // We'll be populating these arrays
    $user = array();
    $groups = array();
    $permissions = array();

    // Load their user record
    list($status, $rows, $user) = db_get_record($onadb, 'users', array('username' => $login_name));
    if ($status or $rows != 1) {
        // Load the guest account since they don't have an account of their own
        list($status, $rows, $user) = db_get_record($onadb, 'users', array('username' => 'guest'));
    }

    // Update the user's atime
    db_update_record($onadb, 'users', array('id' => $user['id']), array('atime' => date_mangle(time())));

    // Load the user's groups
    list($status, $rows, $records) = db_get_records($onadb, 'group_assignments', array('user_id' => $user['id']));
    foreach ($records as $record) {
        list($status, $rows, $group) = db_get_record($onadb, 'groups', array('id' => $record['group_id']));
        $groups[$group['name']] = $group['id'];
        if ($group['level'] > $user['level']) { $user['level'] = $group['level']; }
    }
    // // Load their AD groups
    // foreach (array()$ad_groups as $group) {
    //     list($status, $rows, $record) = db_get_record($onadb, 'groups', array('name' => $group));
    //     if ($status == 0 and $rows == 1) {
    //         $groups[$group] = $record['id'];
    //     }
    //     if ($record['level'] > $user['level']) { $user['level'] = $record['level']; }
    // }

    // Load the users permissions based on their user_id
    list($status, $rows, $records) = db_get_records($onadb, 'permission_assignments', array('user_id' => $user['id']));
    foreach ($records as $record) {
        list($status, $rows, $perm) = db_get_record($onadb, 'permissions', array('id' => $record['perm_id']));
        $permissions[$perm['name']] = $perm['id'];
    }

    // Load the users permissions based on their group ids
    foreach (array_values($groups) as $group_id) {
        list($status, $rows, $records) = db_get_records($onadb, 'permission_assignments', array('group_id' => $group_id));
        foreach ($records as $record) {
            list($status, $rows, $perm) = db_get_record($onadb, 'permissions', array('id' => $record['perm_id']));
            $permissions[$perm['name']] = $perm['id'];
        }
    }

    // Save stuff in the session
    unset($_SESSION['ona']['auth']);
    $_SESSION['ona']['auth']['user']   = $user;
    $_SESSION['ona']['auth']['groups'] = $groups;
    $_SESSION['ona']['auth']['perms']  = $permissions;

    // Log that the user logged in
    printmsg("INFO => Authorization successful for " . $login_name, 0);
    return(0);

}

?>