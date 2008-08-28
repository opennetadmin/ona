<?php
global $conf, $self, $onadb ;
global $font_family, $color, $style, $images;

//$options['subnet_id'] = 424685;
//list($status, $rows, $subnet) = ona_get_subnet_record(array('id' => $options['subnet_id']));

// Set the window title:
$window['title'] = "List by IP: {$subnet['name']}";

// Load some html into $window['html']
    // HOST LIST
    $tab = 'hosts';
    $submit_window = "list_{$tab}";
    $form_id = "{$submit_window}_filter_form";
    $_SESSION['ona'][$form_id]['tab'] = $tab;
    $content_id = "{$window_name}_{$submit_window}";
    $window['html'] .= <<<EOL
    <!-- HOST LIST -->
    <div style="border: 1px solid {$color['border']}; height : 700px; overflow : auto">
        
        <!-- Tab & Quick Filter -->
        <table id="{$form_id}_table" cellspacing="0" border="0" cellpadding="0">
            <tr>
                <td id="{$form_id}_{$tab}_tab" >
                   <input id="{$form_id}_{$tab}_count" type="hidden">
                </td>
            </tr>
        </table>
         <div id='{$content_id}'>
            {$conf['loading_icon']}
        </div>
    </div>        
EOL;

// Define javascript to run after the window is created
$window['js'] = <<<EOL
     /* Tell the browser to load/display the list */
     xajax_window_submit('app_full_list', 'subnet_id=>{$options['subnet_id']},form_id=>{$form_id},content_id=>{$content_id}', 'display_list');
     
EOL;

//////////////////////////////////////////////////////////////////////////////
// Function: ws_display()
// 
// Description:
//   Displays A list of hosts based on search criteria.
//   Input:  An array from xajaxGetFormValues() from a quick filter form.
//////////////////////////////////////////////////////////////////////////////
function ws_display($window_name, $form='') {

global $conf, $self, $onadb;
global $font_family, $color, $style, $images;
    
// If the user supplied an array in a string, build the array and store it in $form
$form = parse_options_string($form);
printmsg("ws_display in app_full_list.inc.php called with: " . print_r($form,1), 3);

$window['title']= "Host List by IP";
$window['js'] = <<<EOL
        /* Put a minimize icon in the title bar */
        el('{$window_name}_title_r').innerHTML = 
            '&nbsp;<a onClick="toggle_window(\'{$window_name}\');" title="Minimize window" style="cursor: pointer;"><img src="{$images}/icon_minimize.gif" border="0" /></a>' +
            el('{$window_name}_title_r').innerHTML;
        
        /* Put a help icon in the title bar */
        el('{$window_name}_title_r').innerHTML = 
            '&nbsp;<a href="{$_ENV['help_url']}{$window_name}" target="null" title="Help" style="cursor: pointer;"><img src="{$images}/silk/help.png" border="0" /></a>' +
            el('{$window_name}_title_r').innerHTML;

EOL;

// Load some html into $window['html']
    // HOST LIST
    $tab = 'hosts_by_ip';
    $submit_window = "list_{$tab}";
    $form_id = "{$submit_window}_filter_form";
    $_SESSION['ona'][$form_id]['tab'] = $tab;
    $content_id = "{$window_name}_{$submit_window}";

    if(array_key_exists('ip',$form) && array_key_exists('ip_thru',$form) ) {
        // Set the window title:
        $window['subtitle'] = "Range: {$form['ip']} to {$form['ip_thru']}";
        
        // Define javascript to run after the window is created
        $window['js'] .= <<<EOL
         /* Tell the browser to load/display the list */
         xajax_window_submit('app_full_list', 'ip=>{$form['ip']},ip_thru=>{$form['ip_thru']},form_id=>{$form_id},content_id=>{$content_id}', 'display_list');
         
EOL;
    
    }
    if(array_key_exists('subnet_id',$form)) {
        list($status, $rows, $subnet) = ona_get_subnet_record(array('id' => $form['subnet_id']));
        
        // Set the window title:
        $window['subtitle'] = "Subnet: {$subnet['name']}";
        
        // Define javascript to run after the window is created
        $window['js'] .= <<<EOL
         /* Tell the browser to load/display the list */
         xajax_window_submit('app_full_list', 'subnet_id=>{$form['subnet_id']},form_id=>{$form_id},content_id=>{$content_id}', 'display_list');
         
EOL;
        
    }    
    $window['html'] .= <<<EOL
    <!-- HOST LIST -->
    <div style="border: 1px solid {$color['border']}; height : 700px; overflow : auto">
        
        <!-- Tab & Quick Filter -->
        <table id="{$form_id}_table" cellspacing="0" border="0" cellpadding="0">
            <tr>
                <td id="{$form_id}_{$tab}_tab" class="table-tab-active">
                   <b>{$window['subtitle']}</b>&nbsp;<span id="{$form_id}_{$tab}_count" />
                </td>
            </tr>
        </table>
         <div id='{$content_id}'>
            {$conf['loading_icon']}
        </div>
    </div>        
EOL;


        return(window_open($window_name, $window));

}

