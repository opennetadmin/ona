<?php
global $conf, $self, $onadb ;
global $font_family, $color, $style, $images;


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
    <div style="border: 1px solid {$color['border']}; height : 700px; overflow-y : auto;overflow-x : hidden">
        
        <!-- Tab & Quick Filter -->
        <table id="{$form_id}_table" cellspacing="0" border="0" cellpadding="0">
            <tr>
                <td id="{$form_id}_{$tab}_tab" class="table-tab-active">
                   <b>{$window['subtitle']}</b>&nbsp;<span id="{$form_id}_{$tab}_count" />
                </td>
                <td>
                    &nbsp;<a onclick="toggle_table_rows('{$form_id}_full_host_list','byip_available');" class="button" ><img src="{$images}/silk/arrow_rotate_clockwise.png" border="0"> Toggle Available IPs</a>
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
    
    // Find the "tab" we're on
    $tab = $_SESSION['ona'][$form['form_id']]['tab'];
    
    // Build js to refresh this list
    $refresh = "xajax_window_submit('{$window_name}', xajax.getFormValues('{$form['form_id']}'), 'display_list');";
    
   
    // Search results go in here
    $results = array();
    $count = 0;
   
    
    // NETWORK ID
    if (is_numeric($form['subnet_id'])) {
        
    }
    
    // Do the SQL Query
    list ($status, $count, $results) = 
        db_get_records(
            $onadb,
            'interfaces',
            "subnet_id = " . $onadb->qstr($form['subnet_id']) ,
            'ip_addr',
            -1,
            -1
        );

    // make an array of ips from our results
    $iplist=array();
    foreach($results as $record) {
        $iplist["{$record['ip_addr']}"]='used';
    }
   
    list($status, $rows, $subnet) = ona_find_subnet($form['subnet_id']); 

    // Create a few variables that will be handy later
    $num_ips = 0xffffffff - $subnet['ip_mask'];
    $last_ip = ($subnet['ip_addr'] + $num_ips) - 1;
    $currip = $subnet['ip_addr'] + 1;

    // Get a list of blocks that touches this subnet
    list($status, $blockrows, $blocks) = db_get_records($onadb, 'blocks', "{$subnet['ip_addr']} BETWEEN ip_addr_start AND ip_addr_end OR {$last_ip} BETWEEN ip_addr_start AND ip_addr_end OR ip_addr_start BETWEEN {$subnet['ip_addr']} and {$last_ip}");

    // Get a list of dhcp pools on the selected subnet
    list($status, $rows, $pools) =
        db_get_records($onadb, 'dhcp_pools', array('subnet_id' => $subnet['id']));

    // Add DHCP pool addresses into the list of used ips
    foreach ($pools as $pool)
        for ($ip = $pool['ip_addr_start']; $ip <= $pool['ip_addr_end']; $ip++)
            $iplist["{$ip}"] = 'pool-'.$pool['id'];
    
    // 
    // *** BUILD HTML LIST ***
    // 
    $html .= <<<EOL
        <!-- Host Results -->
        <table id="{$form['form_id']}_full_host_list" class="list-box" cellspacing="0" border="0" cellpadding="0">
            
            <!-- Table Header -->
            <tr>
                <td class="list-header" align="center" style="{$style['borderR']};" title="IP Block Association">B</td>
                <td class="list-header" align="center" style="{$style['borderR']};">IP Address</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Last Response</td>
                <td class="list-header" align="center" style="{$style['borderR']};">[Name] Desc</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Host Name</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Device Type</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Host Notes</td>
            </tr>
