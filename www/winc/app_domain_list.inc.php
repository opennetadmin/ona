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


// Check permissions
// if (!auth('advanced')) {
//     $window['js'] = "removeElement('{$window_name}'); alert('Permission denied!');";
//     return;
// }

// Set the window title:
$window['title'] = "DNS Domain Administration";

// Load some html into $window['html']
$form_id = "{$window_name}_filter_form";
$tab = 'domain';
$submit_window = $window_name;
$content_id = "{$window_name}_list";


$window['html'] .= <<<EOL
    <!-- Tabs & Quick Filter -->
    <table width="100%" cellspacing="0" border="0" cellpadding="0" >
        <tr>
            <td id="{$form_id}_{$tab}_tab" nowrap="true" class="table-tab-active">
                Domains <span id="{$form_id}_{$tab}_count"></span>
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
                    placeholder="Domain"
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












// This function displays a list (all?) network types
function ws_display_list($window_name, $form) {
    global $conf, $self, $mysql, $onadb;
    global $font_family, $color, $style, $images;

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);

    // Build js to refresh this list
    $refresh = "xajax_window_submit('{$window_name}', xajax.getFormValues('{$form['form_id']}'), 'display_list');";


    // Find out what page we're on
    $page = 1;
    if ($form['page'] and is_numeric($form['page'])) { $page = $form['page']; }


    $html = <<<EOL

    <!-- Results Table -->
    <table cellspacing="0" border="0" cellpadding="0" width="100%" class="list-box">

        <!-- Table Header -->
        <tr>
            <td class="list-header" align="center" style="{$style['borderR']};">Domain name</td>
            <td class="list-header" align="center" style="{$style['borderR']};">Parent domain</td>
            <td class="list-header" align="center" style="{$style['borderR']};">Records in domain</td>
            <td class="list-header" align="center">&nbsp;</td>
        </tr>

EOL;

    $where = 'ID > 0';
    if (is_array($form) and $form['filter']) {
        $where = 'name like ' . $onadb->qstr('%'.$form['filter'].'%');
    }
    // Offset for SQL query
    $offset = ($conf['search_results_per_page'] * ($page - 1));
    if ($offset == 0) { $offset = -1; }

    // Get list of elements
    list($status, $rows, $records) = db_get_records($onadb, 'domains', $where, 'name', $conf['search_results_per_page'], $offset);

    // If we got less than search_results_per_page, add the current offset to it
    // so that if we're on the last page $rows still has the right number in it.
    if ($rows > 0 and $rows < $conf['search_results_per_page']) {
        $rows += ($conf['search_results_per_page'] * ($page - 1));
    }

    // If there were more than $conf['search_results_per_page'] find out how many records there really are
    else if ($rows >= $conf['search_results_per_page']) {
        list ($status, $rows, $tmp) = db_get_records($onadb, 'domains', $where, '', 0);
    }
    $count = $rows;


    // Loop through and display
    foreach ($records as $record) {

        if ($record['parent_id']) {
            list ($status, $rows, $parent) = ona_get_domain_record(array('id' => $record['parent_id']));
            $parent['name'] = ona_build_domain_name($parent['id']);
        }
        $record['parent_domain_name'] = $parent['name'];

        // Need to remove this variable, otherwise, if the current record does not have a parent
        // the previous parent domain name will be displayed
        unset($parent['name']);

        // make the name an fqdn
        $record['name'] = ona_build_domain_name($record['id']);

        // Escape data for display in html
        foreach(array_keys($record) as $key) {
            $record[$key] = htmlentities($record[$key], ENT_QUOTES, $conf['php_charset']);
        }

        list($status, $usage_rows, $tmp) = db_get_records($onadb, 'dns', "domain_id = {$record['id']}", '', 0);

        $html .= <<<EOL
        <tr onMouseOver="this.className='row-highlight'" onMouseOut="this.className='row-normal'">

EOL;
        if (auth('advanced')) {
            $html .= <<<EOL
            <td class="list-row">
                <form id="{$form['form_id']}_list_domain_{$record['id']}"
                        ><input type="hidden" name="id" value="{$record['id']}"
                        ><input type="hidden" name="js" value="{$refresh}"
                ></form>
                <a title="Edit DNS domain. ID: {$record['id']}"
                   class="act"
                   onClick="xajax_window_submit('edit_domain', xajax.getFormValues('{$form['form_id']}_list_domain_{$record['id']}'), 'editor');"
                >{$record['name']}</a>&nbsp;
            </td>
EOL;
        }
        else {
            $html .= <<<EOL
            <td class="list-row">
                {$record['name']}&nbsp;
            </td>
EOL;
        }
        $html .= <<<EOL
            <td class="list-row">
                {$record['parent_domain_name']}&nbsp;
            </td>
            <td class="list-row">
                {$usage_rows}
            </td>

            <td align="right" class="list-row" nowrap="true">
                <a title="View DNS domain. ID: {$record['id']}"
                    class="act"
                    onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain\', \'domain_id=>{$record['id']}\', \'display\')');"
                ><img src="{$images}/silk/zoom.png" border="0"></a>&nbsp;
EOL;
        if (auth('advanced')) {
            $html .= <<<EOL
                <a title="Edit DNS domain. ID: {$record['id']}"
                    class="act"
                    onClick="xajax_window_submit('edit_domain', xajax.getFormValues('{$form['form_id']}_list_domain_{$record['id']}'), 'editor');"
                ><img src="{$images}/silk/page_edit.png" border="0"></a>&nbsp;

                <a title="Delete DNS domain: ID: {$record['id']}"
                    class="act"
                    onClick="var doit=confirm('Are you sure you want to delete this domain?');
                            if (doit == true)
                                xajax_window_submit('edit_domain', xajax.getFormValues('{$form['form_id']}_list_domain_{$record['id']}'), 'delete');"
                ><img src="{$images}/silk/delete.png" border="0"></a>&nbsp;
EOL;
    }
        $html .= <<<EOL
            </td>

        </tr>
EOL;
    }

    $html .= <<<EOL
    </table>

    <!-- Add a new record -->
    <div class="act-box" style="padding: 2px 4px; border-top: 1px solid {$color['border']}; border-bottom: 1px solid {$color['border']};">
        <form id="{$form['form_id']}_add_domain_{$record['id']}"
                ><input type="hidden" name="js" value="{$refresh}"
        ></form>
        <!-- ADD domain LINK -->
        <a title="New DNS domain"
            class="act"
            onClick="xajax_window_submit('edit_domain', xajax.getFormValues('{$form['form_id']}_add_domain_{$record['id']}'), 'editor');"
        ><img src="{$images}/silk/page_add.png" border="0"></a>&nbsp;

        <a title="New DNS domain"
            class="act"
            onClick="xajax_window_submit('edit_domain', xajax.getFormValues('{$form['form_id']}_add_domain_{$record['id']}'), 'editor');"
        >Add DNS domain</a>&nbsp;
    </div>
EOL;


    // Build page links if there are any
    $html .= get_page_links($page, $conf['search_results_per_page'], $count, $window_name, $form['form_id']);


    // Insert the new table into the window
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $response->assign("{$form['form_id']}_domain_count",  "innerHTML", "({$count})");
    $response->assign("{$form['content_id']}", "innerHTML", $html);
    return $response;
}









?>
