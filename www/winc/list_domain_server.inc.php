<?php



//////////////////////////////////////////////////////////////////////////////
// Function: ws_display_list()
//
// Description:
//   Displays A list of domain server info based on search criteria.
//   Input:  An array from xajaxGetFormValues() from a quick filter form.
//////////////////////////////////////////////////////////////////////////////
function ws_display_list($window_name, $form='') {
    global $conf, $self, $onadb;
    global $images, $color, $style;
    $html = '';
    $js = '';
    $debug_val = 3;  // used in the auth() calls to supress logging

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

    // DISPLAY ALL DOMAINS
    if ($form['server_id']) {
        $where .= $and . 'id IN (SELECT domain_id FROM dns_server_domains WHERE host_id = ' . $onadb->qstr($form['server_id']) . ')';
        $and = " AND ";
    }



    // Do the SQL Query
    $filter = '';
    if ($form['filter']) {
        $filter = $and . 'name LIKE ' . $onadb->qstr('%'.$form['filter'].'%');
    }


    list ($status, $rows, $results) =
        db_get_records(
            $onadb,
            'domains',
            $where . $filter,
            "name ASC",
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
                'domains',
                $where . $filter,
                "",
                0
            );
    }
    $count = $rows;

    $html .= <<<EOL
        <!-- Domain List -->
        <table id="{$form['form_id']}_domain_list" class="list-box" cellspacing="0" border="0" cellpadding="0">

            <!-- Table Header -->
            <tr>
                <td class="list-header" align="center" style="{$style['borderR']};">Domain Name</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Parent Domain</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Role</td>
                <td class="list-header" align="center">&nbsp;</td>
            </tr>
EOL;

        // Loop and display each record
        foreach($results as $record) {
            // Grab some info from the associated domain server record
            list($status, $rows, $domain_server) = ona_get_dns_server_domain_record(array('domain_id' => $record['id'],'host_id' => $form['server_id']));
            list($status, $rows, $parent_zone) = ona_get_domain_record(array('id' => $record['parent_id']));

            $record['role'] = strtoupper($domain_server['role']);
            $record['parent_dns_zone_id'] = $parent_zone['id'];
            $record['parent_dns_zone']    = $parent_zone['fqdn'];
            if($parent_zone['fqdn']) $parent_zone['fqdn'] = '.'.$parent_zone['fqdn'];

            // Escape data for display in html
            foreach(array_keys((array)$record) as $key) {$record[$key] = htmlentities($record[$key], ENT_QUOTES, $conf['php_charset']);}

            $html .= <<<EOL
            <tr onMouseOver="this.className='row-highlight'" onMouseOut="this.className='row-normal'">

                <td class="list-row" align="left">
                    <a title="View domain. ID: {$record['id']}"
                       class="domain"
                       onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain\', \'domain_id=>{$record['id']}\', \'display\')');"
                    >{$record['name']}{$parent_zone['fqdn']}</a>
                </td>

                <td class="list-row" align="left">
                    <a title="View domain. ID: {$record['parent_dns_zone_id']}"
                       class="domain"
                       onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_zone\', \'zone_id=>{$record['parent_dns_zone_id']}\', \'display\')');"
                    >{$record['parent_dns_zone']}</a>&nbsp;
                </td>

                <td class="list-row" align="left">
                    {$record['role']}&nbsp;
                </td>

                <td class="list-row" align="right">
                    <form id="{$form['form_id']}_list_domain_server_{$record['id']}"
                        ><input type="hidden" name="id" value="{$record['id']}"
                        ><input type="hidden" name="domain" value="{$record['id']}"
                        ><input type="hidden" name="server" value="{$form['server_id']}"
                        ><input type="hidden" name="js" value="{$refresh}"
                    ></form>
EOL;

    if (auth('advanced',$debug_val)) {
        $html .= <<<EOL

                    <a title="Edit domain. ID: {$record['id']}"
                       class="act"
                       onClick="xajax_window_submit('edit_domain', xajax.getFormValues('{$form['form_id']}_list_domain_server_{$record['id']}'), 'editor');"
                    ><img src="{$images}/silk/page_edit.png" border="0"></a>&nbsp;

                    <a title="Remove domain assosiation. ID: {$domain_server['id']}"
                       class="act"
                       onClick="var doit=confirm('Are you sure you want to remove this domain from this DNS server?');
                            if (doit == true)
                                xajax_window_submit('edit_domain_server', xajax.getFormValues('{$form['form_id']}_list_domain_server_{$record['id']}'), 'delete');"
                    ><img src="{$images}/silk/page_delete.png" border="0"></a>
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


    // Insert the new html into the content div specified
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $response->assign("{$form['form_id']}_{$tab}_count",  "innerHTML", "({$count})");
    $response->assign($form['content_id'], "innerHTML", $html);
    if ($js) { $response->script($js); }
    return $response;
}











?>
