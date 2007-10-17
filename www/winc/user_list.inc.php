<?
// 
// So, the basic flow of this script is like this:
//   * When the window is initially opened we define the normal window
//     parameters for building an almost empty window.  After that new
//     empty window is created it's instructed to run an xajax callback
//     to the display_list() function.  display_list() builds an
//     html list of entriess and pushes it into the empty window.
//   * If a search is entered into the "quick filter" another xajax
//     call is made to display_list(), this time passing a search 
//     query.  display_list() grabs the refined list of entries
//     and pushes them to the window just like the first time.
// 
// 
// 



// Set the window title:
$window['title'] = "Employee List";


// EMPLOYEE LIST
$form_id = "{$window_name}_filter_form";
$tab = 'employees';
$submit_window = $window_name;
$content_id = "{$window_name}_list";
$window['html'] .= <<<EOL
    <!-- EMPLOYEE LIST -->
    <div style="border: 1px solid {$color['border']};">
        
        <!-- Tab & Quick Filter -->
        <table id="{$form_id}_table" cellspacing="0" border="0" cellpadding="0">
            <tr>
                <td id="{$form_id}_employees_tab" nowrap="true" class="table-tab-active">
                    Employees <span id="{$form_id}_{$tab}_count"></span>
                </td>
                
                <td id="{$form_id}_quick_filter" class="padding" align="right" width="100%">
                    <form id="{$form_id}" onSubmit="return false;">
                    <input id="{$form_id}_page" name="page" value="1" type="hidden">
                    <input name="content_id" value="{$content_id}" type="hidden">
                    <input name="form_id" value="{$form_id}" type="hidden">
                    <div id="{$form_id}_filter_overlay"
                         style="position: relative;
                                display: inline;
                                color: #CACACA;
                                font-size: small;
                                cursor: text;"
                         onClick="this.style.display = 'none'; el('{$form_id}_filter').focus();"
                    >Filter</div>
                    <input 
                        id="{$form_id}_filter"
                        name="filter" 
                        class="filter" 
                        type="text" 
                        value=""
                        size="10" 
                        maxlength="20" 
                        alt="Quick Filter"
                        onFocus="el('{$form_id}_filter_overlay').style.display = 'none';"
                        onBlur="if (this.value == '') el('{$form_id}_filter_overlay').style.display = 'inline';"
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
        
        <div id="{$content_id}">
            {$conf['loading_icon']}
        </div>
        
    </div>

EOL;

$window['js'] .= <<<EOL
        /* Setup the quick filter */
        el('{$form_id}_filter_overlay').style.left = (el('{$form_id}_filter_overlay').offsetWidth + 10) + 'px';
        {$form_id}_last_search = '';
        
        /* Tell the browser to load/display the list */
        xajax_window_submit('{$submit_window}', xajax.getFormValues('{$form_id}'), 'display_list');
EOL;







// This function displays a list (all?) employees
function ws_display_list($window_name, $form) {
    global $conf, $self, $mysql;
    global $font_family, $color, $style, $images;
    
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    
    // Make sure they're logged in
    if (!loggedIn()) { return($response->getXML()); }
    
    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);
    
    // Find out what page we're on
    $page = 1;
    if ($form['page'] and is_numeric($form['page'])) { $page = $form['page']; }
    
    printmsg("INFO => Displaying user list page: {$page} client url: {$_SESSION['auth']['client']['url']}", 0);
    
    // Calculate the SQL query offset (based on the page being displayed)
    $offset = ($conf['search_results_per_page'] * ($page - 1));
    if ($offset == 0) { $offset = -1; }
    
    
    $where = "`client_id` = {$_SESSION['auth']['client']['id']} AND `active` = 1";
    if (is_array($form) and $form['filter']) {
        $where .= ' AND `username` LIKE ' . $mysql->qstr('%'.$form['filter'].'%');
    }
    
    // Get our employees
    list($status, $rows, $records) = db_get_records($mysql, 'users', $where, 'username', $conf['search_results_per_page'], $offset);
    
    // If we got less than serach_results_per_page, add the current offset to it
    // so that if we're on the last page $rows still has the right number in it.
    if ($rows > 0 and $rows < $conf['search_results_per_page']) {
        $rows += ($conf['search_results_per_page'] * ($page - 1));
    }
    
    // If there were more than $conf['search_results_per_page'] find out how many records there really are
    else if ($rows >= $conf['search_results_per_page']) {
        list ($status, $rows, $tmp) = db_get_records($mysql, 'users', $where, '', 0);
    }
    $count = $rows;
    
    // Add a table header
    $html = <<<EOL
    
    <!-- Results Table -->
    <table id="{$form['form_id']}_host_list" class="list-box" cellspacing="0" border="0" cellpadding="0" width="100%">
        
        <!-- Table Header -->
        <tr>
            <td class="list-header" align="center" style="border-right: 1px solid {$color['border']};">Username</td>
            <td class="list-header" align="center" style="border-right: 1px solid {$color['border']};">Full Name</td>
            <td class="list-header" align="center" style="border-right: 1px solid {$color['border']};">Company</td>
            <td class="list-header" align="center" style="border-right: 1px solid {$color['border']};">Admin</td>
            <td class="list-header" align="center">&nbsp</td>
        </tr>
        