//////////////////////////////////////////////////////////////////////////////
// Function: ws_display_list()
// 
// Description:
//   Displays A list of hosts based on search criteria.
//   Input:  An array from xajaxGetFormValues() from a quick filter form.
//////////////////////////////////////////////////////////////////////////////
function ws_display_list($window_name, $form='') {
    global $conf, $self, $onadb;
    global $images, $color, $style;
    $html = '';
    $js = '';
    
    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);
    printmsg("ws_display_list in app_full_list.inc.php called with: " . print_r($form,1), 3);
    
    // Find the "tab" we're on
    $tab = $_SESSION['ona'][$form['form_id']]['tab'];
    
    // Build js to refresh this list
    $refresh = "xajax_window_submit('{$window_name}', xajax.getFormValues('{$form['form_id']}'), 'display_list');";
    
   
    // Search results go in here
    $results = array();
    $count = 0;
   
    
    // Start building the "where" clause for the sql query to find the hosts to display
    $where = "";
    $and = "";
    $orderby = "ip_addr";
    
  
    // NETWORK ID
    if (is_numeric($form['subnet_id'])) {
        // We do a sub-select to find interface id's that match
        $where .= $and . "subnet_id = " . $onadb->qstr($form['subnet_id']) ;
        $and = " AND ";
        
    }
    
    
    // IP ADDRESS
    if ($form['ip']) {
        // Build $ip and $ip_end from $form['ip'] and $form['ip_thru']
        $ip = ip_complete($form['ip'], '0');
        if ($form['ip_thru']) { $ip_end = ip_complete($form['ip_thru'], '255'); }
        else { $ip_end = ip_complete($form['ip'], '255'); }
        
        // Find out if $ip and $ip_end are valid
        $ip = ip_mangle($ip, 'numeric');
        $ip_end = ip_mangle($ip_end, 'numeric');
        if ($ip != -1 and $ip_end != -1) {
            // We do a sub-select to find interface id's between the specified ranges
            $where .= $and . "ip_addr >= " . $onadb->qstr($ip) . " AND ip_addr <= " . $onadb->qstr($ip_end);
            $and = " AND ";
        }
    }
   
    

    // Do the SQL Query
   
    list ($status, $rows, $results) = 
        db_get_records(
            $onadb,
            'interfaces',
            $where ,
            $orderby,
            -1,
            -1
        );
    $count = $rows;
   
    
    
    // 
    // *** BUILD HTML LIST ***
    // 
    $html .= <<<EOL
        <!-- Host Results -->
        <table id="{$form['form_id']}_full_host_list" class="list-box" cellspacing="0" border="0" cellpadding="0">
            
            <!-- Table Header -->
            <tr>
                <td class="list-header" align="center" style="{$style['borderR']};">Name</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Subnet</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Interface</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Device Model</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Location</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Notes</td>
            </tr>