EOL;
    // Loop and display each ip on the subnet
    while ($currip <= $last_ip) {

        $loc=array();
        $host=array();
        $interface=array();
        $interfaces=0;
        $record=array();
        $interface_style = '';
        $clusterhtml = '';
        $rowstyle='background-color: #E9FFE1';
        $rowid='byip_available';
        $currip_txt = ip_mangle($currip, 'dotted');
        $interface['desc'] = '<span style="color: #aaaaaa;">AVAILABLE</span>';
        $nameval=<<<EOL
            <a title="Add host"
               class="act"
               onClick="xajax_window_submit('edit_host', 'ip_addr=>{$currip_txt}', 'editor');"
            ></a>&nbsp;

            <a title="Add host"
               class="act"
               onClick="xajax_window_submit('edit_host', 'ip_addr=>{$currip_txt}', 'editor');"
            >Add a new host</a>&nbsp;

             <a title="Add interface"
               class="act"
               onClick="xajax_window_submit('edit_interface', 'ip_addr=>{$currip_txt}', 'editor');"
            ></a>&nbsp;

            <a title="Add interface"
               class="act"
               onClick="xajax_window_submit('edit_interface', 'ip_addr=>{$currip_txt}', 'editor');"
            >Add interface to an existing host</a>&nbsp;
                 
EOL;

        // If the current ip is one allocated on this subnet lets do some stuff
        if (array_key_exists($currip, $iplist)) { 
            $rowid='byip_allocated';
            $rowstyle='';

            // check if it is a pool range
            list($pooltype,$poolid) = explode('-',$iplist[$currip]);
            if ($pooltype == 'pool') {
                $interface['desc'] = '<span style="color: #aaaaaa;">DHCP Pool</span>';
                $rowstyle='background-color: #FFFBD6';
                $nameval=<<<EOL
                    <a title="Edit Pool"
                       class="act"
                       onClick="xajax_window_submit('edit_dhcp_pool', 'subnet=>{$subnet['id']},id=>{$poolid}', 'editor');"
                    ><img src="{$images}/silk/page_add.png" border="0"></a>&nbsp;
        
                    <a title="Edit Pool"
                       class="act"
                       onClick="xajax_window_submit('edit_dhcp_pool', 'subnet=>{$subnet['id']},id=>{$poolid}', 'editor');"
                    >Edit DHCP Pool</a>&nbsp;
EOL;

            } else {
                // Get host record
                list($status, $rows, $host) = ona_find_host($currip); 
               
                // Get the interface info 
                list($status, $rows, $interface) = ona_find_interface($currip);
            
                // Count how many interface rows this host hasand assign it back to the interfaces variable
                list($status, $interfaces, $records) = db_get_records($onadb, 'interfaces', 'host_id = '. $onadb->qstr($host['id']), "", 0);

                // get interface cluster info
                list ($status, $intclusterrows, $intcluster) = db_get_records($onadb, 'interface_clusters', "interface_id = {$interface['id']}");
                if ($intclusterrows>0) {
                    $clusterscript= "onMouseOver=\"wwTT(this, event,
                        'id', 'tt_interface_cluster_list_{$interface['id']}',
                        'type', 'velcro',
                        'styleClass', 'wwTT_niceTitle',
                        'direction', 'south',
                        'javascript', 'xajax_window_submit(\'tooltips\', \'tooltip=>interface_cluster_list,id=>tt_interface_cluster_list_{$interface['id']},interface_id=>{$interface['id']}\');'
                        );\"";
                    $clusterhtml .= <<<EOL
                    <img src="{$images}/silk/sitemap.png" {$clusterscript} />
EOL;
                }

                // set the name value for an allocated host
                $nameval = <<<EOL
                    <a title="View host. ID: {$host['id']}"
                       class="nav"
                       onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_host\', \'host_id=>{$host['id']}\', \'display\')');"
                    >{$host['name']}</a
                    >.<a title="View domain. ID: {$host['domain_id']}"
                         class="domain"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain\', \'domain_id=>{$host['domain_id']}\', \'display\')');"
                    >{$host['domain_fqdn']}</a>