EOL;
    
    // Loop through and display the records
    foreach ($records as $record) {
        
        list ($status, $rows, $client) = db_get_record($mysql, 'clients', array('id' => $record['client_id']));
        $record['company_name'] = $client['company_name'];
        
        // Escape data for display in html
        foreach(array_keys($record) as $key) { 
            $record[$key] = htmlentities($record[$key], ENT_QUOTES);
        }
        
        // If the user is an admin, set some extra html
        $admin_html = "";
        if (empty($perm)) list($status, $rows, $perm) = db_get_record($mysql, 'permissions', array('name' => 'admin'));
        list($status, $rows, $acl)  = db_get_record($mysql, 'acl', array('user_id' => $record['id'], 'perm_id' => $perm['id']));
        if ($acl['id']) $admin_html = "<img src=\"{$images}/silk/tick.png\" border=\"0\">";
        
        
        $html .= <<<EOL
        <tr onMouseOver="this.className='row-highlight';" onMouseOut="this.className='row-normal';">
            
            <td class="list-row">
                <a title="Edit"
                   class="act"
                   onClick="xajax_window_submit('user_edit', '{$record['id']}', 'editor');"
                >{$record['username']}</a>&nbsp;
            </td>
            
            <td class="list-row" align="left">
                {$record['fname']} {$record['lname']}&nbsp;
            </td>
            
            <td class="list-row" align="left">
                {$record['company_name']}&nbsp;
            </td>
            
            <td class="list-row" align="left">
                {$admin_html}&nbsp;
            </td>
            
            <td class="list-row" align="right">
                <a title="Edit"
                    class="act"
                    onClick="xajax_window_submit('user_edit', '{$record['id']}', 'editor');"
                ><img src="{$images}/silk/page_edit.png" border="0"></a>&nbsp;
            
                <a title="Delete employee"
                    class="act"
                    onClick="var doit=confirm('Are you sure you want to delete this employee?');
                            if (doit == true)
                                xajax_window_submit('{$window_name}', '{$record['id']}', 'delete');"
                ><img src="{$images}/silk/delete.png" border="0"></a>&nbsp;
            </td>
            
        </tr>
EOL;
    }
    
    $html .= <<<EOL
        <!-- Add a new employee -->
        <tr>
            <td colspan="99" class="list-header">
                <a title="New employee"
                   class="act"
                   onClick="xajax_window_submit('user_edit', ' ', 'editor');"
                ><img src="{$images}/silk/page_add.png" border="0"></a>&nbsp;
                
                <a title="New employee"
                   class="act"
                   onClick="xajax_window_submit('user_edit', ' ', 'editor');"
                >Add new employee</a>&nbsp;
            </td>
        </tr>
    
    </table>
EOL;
    
    
    // Build page links if there are any
    $html .= get_page_links($page, $conf['search_results_per_page'], $count, $window_name, $form['form_id']);
    
    
    // Insert the new table into the window
    $response->addAssign("{$form['form_id']}_employees_count",  "innerHTML", "({$count})");
    $response->addAssign("{$form['content_id']}", "innerHTML", $html);
    // $response->addScript($js);
    return($response->getXML());
}






//////////////////////////////////////////////////////////////////////////////
// Function: delete()
// 
// Description:
//     Deletes a record.
//////////////////////////////////////////////////////////////////////////////
function ws_delete($window_name, $form='') {
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
    
    // Don't allow a user to delete their own account!
    if ($_SESSION['auth']['user']['id'] == $form) {
        $response = new xajaxResponse();
        $response->addScript("alert('Sorry, but you can\\'t delete your own admin account!');");
        return($response->getXML()); 
    }

    
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = '';
    
    
    // Set the user to inactive (which will make them "dissapear" for all practical purposes)
    printmsg("NOTICE => Deleting (disabling) user: {$form} client url: {$_SESSION['auth']['client']['url']}", 0);
    list($status, $rows) = db_update_record($mysql, 'users', array('client_id' => $_SESSION['auth']['client']['id'], 'id' => $form), array('active' => 0));
    
    // If the module returned an error code display a popup warning
    if ($status != 0 or $rows != 1) {
        $js .= "alert('Delete failed');";
    }
    else {
        // Refresh the current list of templates.. it's changed!
        $js .= "xajax_window_submit('{$window_name}', xajax.getFormValues('{$window_name}_filter_form'), 'display_list');";
    }
    
    // Insert the new table into the window
    $response->addScript($js);
    return($response->getXML());
}











?>