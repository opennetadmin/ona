<?php



//////////////////////////////////////////////////////////////////////////////
// Function: ws_display()
//
// Description:
//   Displays a vlan record and all associated info in the work_space div.
//////////////////////////////////////////////////////////////////////////////
function ws_display($window_name, $form='') {
    global $conf, $self, $onadb;
    global $images, $color, $style;
    $html = '';
    $js = '';
    $debug_val = 3;  // used in the auth() calls to supress logging

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);

    // Load the host record
    list($status, $rows, $record) = ona_get_vlan_record(array('id' => $form['vlan_id']));
    if ($status or !$rows) {
        array_pop($_SESSION['ona']['work_space']['history']);
        $html .= "<br><center><font color=\"red\"><b>VLAN doesn't exist!</b></font></center>";
        $response = new xajaxResponse();
        $response->assign("work_space_content", "innerHTML", $html);
        return $response;
    }

    // Get campus info
    list($status, $rows, $vlan_campus) = ona_get_vlan_campus_record(array('id' => $record['vlan_campus_id']));
    $record['vlan_campus_name'] = $vlan_campus['name'];
    $record['vlan_campus_id'] = $vlan_campus['id'];

    // Update History Title (and tell the browser to re-draw the history div)
    $history = array_pop($_SESSION['ona']['work_space']['history']);
    $js .= "xajax_window_submit('work_space', ' ', 'rewrite_history');";
    if ($history['title'] == $window_name) {
        $history['title'] = $record['name'];
        array_push($_SESSION['ona']['work_space']['history'], $history);
    }

    // Create some javascript to refresh the current page
    $refresh = htmlentities(str_replace(array("'", '"'), array("\\'", '\\"'), $history['url']), ENT_QUOTES, $conf['php_charset']);
    $refresh = "xajax_window_submit('work_space', '{$refresh}');";

    $style['content_box'] = <<<EOL
        margin: 10px 20px;
        padding: 2px 4px;
        background-color: #FFFFFF;
        vertical-align: top;
EOL;

    $style['label_box'] = <<<EOL
        font-weight: bold;
        padding: 2px 4px;
        border: solid 1px {$color['border']};
        background-color: {$color['window_content_bg']};
EOL;

    // Escape data for display in html
    foreach(array_keys($record) as $key) { $record[$key] = htmlentities($record[$key], ENT_QUOTES, $conf['php_charset']); }

    // Create a div for workspace plugins to live
    $html .= "<div id='wsplugins' style='margin: 10px;'>";

    $html .= <<<EOL
    <!-- FORMATTING TABLE -->
      <div id="vlan_detail" class="ws_plugin_content">

EOL;

    // VLAN INFORMATION
    $html .= <<<EOL
            <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">

                <tr><td colspan="99" nowrap="true" style="{$style['label_box']}">
                    <!-- LABEL -->
                    <form id="form_vlan_{$record['id']}"
                        ><input type="hidden" name="vlan_id" value="{$record['id']}"
                        ><input type="hidden" name="js" value="{$refresh}"
                    ></form>
EOL;

    if (auth('vlan_del',$debug_val)) {
        $html .= <<<EOL
                    <a title="Edit Vlan. ID: {$record['id']}"
                       class="act"
                       onClick="xajax_window_submit('edit_vlan', xajax.getFormValues('form_vlan_{$record['id']}'), 'editor');"
                    ><img src="{$images}/silk/page_edit.png" border="0"></a>&nbsp;

                    <a title="Delete Vlan. ID: {$record['id']}"
                       class="act"
                       onClick="var doit=confirm('Are you sure you want to delete this vlan?');
                                if (doit == true)
                                    xajax_window_submit('edit_vlan', xajax.getFormValues('form_vlan_{$record['id']}'), 'delete');"
                    ><img src="{$images}/silk/delete.png" border="0"></a>&nbsp;
                    {$record['name']}</a>
EOL;
    }
    else {
        $html .= "                    &nbsp;{$record['name']}";
    }

        $html .= <<<EOL
                </td></tr>

                <tr>
                    <td align="right" nowrap="true"><b>Vlan Campus</b>&nbsp;</td>
                    <td class="padding" align="left">
                        <a title="View Vlan Campus"
                           class="nav"
                           onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_vlan_campus\', \'vlan_campus_id=>{$record['vlan_campus_id']}\', \'display\')');"
                        >{$record['vlan_campus_name']}</a>
                    </td>
                </tr>

                <tr>
                    <td align="right" nowrap="true"><b>Vlan Name</b>&nbsp;</td>
                    <td class="padding" align="left">{$record['name']}&nbsp;</td>
                </tr>

                <tr>
                    <td align="right" nowrap="true"><b>Vlan Number</b>&nbsp;</td>
                    <td class="padding" align="left">{$record['number']}&nbsp;</td>
                </tr>

            </table>
      </div>
EOL;
    // END VLAN INFORMATION


    // extra stuff to pass to ws_plugins
    $extravars['refresh']=$refresh;
    $extravars['window_name']=$window_name;

    $wspl = workspace_plugin_loader('custom_attributes',$record,$extravars);
    $html .= $wspl[0]; $js .= $wspl[1];
    $html .="</div>";

    $html .= <<<EOL
    </div>
    <br style="clear:both;">
    <!-- END OF TOP SECTION -->
EOL;











    // SUBNET LIST
    $tab = 'subnets';
    $submit_window = "list_{$tab}";
    $form_id = "{$submit_window}_filter_form";
    $_SESSION['ona'][$form_id]['tab'] = $tab;
    $content_id = "{$window_name}_{$submit_window}";
    $html .= <<<EOL
    <!-- SUBNET LIST -->
    <div style="border: 1px solid {$color['border']}; margin: 10px 20px;">

        <!-- Tab & Quick Filter -->
        <table id="{$form_id}_table" cellspacing="0" border="0" cellpadding="0">
            <tr>
                <td id="{$form_id}_{$tab}_tab" class="table-tab-active">
                    Associated {$tab} <span id="{$form_id}_{$tab}_count"></span>
                </td>

                <td id="{$form_id}_quick_filter" class="padding" align="right" width="100%">
                    <form id="{$form_id}" onSubmit="return false;">
                    <input id="{$form_id}_page" name="page" value="1" type="hidden">
                    <input name="content_id" value="{$content_id}" type="hidden">
                    <input name="form_id" value="{$form_id}" type="hidden">
                    <input name="vlan_id" value="{$record['id']}" type="hidden">
                    <div id="{$form_id}_filter_overlay"
                         title="Filter"
                         style="position: relative;
                                display: inline;
                                color: #CACACA;
                                cursor: text;"
                         onClick="this.style.display = 'none'; el('{$form_id}_filter').focus();"
                    >Name</div>
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

        <div id='{$content_id}'>
            {$conf['loading_icon']}
        </div>

    </div>

EOL;
    $js .= <<<EOL
        /* Setup the quick filter */
        el('{$form_id}_filter_overlay').style.left = (el('{$form_id}_filter_overlay').offsetWidth + 10) + 'px';
        {$form_id}_last_search = '';

        /* Tell the browser to load/display the list */
        xajax_window_submit('{$submit_window}', xajax.getFormValues('{$form_id}'), 'display_list');
EOL;



    // Insert the new html into the window
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $response->assign("work_space_content", "innerHTML", $html);
    if ($js) { $response->script($js); }
    return $response;
}




?>