EOL;

                // Make it bold if we have more than one interface on this host
                if ($interfaces > 1) {
                    $interface_style = 'font-weight: bold;';
                }

                // Device Description
                list($status, $rows, $device) = ona_find_device($host['device_id']);
                list($status, $rows, $device_type) = ona_get_device_type_record(array('id' => $device['device_type_id']));
                list($status, $rows, $role) = ona_get_role_record(array('id' => $device_type['role_id']));
                list($status, $rows, $model) = ona_get_model_record(array('id' => $device_type['model_id']));
                list($status, $rows, $manufacturer) = ona_get_manufacturer_record(array('id' => $model['manufacturer_id']));
                $record['device'] = "{$manufacturer['name']}, {$model['name']} ({$role['name']})";
                $record['device'] = str_replace('Unknown', '?', $record['device']);

                $record['notes_short'] = truncate($host['notes'], 40);
                $interface['description_short'] = truncate($interface['description'], 40);

                if ($interface['name'] ) $interface['name'] = "[{$interface['name']}]";
                $interface['desc'] = "{$interface['name']} {$interface['description_short']}";

                // Format the date and colorize if its older than 2 months
                if ($interface['last_response']) {
                    $interface['last_response'] = date($conf['date_format'],strtotime($interface['last_response']));
                    if (strtotime($interface['last_response']) < strtotime('-2 month')) {
                        $interface['last_response_fmt'] = 'style=color:red;';
                    }
                }

                // Get location info
                list($status, $rows, $loc) = ona_get_location_record(array('id' => $device['location_id']));

            }// end real host ifblock

        } // end while loop

        // Escape data for display in html
        foreach(array_keys($record) as $key) { $record[$key] = htmlentities($record[$key], ENT_QUOTES, $conf['php_charset']); }

        $html .= <<<EOL
            <tr {$rowid}=true style="{$rowstyle}" onMouseOver="this.className='row-highlight'" onMouseOut="this.className='row-normal'">
                
EOL;

        // Print color info for any matching blocks
        $c=0;
        $blockcolors=array('#CD5C5C','#588C7E','#8C4646','#FFD700','#1E90F0','#8A2BE2','#32CD32','#D96459');
        if ($blockrows) {
          $html .= "<td class='list-row' nowrap style='padding:0'>";
          foreach ($blocks as $block) {
            if ($currip >= $block['ip_addr_start'] && $currip <= $block['ip_addr_end']) {
              $html .= "<span title='{$block['name']}' style='background-color:{$blockcolors[$c]};padding-bottom:4px;float:left;'>&nbsp;&nbsp</span> ";
            }
            $c++;
          }
        } else {
          // print an empty table cell
          $html .= "<td class='list-row'>";
        }

        $html .= <<<EOL
                </td>
                <td class="list-row" align="left">
EOL;
        // if it is used, show an edit interface link
        if ($rowid == 'byip_allocated') {
            $html .= <<<EOL
                    <a class="nav" style="{$interface_style}" title="Edit interface ID: {$interface['id']}"
                          onClick="xajax_window_submit('edit_interface', 'interface_id=>{$interface['id']}', 'editor');"
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
            $html .= '>';

        }


        //print out the IP address
        $html .= $currip_txt;

        // close the A tag if used above
        if ($rowid == 'byip_allocated') { $html .= '</a>';}

        // Keep on goin with the rest of the line
        $html .= <<<EOL

                    &nbsp;<span>{$clusterhtml}</span>
                </td>

                <td class="list-row" {$interface['last_response_fmt']}>{$interface['last_response']}&nbsp;</td>

                <td class="list-row">
                    <span title="{$interface['description']}">{$interface['desc']}</span>&nbsp;
                </td>

                <td class="list-row" style="border-left: 1px solid; border-left-color: #aaaaaa;">
                   {$nameval}
                </td>

                <td class="list-row">{$record['device']}&nbsp;</td>

                <td class="list-row">
                    <span title="{$host['notes']}">{$record['notes_short']}</span>&nbsp;
                </td>

            </tr>
EOL;

        // increment the currip
        $currip++;

    }


    $html .= <<<EOL
    </table>
EOL;
    
    
    $js .= <<<EOL
            /* Make sure this table is 100% wide */
            el('{$form['form_id']}_full_host_list').style.width = el('{$form['form_id']}_table').offsetWidth + 'px';

function togglebyip(name) {
    tr=document.getElementsByTagName('tr')
    for (i=0;i<tr.length;i++){
      if (tr[i].getAttribute(name)){
        if (tr[i].style.display=='none'){tr[i].style.display = '';}
        else {tr[i].style.display = 'none';}
      }
    }
}
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
