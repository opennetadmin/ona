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
if (!auth('advanced')) {
    $window['js'] = "removeElement('{$window_name}'); alert('Permission denied!');";
    return;
}

// Set the window title:
$window['title'] = "Device Model Administration";

// Load some html into $window['html']
$form_id = "{$window_name}_filter_form";
$tab = 'device_model';
$submit_window = $window_name;
$content_id = "{$window_name}_list";
$window['html'] .= <<<EOL
    <!-- Tabs & Quick Filter -->
    <table width="100%" cellspacing="0" border="0" cellpadding="0" >
        <tr>
            <td id="{$form_id}_{$tab}_tab" nowrap="true" class="table-tab-active">
                Device models <span id="{$form_id}_{$tab}_count"></span>
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
                    placeholder="Model"
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
    /* Put a minimize icon in the title bar */
    el('{$window_name}_title_r').innerHTML =
        '&nbsp;<a onClick="toggle_window(\'{$window_name}\');" title="Minimize window" style="cursor: pointer;"><img src="{$images}/icon_minimize.gif" border="0" /></a>' +
        el('{$window_name}_title_r').innerHTML;

    /* Put a help icon in the title bar */
    el('{$window_name}_title_r').innerHTML =
        '&nbsp;<a href="{$_ENV['help_url']}{$window_name}" target="null" title="Help" style="cursor: pointer;"><img src="{$images}/silk/help.png" border="0" /></a>' +
        el('{$window_name}_title_r').innerHTML;

    /* Setup the quick filter */
    {$form_id}_last_search = '';

    /* Tell the browser to load/display the list */
    xajax_window_submit('{$submit_window}', xajax.getFormValues('{$form_id}'), 'display_list');
EOL;












// This function displays a list (all?) device models
function ws_display_list($window_name, $form) {
    global $conf, $self, $onadb;
    global $font_family, $color, $style, $images;

    // Check permissions
    if (!auth('advanced')) {
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
            <td class="list-header" align="center" style="{$style['borderR']};">Manufacturer</td>
            <td class="list-header" align="center" style="{$style['borderR']};">Device model</td>
            <td class="list-header" align="center" style="{$style['borderR']};">SNMP sysobjectid</td>
            <td class="list-header" align="center">&nbsp;</td>
        </tr>

EOL;

    $where = 'id > 0';
    if (is_array($form) and $form['filter']) {
        $where = 'name LIKE ' . $onadb->qstr('%'.$form['filter'].'%');
    }
    // Offset for SQL query
    $offset = ($conf['search_results_per_page'] * ($page - 1));
    if ($offset == 0) { $offset = -1; }

    // Get list of elements
    list($status, $rows, $records) = db_get_records($onadb, 'models', $where, 'manufacturer_id', $conf['search_results_per_page'], $offset);

    // If we got less than serach_results_per_page, add the current offset to it
    // so that if we're on the last page $rows still has the right number in it.
    if ($rows > 0 and $rows < $conf['search_results_per_page']) {
        $rows += ($conf['search_results_per_page'] * ($page - 1));
    }

    // If there were more than $conf['search_results_per_page'] find out how many records there really are
    else if ($rows >= $conf['search_results_per_page']) {
        list ($status, $rows, $tmp) = db_get_records($onadb, 'models', $where, '', 0);
    }
    $count = $rows;


    // Loop through and display the device models
    foreach ($records as $record) {

        list($status, $rows, $manufacturer) = ona_get_manufacturer_record(array('id' => $record['manufacturer_id']));
        $record['manufacturer_name'] = $manufacturer['name'];

        // Escape data for display in html
        foreach(array_keys((array)$record) as $key) {
            $record[$key] = htmlentities($record[$key], ENT_QUOTES, $conf['php_charset']);
        }

        $html .= <<<EOL
        <tr onMouseOver="this.className='row-highlight'" onMouseOut="this.className='row-normal'">

            <td class="list-row">
                {$record['manufacturer_name']}&nbsp;
            </td>

            <td class="list-row">
                <a title="Edit device model. id: {$record['id']}"
                   class="act"
                   onClick="xajax_window_submit('app_device_model_edit', '{$record['id']}', 'editor');"
                >{$record['name']}</a>&nbsp;
            </td>

            <td class="list-row">
                {$record['snmp_sysobjectid']}&nbsp;
            </td>

            <td align="right" class="list-row" nowrap="true">
                <a title="Edit device model. id: {$record['id']}"
                    class="act"
                    onClick="xajax_window_submit('app_device_model_edit', '{$record['id']}', 'editor');"
                ><img src="{$images}/silk/page_edit.png" border="0"></a>&nbsp;

                <a title="Delete device model: id: {$record['id']}"
                    class="act"
                    onClick="var doit=confirm('Are you sure you want to delete this device model?');
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
        <!-- ADD device model LINK -->
        <a title="New device model"
            class="act"
            onClick="xajax_window_submit('app_device_model_edit', ' ', 'editor');"
        ><img src="{$images}/silk/page_add.png" border="0"></a>&nbsp;

        <a title="New device model"
            class="act"
            onClick="xajax_window_submit('app_device_model_edit', ' ', 'editor');"
        >Add device model</a>&nbsp;
    </div>
EOL;


    // Build page links if there are any
    $html .= get_page_links($page, $conf['search_results_per_page'], $count, $window_name, $form['form_id']);


    // Insert the new table into the window
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $response->assign("{$form['form_id']}_device_model_count",  "innerHTML", "({$count})");
    $response->assign("{$form['content_id']}", "innerHTML", $html);
    return $response;
}






//////////////////////////////////////////////////////////////////////////////
// Function:
//     Delete
//
// Description:
//     Deletes a device model.
//////////////////////////////////////////////////////////////////////////////
function ws_delete($window_name, $form='') {
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

    // Load the record to make sure it exists
    list($status, $rows, $device_model) = db_get_record($onadb, 'models', array('id' => $form));
    if ($status or !$rows) {
        $response->script("alert('Delete failed: Device model id {$form} does not exist');");
        return $response;
    }

    // Get a list of device_types that use this device model
    list($status, $rows, $tmp) = db_get_records($onadb, 'device_types', array('model_id' => $form), '', 0);

    // Check that there are no parent records using this model
    if ($rows > 0) {
        $js .= "alert('Delete failed: There are {$rows} device_types using this device model.');";
    }
    else {
        // Delete the record
        list($status, $rows) = db_delete_records($onadb, 'models', array('id' => $device_model['id']));

        if ($status or !$rows) {
            // If the module returned an error code display a popup warning
            $js .= "alert('Delete failed: " . trim($self['error']) . "');";
            $self['error'] = "ERROR => device_model_list ws_delete() SQL Query failed: " . $self['error'];
            printmsg($self['error'], 0);
        }
        else {
            $self['error'] = "INFO => Device Model DELETED: {$device_model['name']} ";
            printmsg($self['error'], 0);
        }
    }

    // Refresh the current list.. it's changed!
    $js .= "xajax_window_submit('$window_name', xajax.getFormValues('{$window_name}_filter_form'), 'display_list');";

    // Send an XML response
    $response->script($js);
    return $response;
}






?>
