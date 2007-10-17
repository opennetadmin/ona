<?



//////////////////////////////////////////////////////////////////////////////
// Function: ws_display_list()
//
// Description:
//   Displays A list of blocks based on search criteria.
//   Input:  An array from xajaxGetFormValues() from a quick filter form.
//////////////////////////////////////////////////////////////////////////////
function ws_display_list($window_name, $form='') {
    global $conf, $self, $onadb;
    global $images, $color, $style;
    $html = '';
    $js = '';

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);

    // Find the "tab" we're on
    $tab = $_SESSION['ona'][$form['form_id']]['tab'];

    // Build js to refresh this list
    $refresh = "xajax_window_submit('{$window_name}', xajax.getFormValues('{$form['form_id']}'), 'display_list');";

    // If it's not a new query, load the previous query from the session
    // into $form and save the current page and filter in the session.
    // Also find/set the "page" we're viewing
    $page = 1;
    if ($form['page'] and is_numeric($form['page'])) {
        $form = array_merge($form, (array)$_SESSION['ona'][$form['form_id']][$tab]['q']);
        $_SESSION['ona'][$form['form_id']][$tab]['page'] = $page = $form['page'];
        $_SESSION['ona'][$form['form_id']][$tab]['filter'] = $form['filter'];
    }

    // Calculate the SQL query offset (based on the page being displayed)
    $offset = ($conf['search_results_per_page'] * ($page - 1));
    if ($offset == 0) { $offset = -1; }

    // Search results go in here
    $results = array();
    $count = 0;

    // Start building the "where" clause for the sql query to find the blocks to display
    $where = "";
    $and = "";

    // DISPLAY ALL BLOCKS
    if ($form['all_flag']) {
        $where .= $and . "id > 0";
        $and = " AND ";
    }

    // BLOCK ID
    if ($form['id']) {
        $where .= $and . "id = " . $onadb->qstr($form['id']);
        $and = " AND ";
    }

    // BLOCK , assume a block descripton
    if ($form['blockname']) {
        $where .= $and . "name LIKE " . $onadb->qstr($form['blockname']);
        $and = " AND ";
    }


    // Wild card .. if $while is still empty, add a 'ID > 0' to it so you see everything.
    if ($where == '')
        $where = 'id > 0';

    // Do the SQL Query
    $filter = '';
    if ($form['filter']) {

        $filter = ' AND name LIKE ' . $onadb->qstr('%'.$form['filter'].'%');
    }
    list ($status, $rows, $results) =
        db_get_records(
            $onadb,
            'blocks',
            $where . $filter,
            "ip_addr_start ASC",
            $conf['search_results_per_page'],
            $offset
        );

    // If we got less than search_results_per_page, add the current offset to it
    // so that if we're on the last page $rows still has the right number in it.
    if ($rows > 0 and $rows < $conf['search_results_per_page']) {
        $rows += ($conf['search_results_per_page'] * ($page - 1));
    }

    // If there were more than $conf['search_results_per_page'] find out how many records there really are
    else if ($rows >= $conf['search_results_per_page']) {
        list ($status, $rows, $records) =
            db_get_records(
                $onadb,
                'blocks',
                $where . $filter,
                "",
                0
            );
    }
    $count = $rows;

    $html .= <<<EOL
        <!-- Block List -->
        <table id="{$form['form_id']}_block_list" class="list-box" cellspacing="0" border="0" cellpadding="0">

            <!-- Table Header -->
            <tr>
                <td class="list-header" align="center" style="{$style['borderR']};">Block Name</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Starting IP</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Ending IP</td>
                <td class="list-header" align="center">&nbsp;</td>
            </tr>
EOL;

        // Loop and display each record
        foreach($results as $record) {
            // Grab some info from the associated block record
            list($status, $rows, $block) = ona_get_block_record(array('id' => $record['id']));
            $num_hosts = 0xffffffff - $block['ip_addr_end'];
            $block['ip_block_end'] = ($block['ip_addr'] + $num_hosts);

            $record['name'] = $block['name'];

            // Convert IP and Netmask to a presentable format
            $record['ip_addr_start'] = ip_mangle($record['ip_addr_start'], 'dotted');
            $record['ip_addr_end'] = ip_mangle($block['ip_addr_end'], 'dotted');

            // Escape data for display in html
            foreach(array_keys($record) as $key) {
                $record[$key] = htmlentities($record[$key], ENT_QUOTES);
            }

            $primary_object_js = "xajax_window_submit('work_space', 'xajax_window_submit(\'display_block\', \'block_id=>{$record['id']}\', \'display\')');";
            $html .= <<<EOL
            <tr onMouseOver="this.className='row-highlight'" onMouseOut="this.className='row-normal'">

                <td class="list-row" align="left">
                    <a title="View block. ID: {$record['id']}"
                       class="nav"
                       onClick="{$primary_object_js}"
                    >{$record['name']}</a>
                </td>

                <td class="list-row" align="left">
                    {$record['ip_addr_start']}&nbsp;
                </td>

                <td class="list-row" align="left">
                    {$record['ip_addr_end']}&nbsp;
                </td>

                <td class="list-row" align="right">
                    <form id="{$form['form_id']}_list_block_{$record['id']}"
                        ><input type="hidden" name="block_id" value="{$record['id']}"
                        ><input type="hidden" name="js" value="{$refresh}"
                    ></form>
EOL;

        if (auth('advanced')) {
            $html .= <<<EOL

                    <a title="Edit block. ID: {$record['id']}"
                       class="act"
                       onClick="xajax_window_submit('edit_block', xajax.getFormValues('{$form['form_id']}_list_block_{$record['id']}'), 'editor');"
                    ><img src="{$images}/silk/brick_edit.png" border="0"></a>&nbsp;
EOL;
        }
        $html .= <<<EOL
                    <a title="Display subnet map"
                       class="act"
                       onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_block_map\', \'ip_block_start=>{$record['ip_addr_start']},ip_block_end=>{$record['ip_addr_end']},id=>{$record['id']}\', \'display\');');"
                    ><img src="{$images}/silk/shape_align_left.png" border="0"></a>&nbsp;
EOL;

        if (auth('advanced')) {
            $html .= <<<EOL

                    <a title="Delete block"
                       class="act"
                       onClick="var doit=confirm('Are you sure you want to delete this block?');
                                if (doit == true)
                                    xajax_window_submit('edit_block', xajax.getFormValues('{$form['form_id']}_list_block_{$record['id']}'), 'delete');"
                    ><img src="{$images}/silk/delete.png" border="0"></a>
EOL;
        }

            $html .= <<<EOL
                    &nbsp;
                </td>

            </tr>

EOL;
    }

    $html .= <<<EOL
    </table>
EOL;
    // Build page links if there are any
    $html .= get_page_links($page, $conf['search_results_per_page'], $count, $window_name, $form['form_id']);

    $js .= <<<EOL
        /* Make sure this table is 100% wide */
        el('{$form['form_id']}_block_list').style.width = el('{$form['form_id']}_table').offsetWidth + 'px';
EOL;

    // If there was only 1 result, and we're about to display results in the "Search Results" window, display it.
    if ($count == 1 and $form['content_id'] == 'search_results_list' and $form['filter'] == '')
        $js .= $primary_object_js;



    // Insert the new html into the content div specified
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $response->addAssign("{$form['form_id']}_{$tab}_count",  "innerHTML", "({$count})");
    $response->addAssign($form['content_id'], "innerHTML", $html);
    if ($js) { $response->addScript($js); }
    return($response->getXML());
}











?>
