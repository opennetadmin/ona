<?php



//////////////////////////////////////////////////////////////////////////////
// Function: ws_display()
//
// Description:
//   Displays a host record and all associated info in the work_space div.
//////////////////////////////////////////////////////////////////////////////
function ws_display($window_name, $form='') {
    global $conf, $self, $onadb;
    global $images, $color, $style;
    $html = '';
    $js = '';
    $debug_val = 3;  // used in the auth() calls to suppress logging

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);

    // Load the domain record
    list($status, $rows, $record) = ona_get_domain_record(array('id' => $form['domain_id']));
    if ($status or !$rows) {
        array_pop($_SESSION['ona']['work_space']['history']);
        $html .= "<br><center><font color=\"red\"><b>Domain doesn't exist!</b></font></center>";
        $response = new xajaxResponse();
        $response->assign("work_space_content", "innerHTML", $html);
        return $response;
    }

    // Update History Title
    $history = array_pop($_SESSION['ona']['work_space']['history']);
    $js .= "xajax_window_submit('work_space', ' ', 'rewrite_history');";
    if ($history['title'] == $window_name) {
        $history['title'] = $record['fqdn'];
        array_push($_SESSION['ona']['work_space']['history'], $history);
    }

    // Create some javascript to refresh the current page
    $refresh = htmlentities(str_replace(array("'", '"'), array("\\'", '\\"'), $history['url']), ENT_QUOTES, $conf['php_charset']);
    $refresh = "xajax_window_submit('work_space', '{$refresh}');";

    // Get associated info
    if (isset($record['parent_id'])) {
        list($status, $rows, $parent_domain) = ona_get_domain_record(array('id' => $record['parent_id']));
        $parent_domain['name'] = ona_build_domain_name($parent_domain['id']);
    } else {
        $parent_domain = "";
    }

    // Find the primary_master host to see if it is valid
    list($status,$rows,$primaster_host) = ona_find_dns_record($record['primary_master']);
    if ($primaster_host['id'] === 0) $not_a_primaster = 1;

    $style['content_box'] = <<<EOL
        margin: 10px 20px;
        padding: 2px 4px;
        background-color: #FFFFFF;
EOL;

    $style['label_box'] = <<<EOL
        font-weight: bold;
        padding: 2px 4px;
        border: solid 1px {$color['border']};
        background-color: {$color['window_content_bg']};
EOL;

    // Escape data for display in html
    foreach(array_keys($record) as $key) { $record[$key] = htmlentities($record[$key], ENT_QUOTES, $conf['php_charset']); }
    foreach(array_keys((array)$parent_domain) as $key) { $parent_domain[$key] = htmlentities($parent_domain[$key], ENT_QUOTES, $conf['php_charset']); }


    $html .= <<<EOL
    <!-- FORMATTING TABLE -->
    <div style="{$style['content_box']}">
    <table cellspacing="0" border="0" cellpadding="0"><tr>

        <!-- START OF FIRST COLUMN OF SMALL BOXES -->
        <td nowrap="true" valign="top" style="padding-right: 15px;">
EOL;


    // DOMAIN INFORMATION BOX
    $html .= <<<EOL
        <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">
            <tr>
            <td colspan="99" nowrap="true">
                <!-- LABEL -->
                    <form id="form_domain_{$record['id']}"
                        ><input type="hidden" name="id" value="{$record['id']}"
                        ><input type="hidden" name="js" value="{$refresh}"
                    ></form>
                    <div style="{$style['label_box']}">

EOL;

    if (auth('advanced',$debug_val)) {
        $html .= <<<EOL
                            <a title="Edit domain. ID: {$record['id']}"
                               onClick="xajax_window_submit('edit_domain', xajax.getFormValues('form_domain_{$record['id']}'), 'editor');"
                            ><img src="{$images}/silk/page_edit.png" border="0"></a>
                            <a title="Delete domain. ID: {$record['id']}"
                               class="linkact"
                               onClick="var doit=confirm('Are you sure you want to delete this domain?');
                                        if (doit == true)
                                            xajax_window_submit('edit_domain', xajax.getFormValues('form_domain_{$record['id']}'), 'delete');"
                            ><img src="{$images}/silk/delete.png"></a>
EOL;
    }

        $html .= <<<EOL
                {$record['fqdn']}
                </div>
            </td>
            </tr>
