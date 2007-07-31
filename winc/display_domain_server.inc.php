<?



//////////////////////////////////////////////////////////////////////////////
// Function: ws_display()
// 
// Description:
//   Displays a DNS server record and all associated info in the work_space div.
//////////////////////////////////////////////////////////////////////////////
function ws_display($window_name, $form='') {
    global $conf, $self, $onadb;
    global $images, $color, $style;
    $html = '';
    $js = '';
    $debug_val = 3;  // used in the auth() calls to supress logging
    
    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);
    
    // Load the server record
    list($status, $rows, $record) = ona_get_host_record(array('id' => $form['host_id']));
    if ($status or !$rows) {
        array_pop($_SESSION['ona']['work_space']['history']);
        $html .= "<br><center><font color=\"red\"><b>Server doesn't exist!</b></font></center>"; 
        $response = new xajaxResponse();
        $response->addAssign("work_space_content", "innerHTML", $html);
        return($response->getXML());
    }
    
    // Pick up host information
    //list($status, $rows, $host) = ona_find_host($form['host_id']);
    
    // Update History Title
    $history = array_pop($_SESSION['ona']['work_space']['history']);
    $js .= "xajax_window_submit('work_space', ' ', 'rewrite_history');";
    if ($history['title'] == $window_name) {
        $history['title'] = "DNS server - ". $record['fqdn'];
        array_push($_SESSION['ona']['work_space']['history'], $history);
    }

    // Create some javascript to refresh the current page
    $refresh = htmlentities(str_replace(array("'", '"'), array("\\'", '\\"'), $history['url']), ENT_QUOTES);
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
    foreach(array_keys((array)$record) as $key) { $record[$key] = htmlentities($record[$key], ENT_QUOTES); }
    
    $html .= <<<EOL
    <!-- FORMATTING TABLE -->
    <div style="{$style['content_box']}">
    <table cellspacing="0" border="0" cellpadding="0"><tr>
        
        <!-- START OF FIRST COLUMN OF SMALL BOXES -->
        <td nowrap="true" valign="top" style="padding-right: 15px;">
EOL;
    
    
    // DNS SERVER INFO
    $html .= <<<EOL
            <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">
                <tr><td colspan="99" nowrap="true" style="{$style['label_box']}">
                    DNS server <a title="View host. ID: {$record['id']}"
                           class="nav"
                           onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_host\', \'host_id=>{$record['id']}\', \'display\')');"
                        >{$record['name']}.{$record['domain_fqdn']}</a>
                </td></tr>
            </table>
EOL;
    // END DNS SERVER INFO
    
    
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
        <td valign="top" style="padding-right: 15px;">
EOL;
    
    
    $html .= <<<EOL
        </td>
        <!-- END OF THIRD COLUMN OF SMALL BOXES -->
    </tr></table>
    </div>
    <!-- END OF TOP SECTION -->
EOL;








    // DOMAIN SERVERS LIST
    $tab = 'domain_server';
    $submit_window = "list_{$tab}";
    $form_id = "{$submit_window}_filter_form";
    $_SESSION['ona'][$form_id]['tab'] = $tab;    
    $content_id = "{$window_name}_{$submit_window}";
    $html .= <<<EOL
    <!-- Domain Servers LIST -->
    <div style="border: 1px solid {$color['border']}; margin: 10px 20px;">
        
        <!-- Tab & Quick Filter -->
        <table id="{$form_id}_table" cellspacing="0" border="0" cellpadding="0">
            <tr>
                <td id="{$form_id}_{$tab}_tab" class="table-tab-active">
                    Assigned domains on {$record['name']}.{$record['domain_fqdn']} <span id="{$form_id}_{$tab}_count"></span>
                </td>
                
                <td id="{$form_id}_quick_filter" class="padding" align="right" width="100%">
                    <form id="{$form_id}" onSubmit="return false;">
                    <input id="{$form_id}_page" name="page" value="1" type="hidden">
                    <input name="content_id" value="{$content_id}" type="hidden">
                    <input name="form_id" value="{$form_id}" type="hidden">
                    <input name="server_id" value="{$record['id']}" type="hidden">
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
   
    if (auth('advanced',$debug_val)) {
        $html .= <<<EOL
        
        <div class="act-box" style="padding: 2px 4px; border-top: 1px solid {$color['border']}">
            <form id="{$form['form_id']}_domain_server_{$record['id']}"
                    ><input type="hidden" name="server" value="{$record['id']}"
                    ><input type="hidden" name="js" value="{$refresh}"
            ></form>

            <!-- ADD DOMAIN LINK -->
            <a title="Assign domain"
               class="act"
               onClick="xajax_window_submit('edit_domain_server', xajax.getFormValues('{$form['form_id']}_domain_server_{$record['id']}'), 'editor');"
            ><img src="{$images}/silk/page_add.png" border="0"></a>
            
            <a title="Assign domain"
               class="act"
               onClick="xajax_window_submit('edit_domain_server', xajax.getFormValues('{$form['form_id']}_domain_server_{$record['id']}'), 'editor');"
            >Assign existing domain</a>&nbsp;

            &nbsp;&nbsp;
            
            <!-- ADD DOMAIN LINK -->
            <a title="New DNS domain"
                class="act"
                onClick="xajax_window_submit('edit_domain', xajax.getFormValues('{$form['form_id']}_domain_server_{$record['id']}'), 'editor');"
            ><img src="{$images}/silk/page_add.png" border="0"></a>
    
            <a title="New DNS domain"
                class="act"
                onClick="xajax_window_submit('edit_domain', xajax.getFormValues('{$form['form_id']}_domain_server_{$record['id']}'), 'editor');"
            >Add DNS domain</a>&nbsp;
            
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