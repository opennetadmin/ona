<?php

//
// So, the basic flow of this script is like this:
//   * When the window is initially opened we define the normal window
//     parameters for building an almost empty window.  After that new
//     empty window is created it's instructed to run an xajax callback
//     to the display_list() function.  display_list() builds an
//     html list of users and pushes it into the empty window.
//   * If a search is entered into the "quick filter" another xajax
//     call is made to display_list(), this time passing a search
//     query.  display_list() grabs the refined list of users
//     and pushes them to the window just like the first time.
//
//
//



// Check permissions
if (!auth('user_admin')) {
    $window['js'] = "removeElement('{$window_name}'); alert('Permission denied!');";
    return;
}


// Set the window title:
$window['title'] = "User Administration";

// Load some html into $window['html']
$form_id = "{$window_name}_filter_form";
$tab = 'users';
$submit_window = $window_name;
$content_id = "{$window_name}_list";
$window['html'] .= <<<EOL
    <!-- Tabs & Quick Filter -->
    <table width="100%" cellspacing="0" border="0" cellpadding="0" >
        <tr>
            <td id="{$form_id}_{$tab}_tab" nowrap="true" class="table-tab-active">
                Users <span id="{$form_id}_{$tab}_count"></span>
            </td>

            <td id="{$form_id}_quick_filter" class="padding" align="right" width="100%">
                <form id="{$form_id}" onSubmit="return false;" autocomplete="off">
                <input id="{$form_id}_page" name="page" value="1" type="hidden">
                <input name="content_id" value="{$content_id}" type="hidden">
                <input name="form_id" value="{$form_id}" type="hidden">
                <input
                    id="{$form_id}_filter"
                    name="filter"
                    class="filter"
                    type="text"
                    value=""
                    size="10"
                    maxlength="20"
                    alt="Quick Filter"
                    placeholder="User"
                    onKeyUp="
                        if (typeof(timer) != 'undefined') clearTimeout(timer);
                        code = 'if ({$form_id}_last_search != el(\'{$form_id}_filter\').value) {' +
                               '    {$form_id}_last_search = el(\'{$form_id}_filter\').value;' +
                               '    document.getElementById(\'{$form_id}_page\').value = 1;' +
                               '    xajax_window_submit(\'{$submit_window}\', xajax.getFormValues(\'{$form_id}\'), \'display_list\');' +
                               '}';
                        timer = setTimeout(code, 700);"
                >
                </form>
            </td>

        </tr>
    </table>

    <!-- Item List -->
    <div id='{$content_id}'>
        {$conf['loading_icon']}
    </div>
EOL;







// Define javascript to run after the window is created
$window['js'] = <<<EOL
    /* Setup the quick filter */
    {$form_id}_last_search = '';

    /* Tell the browser to load/display the list */
    xajax_window_submit('{$submit_window}', xajax.getFormValues('{$form_id}'), 'display_list');
EOL;












// This function displays a list (all?) users in the
function ws_display_list($window_name, $form) {
    global $conf, $self, $onadb;
    global $font_family, $color, $style, $images;

    // Check permissions
    if (!auth('user_admin')) {
        $response = new xajaxResponse();
        $response->script("alert('Permission denied!');");
        return $response;
    }

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);

    // Find out what page we're on
    $page = 1;
    if ($form['page'] and is_numeric($form['page'])) { $page = $form['page']; }


    $html = <<<EOL

    <!-- Results Table -->
    <table cellspacing="0" border="0" cellpadding="0" width="100%" class="list-box">

        <!-- Table Header -->
        <tr>
            <td class="list-header" align="center" style="{$style['borderR']};">Username</td>
            <td class="list-header" align="center">&nbsp;</td>
        </tr>