EOL;

    if ($parent_domain['id']) {
    $html .= <<<EOL
            <tr>
                <td align="right" nowrap="true"><b>Parent Domain</b>&nbsp;</td>
                <td class="padding" align="left">
                    <a title="View domain. ID: {$parent_domain['id']}"
                       class="domain"
                       onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain\', \'domain_id=>{$parent_domain['id']}\', \'display\')');"
                    >{$parent_domain['name']}</a>
                </td>
            </tr>
            <tr><td colspan="2" align="left" nowrap="true">&nbsp;</td></tr>
EOL;
    }

    $html .= <<<EOL
            <tr>
                <td colspan="2" align="left" nowrap="true"><b><u>Domain SOA Parameters</u></b>&nbsp;</td>
            </tr>

EOL;

    if ($record['primary_master']) {
        if ($not_a_primaster) {
            $record['primary_master'] = "<span style='background-color: #FFDDDD;' title='INFO: This FQDN is not defined in the database.'><img src='{$images}/silk/error.png' border='0'> {$record['primary_master']}</span>";
        }
        $html .= <<<EOL
            <tr>
                <td align="right" nowrap="true"><b>Primary Master</b>&nbsp;</td>
                <td class="padding" align="left" onClick="xajax_window_submit('edit_domain', xajax.getFormValues('form_domain_{$record['id']}'), 'editor');">
                    {$record['primary_master']}&nbsp;
                </td>
            </tr>
EOL;
    } else {
        $html .= <<<EOL
            <tr style="background-color: #FFDDDD;" title="There is no defined primary master server!">
                <td align="right" nowrap="true"><b>Primary Master</b>&nbsp;</td>
                <td nowrap="true">
                    <img src='{$images}/silk/error.png' border='0'> Please assign a Primary Master server!
                </td>
            </tr>
EOL;
    }


    $html .= <<<EOL
            <tr>
                <td align="right" nowrap="true"><b>Refresh</b>&nbsp;</td>
                <td class="padding" align="left">
                    {$record['refresh']}&nbsp;
                </td>
            </tr>

            <tr>
                <td align="right" nowrap="true"><b>Retry</b>&nbsp;</td>
                <td class="padding" align="left">
                    {$record['retry']}&nbsp;
                </td>
            </tr>

            <tr>
                <td align="right" nowrap="true"><b>Expiry</b>&nbsp;</td>
                <td class="padding" align="left">
                    {$record['expiry']}&nbsp;
                </td>
            </tr>

            <tr>
                <td align="right" nowrap="true"><b>Minimum</b>&nbsp;</td>
                <td class="padding" align="left">
                    {$record['minimum']}&nbsp;
                </td>
            </tr>

            <tr>
                <td align="right" nowrap="true"><b>Default TTL</b>&nbsp;</td>
                <td class="padding" align="left">
                    {$record['default_ttl']}&nbsp;
                </td>
            </tr>
        </table>
EOL;
    // END DOMAIN INFORMATION BOX


    $html .= <<<EOL
        <!-- END OF FIRST COLUMN OF SMALL BOXES -->
        </td>

        <!-- START OF SECOND COLUMN OF SMALL BOXES -->
        <td valign="top" style="padding-right: 15px;">
EOL;


    // DNS SERVERS BOX
    $html .= <<<EOL
        <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">
            <tr>
                <td colspan="99" nowrap="true" style="{$style['label_box']}">DNS servers&nbsp;</td>
            </tr>

