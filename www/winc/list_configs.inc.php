<?php



//////////////////////////////////////////////////////////////////////////////
// Function: ws_display()
//
// Description:
//   Displays A list of configs based on search criteria.
//   Input:  An array from xajaxGetFormValues() from a quick filter form.
//////////////////////////////////////////////////////////////////////////////
function ws_display_list($window_name, $form='') {
    global $conf, $self, $onadb, $baseURL;
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

    // Start building the "where" clause for the sql query to find the records to display
    $where = '';
    $and = '';

    // DISPLAY ALL CONFIGS
    if ($form['all_flag']) {
        $where .= $and . "id > 0";
        $and = " AND ";
    }

    if ($form['host_id']) {
        $where .= 'host_id = '.$form['host_id'];
        $and = " AND ";
    }

    // Do the SQL Query
    $filter = '';
    if ($form['filter']) {
        $filter = $and . ' configuration_type_id in (select id from configuration_types where name like "%' . $form['filter'] . '%")';
    }
    list ($status, $rows, $results) =
        db_get_records(
            $onadb,
            'configurations',
            $where . $filter,
            'configuration_type_id ASC, ctime DESC',
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
                'configurations',
                $where . $filter,
                "",
                0
            );
    }
    $count = $rows;

    $html .= <<<EOL
        <!-- Config List -->
        <table id="{$form['form_id']}_config_list" class="list-box" cellspacing="0" border="0" cellpadding="0">

            <!-- Table Header -->
            <tr>
                <td class="list-header" align="center" style="{$style['borderR']};">A</td>
                <td class="list-header" align="center" style="{$style['borderR']};">B</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Date</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Type</td>
                <td class="list-header" align="center" style="{$style['borderR']};">MD5 Checksum</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Size (chars)</td>
                <td class="list-header" align="center">&nbsp;</td>
            </tr>
EOL;

        // Loop and display each record
        foreach($results as $record) {
            $id++; // Counter used for comparison seleciton

            // Grab some info from the associated record
            list($status, $rows, $ctype) = ona_get_config_type_record(array('id' => $record['configuration_type_id']));
            $record['config_type_name']         = $ctype['name'];

            // Escape data for display in html
            foreach(array_keys($record) as $key) {
                $record[$key] = htmlentities($record[$key], ENT_QUOTES, $conf['php_charset']);
            }

            // MP: FIXME still not working right for encoded stings.. always returns 0
            $confsize = mb_strlen(html_entity_decode($record['config_body'], ENT_QUOTES, $conf['php_charset']), $conf['php_charset']);

            $html .= <<<EOL
            <tr onMouseOver="this.className='row-highlight'" onMouseOut="this.className='row-normal'">

                <td align="center" class="list-row" style="padding-right: 4px; width: 20px;"
                  ><input id="old{$id}" name="old" type="radio" value="{$record['id']}"
                    onClick="
                        var tmp = 1; var obj = el('new' + tmp);
                        while (obj) {
                            obj.style.visibility = (tmp <= {$id}) ? 'visible' : 'hidden';
                            if (tmp > {$id}) obj.checked = false;
                            obj = el('new' + tmp++);
                        }"
                  >
                </td>

                <td class="list-row" align="center" style="width: 20px;">
                    <input id="new{$id}" style="visibility: hidden;" name="new" type="radio" value="{$record['id']}">
                </td>

                <td class="list-row" align="center">
                    {$record['ctime']}&nbsp;
                </td>

                <td class="list-row" align="center">
                    {$record['config_type_name']}&nbsp;
                </td>

                <td class="list-row" align="center">
                    {$record['md5_checksum']}&nbsp;
                </td>

                <td class="list-row" align="center">
                    {$confsize}
                </td>
                <td class="list-row" align="right">
                    <form id="{$form['form_id']}_list_configs_{$record['id']}">
                        <input type="hidden" name="config_id" value="{$record['id']}">
                        <input type="hidden" name="host" value="{$record['host_id']}">
                        <input type="hidden" name="type" value="{$record['config_type_name']}">
                        <input type="hidden" name="commit" value="y">
                        <input type="hidden" name="js" value="{$refresh}">
                    </form>
EOL;

    if (auth('host_config_admin',$debug_val)) {
        $html .= <<<EOL

                    <a title="View config. ID: {$record['id']}"
                       class="act"
                       onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_config_text\', \'host_id=>{$record['host_id']},type_id=>{$record['configuration_type_id']},displayconf=>{$record['id']}\', \'display\')');"
                    ><img src="{$images}/silk/zoom.png" border="0"></a>&nbsp;

                    <a title="Download config"
                       class="act"
                       target="null"
                       href="{$baseURL}/config_dnld.php?config_id={$record['id']}&download=1"
                    ><img src="{$images}/silk/disk.png" alt="Download config" border="0"></a>&nbsp;

                    <a title="Delete config"
                       class="nav"
                       onClick="var doit=confirm('Are you sure you want to delete this config record?');
                                if (doit == true)
                                    xajax_window_submit('display_config_text', xajax.getFormValues('{$form['form_id']}_list_configs_{$record['id']}'), 'delete');"
                    ><img src="{$images}/silk/delete.png" alt="Delete config" border="0"></a>&nbsp;

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
    $response->addAssign("{$form['form_id']}_{$tab}_count",  "innerHTML", "({$count})");
    $response->addAssign($form['content_id'], "innerHTML", $html);
    if ($js) { $response->addScript($js); }
    return($response->getXML());
}











?>