EOL;

    $where = '`id` > 0';
    if (is_array($form) and $form['filter']) {
        $where = '`username` LIKE ' . $onadb->qstr('%'.$form['filter'].'%');
    }
    // Offset for SQL query
    $offset = ($conf['search_results_per_page'] * ($page - 1));
    if ($offset == 0) { $offset = -1; }

    // Get our users
    list($status, $rows, $records) = db_get_records($onadb, 'users', $where, 'username', $conf['search_results_per_page'], $offset);

    // If we got less than serach_results_per_page, add the current offset to it
    // so that if we're on the last page $rows still has the right number in it.
    if ($rows > 0 and $rows < $conf['search_results_per_page']) {
        $rows += ($conf['search_results_per_page'] * ($page - 1));
    }

    // If there were more than $conf['search_results_per_page'] find out how many records there really are
    else if ($rows >= $conf['search_results_per_page']) {
        list ($status, $rows, $tmp) = db_get_records($onadb, 'users', $where, '', 0);
    }
    $count = $rows;


    // Loop through and display the users
    foreach ($records as $record) {

        // Escape data for display in html
        foreach(array_keys($record) as $key) {
            $record[$key] = htmlentities($record[$key], ENT_QUOTES, $conf['php_charset']);
        }

        $html .= <<<EOL
        <tr onMouseOver="this.className='row-highlight'" onMouseOut="this.className='row-normal'">

            <td class="list-row">
                <a title="Edit user. ID: {$record['id']}"
                   class="act"
                   onClick="xajax_window_submit('app_user_edit', '{$record['id']}', 'editor');"
                >{$record['username']}</a>&nbsp;
            </td>

            <td align="right" class="list-row" nowrap="true">
                <a title="Edit user permissions. ID: {$record['id']}"
                    class="act"
                    onClick="xajax_window_submit('app_permission_editor', 'user_id=>{$record['id']}', 'editor');"
                ><img src="{$images}/silk/key.png" border="0"></a>&nbsp;

                <a title="Edit user. ID: {$record['id']}"
                    class="act"
                    onClick="xajax_window_submit('app_user_edit', '{$record['id']}', 'editor');"
                ><img src="{$images}/silk/page_edit.png" border="0"></a>&nbsp;

                <a title="Delete user: ID: {$record['id']}"
                    class="act"
                    onClick="var doit=confirm('Are you sure you want to delete this user?');
                            if (doit == true)
                                xajax_window_submit('{$window_name}', '{$record['id']}', 'delete');"
                ><img src="{$images}/silk/delete.png" border="0"></a>&nbsp;
            </td>

        </tr>
EOL;
    }

    $html .= <<<EOL
    </table>

    <!-- Add a new record -->
    <div class="act-box" style="padding: 2px 4px; border-top: 1px solid {$color['border']}; border-bottom: 1px solid {$color['border']};">
        <!-- ADD USER LINK -->
        <a title="New user"
            class="act"
            onClick="xajax_window_submit('app_user_edit', ' ', 'editor');"
        ><img src="{$images}/silk/page_add.png" border="0"></a>&nbsp;

        <a title="New user"
            class="act"
            onClick="xajax_window_submit('app_user_edit', ' ', 'editor');"
        >Add new user</a>&nbsp;
    </div>
EOL;


    // Build page links if there are any
    $html .= get_page_links($page, $conf['search_results_per_page'], $count, $window_name, $form['form_id']);


    // Insert the new table into the window
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $response->assign("{$form['form_id']}_users_count",  "innerHTML", "({$count})");
    $response->assign("{$form['content_id']}", "innerHTML", $html);
    return $response;
}






//////////////////////////////////////////////////////////////////////////////
// Function:
//     Delete
//
// Description:
//     Deletes a user.
//////////////////////////////////////////////////////////////////////////////
function ws_delete($window_name, $form='') {
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

    // Load the user record to make sure it exists
    list($status, $rows, $user) = db_get_record($onadb, 'users', array('id' => $form));
    if ($status or !$rows) {
        $response->script("alert('Delete failed: User ID {$form} doesnt exist');");
        return $response;
    }

    // Delete the user's group assignments
    do {
        list($status, $rows) = db_delete_records($onadb, 'group_assignments', array('user_id' => $user['id']));
    } while ($rows >= 1);

    // Delete the user's permission assignments
    do {
        list($status, $rows) = db_delete_records($onadb, 'permission_assignments', array('user_id' => $user['id']));
    } while ($rows >= 1);

    // Delete the user's record
    list($status, $rows) = db_delete_records($onadb, 'users', array('id' => $user['id']));

    // If the module returned an error code display a popup warning
    if ($status != 0) {
        $js .= "alert('Delete failed');";
        $self['error'] = "ERROR => user_list delete ws_save() SQL Query failed: " . $self['error'];
        printmsg($self['error'], 0);
    }
    else {
        $self['error'] = "INFO => User DELETED: {$user['username']} ";
        printmsg($self['error'], 0);
        // Refresh the current list of users.. it's changed!
        $js .= "xajax_window_submit('$window_name', xajax.getFormValues('{$window_name}_filter_form'), 'display_list');";
    }

    // Send an XML response
    $response->script($js);
    return $response;
}






?>