EOL;

    // Get a list of servers, and loop through them
    list($status, $rows, $domainservers) = db_get_records($onadb, 'dns_server_domains', array('domain_id' => $record['id']),'role');
    if ($rows) {
        foreach ($domainservers as $domainserver) {
            $domainserver['role'] = strtoupper($domainserver['role']);

            list($status, $rows, $host) = ona_find_host($domainserver['host_id']);
            $host['fqdn'] = htmlentities($host['fqdn'], ENT_QUOTES, $conf['php_charset']);
            $html .= <<<EOL
                <tr onMouseOver="this.className='row-highlight';"
                    onMouseOut="this.className='row-normal';">

                    <td align="left" nowrap="true">
                        <a title="View server. ID: {$host['id']}"
                           class="nav"
                           onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain_server\', \'host_id=>{$host['id']}\', \'display\')');"
                        >{$host['fqdn']}</a>&nbsp;
                     </td>
                     <td align="left" nowrap="true" style="border-left: 1px solid; border-left-color: #aaaaaa;padding-left: 3px;">
                            {$domainserver['role']}
                    </td>
                     <td align="right" nowrap="true">
                        <form id="{$form['form_id']}_domain_serv_{$domainserver['id']}"
                                ><input type="hidden" name="server" value="{$domainserver['host_id']}"
                                ><input type="hidden" name="domain" value="{$domainserver['domain_id']}"
                                ><input type="hidden" name="js" value="{$refresh}"
                        ></form>
EOL;

            if (auth('advanced',$debug_val)) {
                $html .= <<<EOL

                        &nbsp;<a title="Remove domain assignment"
                           class="linkact"
                           onClick="var doit=confirm('Are you sure you want to remove this domain from this DNS server?');
                           if (doit == true)
                                xajax_window_submit('edit_domain_server', xajax.getFormValues('{$form['form_id']}_domain_serv_{$domainserver['id']}'), 'delete');"
                        ><img src="{$images}/silk/page_delete.png"></a>
EOL;
            }

            $html .= <<<EOL
                   </td>

                </tr>
EOL;
        }
    } else {
            $html .= <<<EOL
            <tr style="background-color: #FFDDDD;" title="There are no defined servers for this domain!">
                <td colspan=10 nowrap="true">
                    <img src="{$images}/silk/error.png"> Please add a server for this domain!
                </td>
EOL;
    }

    if (auth('advanced',$debug_val)) {
        $html .= <<<EOL

                <tr>
                    <td colspan="3" align="left" valign="middle" nowrap="true" class="act-box">
                        <form id="form_domain_server_{$record['id']}"
                            ><input type="hidden" name="domain" value="{$record['name']}"
                            ><input type="hidden" name="js" value="{$refresh}"
                        ></form>

                        <a title="Assign server"
                           class="linkact"
                           onClick="xajax_window_submit('edit_domain_server', xajax.getFormValues('form_domain_server_{$record['id']}'), 'editor');"
                        ><img src="{$images}/silk/page_add.png">Assign to server</a>

                    </td>
                </tr>
EOL;
    }

    $html .= "          </table>";

    // END DNS SERVERS BOX


    $html .= <<<EOL
        <!-- END OF SECOND COLUMN OF SMALL BOXES -->
        </td>

        <!-- START OF THIRD COLUMN OF SMALL BOXES -->
        <td valign="top" style="padding-right: 15px;">
EOL;

    // extra stuff to pass to ws_plugins
    $extravars['refresh']=$refresh;
    $extravars['window_name']=$window_name;


    // Get all the plugin based worspace items
    $wspl_list = plugin_list('wspl_item');

    // Load all the dynamic plugins
    foreach ($wspl_list as $p) {
        $wspl = workspace_plugin_loader($p['path'],$record,$extravars);
        $html .= $wspl[0]; $js .= $wspl[1];
    }


    $html .= <<<EOL
        </td>
        <!-- END OF THIRD COLUMN OF SMALL BOXES -->
    </tr></table>
    </div>
    <!-- END OF TOP SECTION -->
EOL;




    // LIST
    $tab = 'records';
    $submit_window = "list_{$tab}";
    $form_id = "{$submit_window}_filter_form";
    $_SESSION['ona'][$form_id]['tab'] = $tab;
    $content_id = "{$window_name}_{$submit_window}";
    $html .= <<<EOL
    <!-- RECORD LIST -->
    <div style="border: 1px solid {$color['border']}; margin: 10px 20px;">

        <!-- Tab & Quick Filter -->
        <table id="{$form_id}_table" cellspacing="0" border="0" cellpadding="0">
            <tr>
                <td id="{$form_id}_{$tab}_tab" class="table-tab-active">
                    Associated {$tab} <span id="{$form_id}_{$tab}_count"></span>
                </td>

                <td id="{$form_id}_quick_filter" class="padding" align="right" width="100%">
EOL;
        $html .= <<<EOL
                    <form id="{$form_id}" onSubmit="return false;" autocomplete="off">
                    <input id="{$form_id}_page" name="page" value="1" type="hidden">
                    <input name="content_id" value="{$content_id}" type="hidden">
                    <input name="form_id" value="{$form_id}" type="hidden">
                    <input name="domain_id" value="{$record['id']}" type="hidden">
                    <input
                        id="{$form_id}_filter"
                        name="filter"
                        class="filter"
                        type="text"
                        value=""
                        size="10"
                        maxlength="20"
                        alt="Quick Filter"
                        placeholder="Name"
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

        <div id='{$content_id}'>
            {$conf['loading_icon']}
        </div>
