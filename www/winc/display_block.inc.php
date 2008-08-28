<?php
// Include map portal functions
include('include/functions_network_map.inc.php');


//////////////////////////////////////////////////////////////////////////////
// Function: ws_display()
//
// Description:
//   Displays a block map in the work_space div.
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
    list($status, $rows, $record) = ona_get_block_record(array('id' => $form['block_id']));
    if ($status or !$rows) {
        array_pop($_SESSION['ona']['work_space']['history']);
        $html .= "<br><center><font color=\"red\"><b>Block doesn't exist!</b></font></center>";
        $response = new xajaxResponse();
        $response->addAssign("work_space_content", "innerHTML", $html);
        return($response->getXML());
    }

    // Update History Title
    $history = array_pop($_SESSION['ona']['work_space']['history']);
    $js .= "xajax_window_submit('work_space', ' ', 'rewrite_history');";
    if ($history['title'] == $window_name) {
        $history['title'] = $record['name'];
        array_push($_SESSION['ona']['work_space']['history'], $history);
    }

    // Create some javascript to refresh the current page
    $refresh = htmlentities(str_replace(array("'", '"'), array("\\'", '\\"'), $history['url']), ENT_QUOTES);
    $refresh = "xajax_window_submit('work_space', '{$refresh}');";

    $record['ip_addr_start'] = ip_mangle($record['ip_addr_start'], 'dotted');
    $record['ip_addr_end']  = ip_mangle($record['ip_addr_end'], 'dotted');

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
EOL;


    // BLOCK INFORMATION
    $html .= <<<EOL
            <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">

                <tr><td colspan="99" nowrap="true" style="{$style['label_box']}">
                    <!-- LABEL -->
                    <form id="form_block_{$record['id']}"
                        ><input type="hidden" name="block_id" value="{$record['id']}"
                        ><input type="hidden" name="js" value="{$refresh}"
                    ></form>
EOL;
    if (auth('advanced',$debug_val)) {
        $html .= <<<EOL

                    <a title="Edit block"
                       class="act"
                       onClick="xajax_window_submit('edit_block', xajax.getFormValues('form_block_{$record['id']}'), 'editor');"
                    ><img src="{$images}/silk/page_edit.png" border="0"></a>&nbsp;

                    <a title="Delete block"
                       class="act"
                       onClick="var doit=confirm('Are you sure you want to delete this block?');
                                if (doit == true)
                                    xajax_window_submit('edit_block', xajax.getFormValues('form_block_{$record['id']}'), 'delete');"
                    ><img src="{$images}/silk/delete.png" border="0"></a>&nbsp;
EOL;
    }
        $html .= <<<EOL
                    {$record['name']}
                </td></tr>
                <tr>
                    <td align="right" nowrap="true"><b>Name</b>&nbsp;</td>
                    <td class="padding" align="left">{$record['name']}&nbsp;</td>
                </tr>

                <tr>
                    <td align="right" nowrap="true"><b>IP start</b>&nbsp;</td>
                    <td class="padding" align="left">
                        {$record['ip_addr_start']}
                        &nbsp;
                    </td>
                </tr>
                <tr>
                    <td align="right" nowrap="true"><b>IP end</b>&nbsp;</td>
                    <td class="padding" align="left">
                        {$record['ip_addr_end']}
                        &nbsp;
                    </td>
                </tr>
                <tr>
                    <td align="right" nowrap="true"><b>Notes</b>&nbsp;</td>
                    <td class="padding" align="left">
                        {$record['notes']}
                        &nbsp;
                    </td>
                </tr>


            </table>
EOL;
    // END BLOCK INFORMATION


    $html .= <<<EOL
        <!-- END OF FIRST COLUMN OF SMALL BOXES -->
        </td>

        <!-- START OF SECOND COLUMN OF SMALL BOXES -->
        <td valign="top" style="padding-right: 15px;">
EOL;

    // SMALL SUBNET MAP

    // Get the numeric IP address of our subnet (we replace the last quad with a .0)
    $ip = ip_mangle(preg_replace('/\.\d+$/', '.0', $record['ip_addr_start']), 'numeric');
    $ip_subnet = ip_mangle($record['ip_addr_start'], 'numeric');

    $html .= <<<EOL
            <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">
                <tr><td colspan="99" nowrap="true">
                    <!-- LABEL -->
                    <div style="{$style['label_box']}">
                        <a title="Display full sized subnet map"
                           class="act"
                           onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_block_map\', \'ip_block_start=>{$record['ip_addr_start']},ip_block_end=>{$record['ip_addr_end']},id=>{$record['id']}\', \'display\');');"
                        ><img src="{$images}/silk/shape_align_left.png" border="0"></a>&nbsp;
                        <a title="Highlight start of block"
                           class="act"
                           onClick="
                             var _el = el('{$ip_subnet}_row_label');
                             if (_el) {
                               if (_el.style.isHighlighted) {
                                 _el.style.backgroundColor = '#000000';
                                 _el.style.isHighlighted = false;
                               }
                               else {
                                 _el.style.backgroundColor = '{$color['bgcolor_map_selected']}';
                                 _el.style.isHighlighted = true;
                               }
                             }
                           "
                        ><img src="{$images}/silk/paintbrush.png" border="0"></a>&nbsp;
                        <b>Block Map</b>
                    </div>
                </td></tr>

                <tr><td colspan="99" nowrap="true">
                    <input type="hidden" id="{$window_name}_zoom" name="zoom" value="7">
                    <div id="{$window_name}_portal" style="position: relative; height: 150px; width: 355px;">
                        <span id="{$window_name}_substrate"></span>
                    </div>
                </td></tr>
            </table>
EOL;

    // Get javascript to setup the map portal mouse handlers
    // Force ip end to be less than ip start to prevent Block highlighting
    $portal_js .= get_portal_js($window_name, $ip, $ip -1);
    //*** Send a fake mouseup event to draw the initial map view ***
    $portal_js .= "el('{$window_name}_portal').myonmouseup('fake event');";

    // END SMALL SUBNET MAP

    $html .= <<<EOL
        <!-- END OF SECOND COLUMN OF SMALL BOXES -->
        </td>

        <!-- START OF THIRD COLUMN OF SMALL BOXES -->
        <td valign="top" style="padding-right: 15px;">
EOL;


    $html .= <<<EOL
        </td>
        <!-- END OF THIRD COLUMN OF SMALL BOXES -->
    </tr></table>
    </div>
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
                <td id="{$form_id}_subnets_tab" class="table-tab-active">
                    Associated {$tab} <span id="{$form_id}_{$tab}_count"></span>
                </td>

                <td id="{$form_id}_quick_filter" class="padding" align="right" width="100%">
                    <form id="{$form_id}" onSubmit="return false;">
                    <input id="{$form_id}_page" name="page" value="1" type="hidden">
                    <input name="content_id" value="{$content_id}" type="hidden">
                    <input name="form_id" value="{$form_id}" type="hidden">
                    <input name="ip_subnet" value="{$record['ip_addr_start']}" type="hidden">
                    <input name="ip_subnet_thru" value="{$record['ip_addr_end']}" type="hidden">
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
    $response->addAssign("work_space_content", "innerHTML", $html);
    if ($js) { $response->addScript($js . $portal_js); }
    return($response->getXML());
}


















?>