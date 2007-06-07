<?



//////////////////////////////////////////////////////////////////////////////
// Function: ws_display()
//
// Description:
//   Displays an interface record and all associated info in the work_space div.
//////////////////////////////////////////////////////////////////////////////
function ws_display($window_name, $form='') {
    global $conf, $self, $onadb;
    global $images, $color, $style, $msgtype;
    $html = '';
    $js = '';
    $debug_val = 3;  // used in the auth() calls to supress logging

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);

    // Load the host record
    if ($form['interface_id'])
        list($status, $rows, $record) = ona_get_interface_record(array('id' => $form['interface_id']));


    $record['ip_addr'] = ip_mangle($record['ip_addr'], 'dotted');

    if ($status or !$rows) {
        array_pop($_SESSION['ona']['work_space']['history']);
        $html .= "<br><center><font color=\"red\"><b>Interface doesn't exist!</b></font></center>";
        $response = new xajaxResponse();
        $response->addAssign("work_space_content", "innerHTML", $html);
        return($response->getXML());
    }

    // Update History Title (and tell the browser to re-draw the history div)
    $history = array_pop($_SESSION['ona']['work_space']['history']);
    $js .= "xajax_window_submit('work_space', ' ', 'rewrite_history');";
    if ($history['title'] == $window_name) {
        $history['title'] = $record['ip_addr'];
        array_push($_SESSION['ona']['work_space']['history'], $history);
    }

    // Create some javascript to refresh the current page
    $refresh = htmlentities(str_replace(array("'", '"'), array("\\'", '\\"'), $history['url']), ENT_QUOTES);
    $refresh = "xajax_window_submit('work_space', '{$refresh}');";

    // Associated Host info
    list($status, $rows, $host) = ona_get_host_record(array('id' => $record['host_id']), '');


    // Subnet description
    list($status, $rows, $subnet) = ona_get_subnet_record(array('id' => $interface['subnet_id']));
    $record['subnet_name'] = $subnet['name'];
    $record['ip_subnet_mask'] = ip_mangle($subnet['ip_mask'], 'dotted');
    $record['ip_subnet_mask_cidr'] = ip_mangle($subnet['ip_mask'], 'cidr');


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
    foreach(array_keys($record) as $key) { $record[$key] = htmlentities($record[$key], ENT_QUOTES); }

    $html .= <<<EOL

    <!-- FORMATTING TABLE -->
    <div style="{$style['content_box']}">
    <table cellspacing="0" border="0" cellpadding="0"><tr>

        <!-- START OF FIRST COLUMN OF SMALL BOXES -->
        <td nowrap="true" valign="top" style="padding-right: 15px;">

            <!-- HOST INFORMATION -->
            <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">

                <tr><td colspan="99" nowrap="true" style="{$style['label_box']}">
                    <!-- LABEL -->
                    <form id="form_interface_{$record['id']}"
                        ><input type="hidden" name="interface_id" value="{$record['id']}"
                        ><input type="hidden" name="js" value="{$refresh}"
                    ></form>
EOL;

    if (auth('host_modify',$debug_val)) {
        $html .= <<<EOL
                    <a title="Edit interface. ID: {$record['id']}"
                       class="act"
                       onClick="xajax_window_submit('edit_interface', xajax.getFormValues('form_interface_{$record['id']}'), 'editor');"
                    ><img src="{$images}/silk/page_edit.png" border="0"></a>
EOL;
    }
    if (auth('host_del',$debug_val)) {
        $html .= <<<EOL
                    <a title="Delete interface. ID: {$record['id']}"
                       class="act"
                       onClick="var doit=confirm('Are you sure you want to delete this interface?');
                                if (doit == true)
                                    xajax_window_submit('edit_interface', xajax.getFormValues('form_interface_{$record['id']}'), 'delete');"
                    ><img src="{$images}/silk/delete.png" border="0"></a>
EOL;
    }
    $html .= <<<EOL
                     &nbsp;{$record['ip_addr']}
                </td></tr>

                <tr>
                    <td align="right" nowrap="true"><b>MAC Address</b>&nbsp;</td>
                    <td class="padding" align="left">{$record['mac_addr']}&nbsp;</td>
                </tr>

                <tr>
                    <td align="right" nowrap="true"><b>Name</b>&nbsp;</td>
                    <td class="padding" align="left">{$record['name']}&nbsp;</td>
                </tr>

                <tr>
                    <td align="right" nowrap="true"><b>Description</b>&nbsp;</td>
                    <td class="padding" align="left">{$record['description']}&nbsp;</td>
                </tr>

            </table>
EOL;





    $html .= <<<EOL
        <!-- END OF FIRST COLUMN OF SMALL BOXES -->
        </td>

        <!-- START OF SECOND COLUMN OF SMALL BOXES -->
        <td valign="top" style="padding-right: 15px;">
EOL;


    $html .= <<<EOL
        <!-- END OF SECOND COLUMN OF SMALL BOXES -->
        </td>


        <!-- START OF THIRD COLUMN OF SMALL BOXES -->
        <td valign="top">
EOL;


    $html .= <<<EOL
        </td>
        <!-- END OF THIRD COLUMN OF SMALL BOXES -->
    </tr></table>
    </div>
    <!-- END OF TOP SECTION -->

EOL;









    // record LIST
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
                    Associated DNS {$tab} <span id="{$form_id}_{$tab}_count"></span>
                </td>

                <td id="{$form_id}_quick_filter" class="padding" align="right" width="100%">
                    <form id="{$form_id}" onSubmit="return false;">
                    <input id="{$form_id}_page" name="page" value="1" type="hidden">
                    <input name="content_id" value="{$content_id}" type="hidden">
                    <input name="form_id" value="{$form_id}" type="hidden">
                    <input name="interface_id" value="{$record['id']}" type="hidden">
                    <div id="{$form_id}_filter_overlay"
                         style="position: relative;
                                display: inline;
                                color: #CACACA;
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

        <div id='{$content_id}'>
            {$conf['loading_icon']}
        </div>
EOL;

    if (auth('record_add',$debug_val)) {
        $html .= <<<EOL

        <!-- ADD DNS RECORD LINK -->
        <div class="act-box" style="padding: 2px 4px; border-top: 1px solid {$color['border']}">
            <form id="form_record_{$record['id']}"
                ><input type="hidden" name="host_id" value="{$record['id']}"
                ><input type="hidden" name="js" value="{$refresh}"
            ></form>

            <a title="Add record"
               class="act"
               onClick="xajax_window_submit('edit_dns_record', xajax.getFormValues('form_record_{$record['id']}'), 'editor');"
            ><img src="{$images}/silk/page_add.png" border="0"></a>&nbsp;

            <a title="Add record"
               class="act"
               onClick="xajax_window_submit('edit_dns_record', xajax.getFormValues('form_record_{$record['id']}'), 'editor');"
            >Add record</a>&nbsp;
        </div>
EOL;
    }

    $html .= "    </div>";

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
    $response->addAssign("work_space_content", "innerHTML", $html);
    if ($js) { $response->addScript($js); }
    return($response->getXML());
}


















?>
