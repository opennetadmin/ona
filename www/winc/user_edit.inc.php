<?php


//////////////////////////////////////////////////////////////////////////////
// Function:
//     Display Edit Form
// 
// Description:
//     Displays a form for creating/editing a record.
//     If a record id is found in $form it is used to display an existing
//     record for editing.  When "Save" is pressed the save()
//     function is called.
//////////////////////////////////////////////////////////////////////////////
function ws_editor($window_name, $form='') {
    global $conf, $self, $mysql;
    global $color, $style, $images;
    
    // Make sure they have permission
    if (!auth('admin')) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML()); 
    }
    
    
    // If we got a record ID, load it for display
    $admin_checked = '';
    if (is_string($form) and is_numeric($form)) {
        list($status, $rows, $record) = db_get_record($mysql, 'users', array('id' => $form, 'client_id' => $_SESSION['auth']['client']['id']));
        list($status, $rows, $perm) = db_get_record($mysql, 'permissions', array('name' => 'admin'));
        list($status, $rows, $acl)  = db_get_record($mysql, 'acl', array('user_id' => $record['id'], 'perm_id' => $perm['id']));
        if ($acl['id']) {
            $admin_checked = 'CHECKED';
        }
    }
    
    
    // Set a few parameters for the "results" window we're about to create
    $window = array(
        'title' => 'Employee Editor',
        'html'  => '',
        'js'    => '',
    );
    
    
    // Escape data for display in html
    foreach(array_keys($record) as $key) { $record[$key] = htmlentities($record[$key], ENT_QUOTES, $conf['php_charset']); }
    
    // Load some html into $window['html']
    $window['html'] .= <<<EOL
    
    <!-- Simple Employee Edit Form -->
    <form id="{$window_name}_edit_form" onSubmit="return false;">
    <input type="hidden" name="id" value="{$record['id']}">
    <table cellspacing="1" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding: 5px 20px;">
        
        <tr>
            <td align="right" nowrap="true" class="padding">
                First Name
            </td>
            <td class="padding" align="left" width="100%" class="padding">
                <input 
                    name="fname" 
                    alt="First Name"
                    value="{$record['fname']}"
                    class="edit" 
                    type="text" 
                    size="32" maxlength="64" 
                >
            </td>
        </tr>
        
        <tr>
            <td align="right" nowrap="true" class="padding">
                Last Name
            </td>
            <td class="padding" align="left" width="100%" class="padding">
                <input 
                    name="lname" 
                    alt="Last Name"
                    value="{$record['lname']}"
                    class="edit" 
                    type="text" 
                    size="32" maxlength="64" 
                >
            </td>
        </tr>
        
        <tr>
            <td align="right" nowrap="true" class="padding">
                Username
            </td>
            <td class="padding" align="left" width="100%" class="padding">
                <input 
                    name="username"
                    alt="Username"
                    value="{$record['username']}"
                    class="edit" 
                    type="text"
                    size="32" maxlength="255"
                >
            </td>
        </tr>
        
        <tr>
            <td align="right" valign="top" nowrap="true" class="padding">
                Password
            </td>
            <td class="padding" align="left" width="100%" class="padding">
                <input 
                    name="passwd"
                    alt="Password"
                    value=""
                    class="edit" 
                    type="password"
                    size="32" maxlength="64"
                >
            </td>
        </tr>
        
        <tr>
            <td align="right" valign="top" nowrap="true" class="padding">
                Admin
            </td>
            <td class="padding" align="left" width="100%" class="padding">
                <input 
                    name="admin"
                    alt="Admin"
                    type="checkbox"
                    {$admin_checked}
                >
            </td>
        </tr>
        
        <tr>
            <td align="right" valign="top" nowrap="true" class="padding">
                &nbsp;
            </td>
            <td class="padding" align="right" width="100%" class="padding">
                <input type="hidden" name="overwrite" value="{$overwrite}">
                <input class="edit" type="button" name="cancel" value="Cancel" onClick="removeElement('{$window_name}');">
                <input class="edit" type="button" 
                    name="submit" 
                    value="Save" 
                    onClick="xajax_window_submit('{$window_name}', xajax.getFormValues('{$window_name}_edit_form'), 'save');"
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
//     Creates/updates a record with the info from the submitted form.
//////////////////////////////////////////////////////////////////////////////
function ws_save($window_name, $form='') {
    global $conf, $self, $mysql;
    
    // Make sure they have permission
    if (!auth('admin')) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML()); 
    }
    
    // Don't allow this in the demo account!
    if ($_SESSION['auth']['client']['url'] == 'demo') {
        $response = new xajaxResponse();
        $response->addScript("alert('Feature disabled in this demo!');");
        return($response->getXML()); 
    }
    
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = '';
    
    // Make sure they're logged in
    if (!loggedIn()) { return($response->getXML()); }
    
    // Validate input
    if (!$form['fname'] or !$form['lname'] or !$form['username']) {
        $js .= "alert('Error! First name, last name, and username are required fields!');";
        $response->addScript($js);
        return($response->getXML());
    }
    if (!$form['id'] and !$form['passwd']) {
        $js .= "alert('Error! A password is required to create a new employee!');";
        $response->addScript($js);
        return($response->getXML());
    }
    
    // Usernames are stored in lower case
    $form['username'] = strtolower($form['username']);
    
    // md5sum the password if there is one
    if ($form['passwd']) {
        $form['passwd'] = md5($form['passwd']);
    }
    
    // Create a new record?
    if (!$form['id']) {
        list ($status, $rows) = db_insert_record(
            $mysql, 
            'users',
            array(
                'client_id'   => $_SESSION['auth']['client']['id'],
                'active'      => 1,
                'fname'       => $form['fname'],
                'lname'       => $form['lname'],
                'username'    => $form['username'],
                'passwd'      => $form['passwd'],
                'ctime'       => date_mangle(time()),
                'mtime'       => date_mangle(time()),
            )
        );
        printmsg("NOTICE => Added new user: {$form['username']} client url: {$_SESSION['auth']['client']['url']}", 0);
    }
    
    // Update an existing record?
    else {
        list($status, $rows, $record) = db_get_record($mysql, 'users', array('id' => $form['id'], 'client_id' => $_SESSION['auth']['client']['id']));
        if ($rows != 1 or $record['id'] != $form['id']) {
            $js .= "alert('Error! The record requested could not be loaded from the database!');";
            $response->addScript($js);
            return($response->getXML());
        }
        if (strlen($form['passwd']) < 32) {
            $form['passwd'] = $record['passwd'];
        }
        
        list ($status, $rows) = db_update_record(
            $mysql, 
            'users',
            array(
                'id'          => $form['id'],
            ),
            array(
                'fname'       => $form['fname'],
                'lname'       => $form['lname'],
                'username'    => $form['username'],
                'passwd'      => $form['passwd'],
                'mtime'       => date_mangle(time()),
                'active'      => 1
            )
        );
        
        printmsg("NOTICE => Updated user: {$form['username']} client url: {$_SESSION['auth']['client']['url']}", 0);
    }
    
    // If the module returned an error code display a popup warning
    if ($status) {
        printmsg("ERROR => User add/edit failed! {$self['error']}", 0);
        $js .= "alert('Save failed. Contact the webmaster if this problem persists.');";
        $response->addScript($js);
        return($response->getXML());
    }
    
    $js .= "removeElement('{$window_name}');";
    $js .= "xajax_window_submit('user_list', xajax.getFormValues('user_list_filter_form'), 'display_list');";
    
    // Handle the "admin" flag
    list($status, $rows, $user) = db_get_record($mysql, 'users', array('username' => $form['username'], 'client_id' => $_SESSION['auth']['client']['id'], 'active' => 1));
    list($status, $rows, $perm) = db_get_record($mysql, 'permissions', array('name' => 'admin'));
    list($status, $rows, $acl)  = db_get_record($mysql, 'acl', array('user_id' => $user['id'], 'perm_id' => $perm['id']));
    if ($form['admin'] and !$acl['id'] and $user['id'] and $perm['id']) {
        // Give the user the permission
        list($status, $rows)  = db_insert_record($mysql, 'acl', array('user_id' => $user['id'], 'perm_id' => $perm['id']));
    }
    else if (!$form['admin'] and $acl['id'] and $user['id'] and $perm['id'] and ($_SESSION['auth']['user']['id'] != $user['id']) ) {
        // Take the permission away, UNLESS THEY ARE TRYING TO MODIFY THEIR OWN ACCOUNT!
        list($status, $rows)  = db_delete_record($mysql, 'acl', array('user_id' => $user['id'], 'perm_id' => $perm['id']));
        
    }
    else if ($_SESSION['auth']['user']['id'] == $user['id']) {
        // IF they did try to remove their own admin status, give them a popup and tell them they can't do that.
        $js .= "alert('WARNING => You can\\'t change your own admin status!');";
    }
    
    // Insert the new table into the window
    $response->addScript($js);
    return($response->getXML());
}




?>