EOL;
    // Loop and display each record
    foreach($results as $record) {
        // Get host record
        list($status, $rows, $host) = ona_find_host($record['host_id']); 
        
        // If a network_id was passed use it as part of the search.  Used to display the IP of the network you searched
        if (is_numeric($form['subnet_id'])) {
            
            list($status, $rows, $interface) = ona_get_interface_record(array('host_id' => $host['id'], 'subnet_id' => $form['subnet_id']), 'ip_addr');
            
            // Count how many rows and assign it back to the interfaces variable
            list($status, $interfaces, $records) = db_get_records($onadb,
                                                            'interfaces',
                                                            'host_id = '. $onadb->qstr($host['id']),
                                                            "",
                                                            0);
            
        } elseif (is_numeric($ip)) {
            list($status, $rows, $interface) = db_get_record($onadb,
                                                            'interfaces',
                                                            'host_id = '. $onadb->qstr($host['id']) . 
                                                            ' AND ip_addr >= ' . $onadb->qstr($ip) . 
                                                            ' AND ip_addr <= ' . $onadb->qstr($ip_end),
                                                            "",
                                                            0);
            
            // Count how many rows and assign it back to the interfaces variable
            list($status, $interfaces, $records) = db_get_records($onadb,
                                                            'interfaces',
                                                            'host_id = '. $onadb->qstr($host['id']),
                                                            "",
                                                            0);
            
        }  else {
            // Interface (and find out how many there are)
            list($status, $interfaces, $interface) = ona_get_interface_record(array('host_id' => $host['id']), 'ip_addr');
        }
        
        $record['ip_addr'] = ip_mangle($record['ip_addr'], 'dotted');
        $interface_style = '';
        if ($interfaces > 1) {
            $interface_style = 'font-weight: bold;';
        }
        
        // Subnet description
        list($status, $rows, $subnet) = ona_get_subnet_record(array('id' => $record['subnet_id']));
        $record['subnet'] = $subnet['name'];
        $record['ip_mask'] = ip_mangle($subnet['ip_mask'], 'dotted');
        $record['IP_SUBNET_MASK_CIDR'] = ip_mangle($subnet['ip_mask'], 'cidr');
        
        // Device Description
        list($status, $rows, $device) = ona_find_device($host['device_id']);
        list($status, $rows, $device_type) = ona_get_device_type_record(array('id' => $device['device_type_id']));
        list($status, $rows, $role) = ona_get_role_record(array('id' => $device_type['role_id']));
        list($status, $rows, $model) = ona_get_model_record(array('id' => $device_type['model_id']));
        list($status, $rows, $manufacturer) = ona_get_manufacturer_record(array('id' => $model['manufacturer_id']));
        $record['DEVICE'] = "{$manufacturer['name']}, {$model['name']} ({$role['name']})";
        $record['DEVICE'] = str_replace('Unknown', '?', $record['DEVICE']);
        
        $record['NOTES_SHORT'] = truncate($host['notes'], 40);


        // get interface cluster info
        $clusterhtml = '';
        list ($status, $intclusterrows, $intcluster) = db_get_records($onadb, 'interface_clusters', "interface_id = {$interface['id']}");
        if ($intclusterrows>0) {
            $clusterscript= "onMouseOver=\"wwTT(this, event,
                    'id', 'tt_interface_cluster_list_{$record['id']}',
                    'type', 'velcro',
                    'styleClass', 'wwTT_niceTitle',
                    'direction', 'south',
                    'javascript', 'xajax_window_submit(\'tooltips\', \'tooltip=>interface_cluster_list,id=>tt_interface_cluster_list_{$record['id']},interface_id=>{$interface['id']}\');'
                    );\"";
            $clusterhtml .= <<<EOL
                <img src="{$images}/silk/sitemap.png" {$clusterscript} />
EOL;
        }
        
        // Get location info
        list($status, $rows, $loc) = ona_get_location_record(array('id' => $device['location_id']));

        
        // Escape data for display in html
        foreach(array_keys($record) as $key) { $record[$key] = htmlentities($record[$key], ENT_QUOTES); }
        
        $primary_object_js = "xajax_window_submit('work_space', 'xajax_window_submit(\'display_host\', \'host_id=>{$host['id']}\', \'display\')');";
        $html .= <<<EOL
            <tr onMouseOver="this.className='row-highlight';" onMouseOut="this.className='row-normal';">
                
                <td class="list-row">
                    <a title="View host. ID: {$host['id']}"
                       class="nav"
                       onClick="{$primary_object_js}"
                    >{$host['name']}</a
                    >.<a title="View domain. ID: {$host['domain_id']}"
                         class="domain"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain\', \'domain_id=>{$host['domain_id']}\', \'display\')');"
                    >{$host['domain_fqdn']}</a>
                </td>
                
                <td class="list-row">
                    <a title="View subnet. ID: {$subnet['id']}"
                         class="nav"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_subnet\', \'subnet_id=>{$subnet['id']}\', \'display\')');"
                    >{$record['subnet']}</a>&nbsp;
                </td>
                
                <td class="list-row" align="left">
                    <span style="{$interface_style}"
EOL;

if ($interfaces > 1) {
        $html .= <<<EOL
                          onMouseOver="wwTT(this, event,
                                            'id', 'tt_host_interface_list_{$host['id']}',
                                            'type', 'velcro',
                                            'styleClass', 'wwTT_niceTitle',
                                            'direction', 'south',
                                            'javascript', 'xajax_window_submit(\'tooltips\', \'tooltip=>host_interface_list,id=>tt_host_interface_list_{$host['id']},host_id=>{$host['id']}\');'
                                           );"
EOL;
}                    
        $html .= <<<EOL
                    >{$record['ip_addr']}</span>&nbsp;
                    <span title="{$record['ip_mask']}">/{$record['IP_SUBNET_MASK_CIDR']}</span>
                    <span>{$clusterhtml}</span>
                </td>
                
                <td class="list-row">{$record['DEVICE']}&nbsp;</td>
                
                <td class="list-row" align="right">
                    <span onMouseOver="wwTT(this, event, 
                                            'id', 'tt_location_{$device['location_id']}', 
                                            'type', 'velcro',
                                            'styleClass', 'wwTT_niceTitle',
                                            'direction', 'south',
                                            'javascript', 'xajax_window_submit(\'tooltips\', \'tooltip=>location,id=>tt_location_{$device['location_id']},location_id=>{$device['location_id']}\');'
                                           );"
                    >{$loc['reference']}</span>&nbsp;
                </td>
                
                <td class="list-row">
                    <span title="{$host['notes']}">{$record['NOTES_SHORT']}</span>&nbsp;
                </td>
                
            
            </tr>
EOL;

    }

   
    $html .= <<<EOL
    </table>
EOL;
    
    
    $js .= <<<EOL
            /* Make sure this table is 100% wide */
            el('{$form['form_id']}_full_host_list').style.width = el('{$form['form_id']}_table').offsetWidth + 'px';
EOL;
    
   
    
    // Insert the new html into the content div specified
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $response->addAssign("{$form['form_id']}_{$tab}_count",  "innerHTML", "({$count})");
    $response->addAssign($form['content_id'], "innerHTML", $html);
    if ($js) { $response->addScript($js); }
    return($response->getXML());
}












?>