EOL;

    if (auth('dns_record_add',$debug_val)) {
        $html .= <<<EOL

        <!-- List by IP Address LINK -->
        <div class="act-box" style="padding: 2px 4px; border-top: 1px solid {$color['border']}">
            <form id="form_dns_add_{$record['id']}">
                <input type="hidden" name="js" value="{$refresh}">
                <input type="hidden" name="domain_id" value="{$record['id']}">
            </form>

            <a title="Add DNS Record"
               class="linkact"
               onClick="xajax_window_submit('edit_record', xajax.getFormValues('form_dns_add_{$record['id']}'), 'editor');"
            ><img src="{$images}/silk/page_add.png">Add a new DNS record</a>&nbsp;

        </div>
EOL;
    }

    $html .= <<<EOL
    </div>
EOL;

    // If we have a build type set, then display the output div
    if ($conf['build_dns_type'] && auth('dns_record_add',$debug_val)) {

        // Get a list of the views so we can build a select option
        if ($conf['dns_views']) {
            list($status, $rows, $recs) = db_get_records($onadb, 'dns_views', 'id >= 0', 'name');
            $dns_view_list = '';
            foreach ($recs as $rec) {
                $rec['name'] = htmlentities($rec['name']);
                $dns_view_list .= "<option value=\"{$rec['id']}\">{$rec['name']}</option>\n";
            }
            $html .= <<<EOL
    <div style="margin: 10px 20px;padding-left: 8px;">
        <form>
        Show config for DNS view: <select name="build_dns_view"
                id="build_dns_view"
                class="edit"
                onchange="xajax_window_submit('{$window_name}', 'fqdn=>{$record['fqdn']},view=>'+el('build_dns_view').value , 'display_config');"
        >
            {$dns_view_list}
        </select>
        </form>
    </div>
EOL;

        }


        $html .= <<<EOL
    <div id="confoutputdiv" style="border: 1px solid rgb(26, 26, 26); margin: 10px 20px;padding-left: 8px;overflow:hidden;width: 100px;"><pre style='font-family: monospace;overflow-y:auto;' id="confoutput"><center>Generating configuration...</center><br>{$conf['loading_icon']}</pre></div>
EOL;

        $js .= "xajax_window_submit('{$window_name}', 'fqdn=>{$record['fqdn']}', 'display_config');";
    }

    $js .= <<<EOL
        /* Setup the quick filter */
        {$form_id}_last_search = '';

        /* Tell the browser to load/display the list */
        xajax_window_submit('{$submit_window}', xajax.getFormValues('{$form_id}'), 'display_list');

        setTimeout('el(\'confoutputdiv\').style.width = el(\'{$form_id}_table\').offsetWidth-8+\'px\';',900);
EOL;



    // Insert the new html into the window
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $response->assign("work_space_content", "innerHTML", $html);
    if ($js) { $response->script($js); }
    return $response;
}




//////////////////////////////////////////////////////////////////////////////
// Function: ws_display_config()
//
// Description:
//   generates the configuration from the database.
//////////////////////////////////////////////////////////////////////////////
function ws_display_config($window_name, $form='') {
    global $conf;
    $html = '';
    $js = '';

    // If the user supplied an array in a string, transform it into an array
    $form = parse_options_string($form);

    // MP: This could be slow depending on the size of the database.  maybe make it a button.. having no build_dns_type turns it off
    // It expects to be passed the domain name as domain= to the module
    if ($conf['build_dns_type'] && auth('dns_record_add',$debug_val)) {
        switch (strtolower($conf['build_dns_type'])) {
            case "bind":
                $dns_module_name = 'build_bind_domain';
                break;
            case "tinydns":
                $dns_module_name = 'build_tinydns_conf';
                break;
        }
        list($status, $output) = run_module("{$dns_module_name}", array('domain' => $form['fqdn'],'view' => $form['view']));
        // Display the config if it ran ok
        if (!$status) {
            $html .= $output;
        } else {
            $html .= "There was a problem generating the configuration.<br>{$output}";
        }
    }

    // Insert the new html into the window
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $response->assign("confoutput", "innerHTML", $html);
    if ($js) { $response->script($js); }
    return $response;
}




?>
