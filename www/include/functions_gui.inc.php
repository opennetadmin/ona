<?php

//
// GUI Specific Functions
// Not included for DCM modules
//

// FIXME: This should be done where all the other user's prefs are loaded
// The other half of this is in search_results.inc.php
// if (is_numeric($_SESSION['search_results_per_page'])) $conf['search_results_per_page'] = $_SESSION['search_results_per_page'];



/////////////////////////////////////////////
// Returns a list of available local plugins of given type
//
// Patterned from dokuwiki by Andreas Gohr <andi@splitbrain.org>
//
// Not currently utilizing the "type" stuff as I could be
//
// returns an array of plugin names that have a specific type available.
//
/////////////////////////////////////////////
function plugin_list($type=''){
  global $base;
  $plugins = array();
  $i=0;
  // check local plugins, then builtin plugins
  $plugin_paths = array($base."/local/plugins/",$base."/plugins/");
  foreach ($plugin_paths as $plugin_path) {
    if ($dh = @opendir($plugin_path)) {
        while (false !== ($plugin = readdir($dh))) {
            if ($plugin == '.' || $plugin == '..' || $plugin == 'tmp' || $plugin == '.svn'|| substr($plugin, -7) == '.tar.gz') continue;
            if (is_file($plugin_path.$plugin)) continue;

            if ($type=='' || @file_exists($plugin_path."$plugin/$type.inc.php") and @!file_exists($plugin_path."$plugin/plugin_disabled")){
                $plugins[$i]['name'] = $plugin;
                //if we did not specify a type.. just get the path for the plugin
                if($type=='')
                    $plugins[$i]['path'] = $plugin_path."$plugin";
                else
                    $plugins[$i]['path'] = $plugin_path."$plugin/$type.inc.php";
                $i++;
            }
        }
        closedir($dh);
    }
  }
  return $plugins;
}

///////////////////////////////////////////////
// Loads the given local plugin and creates an object of it
//
// Patterned from dokuwiki by Andreas Gohr <andi@splitbrain.org>
//
// @param  $type string     type of plugin to load
// @param  $name string     name of the plugin to load
// @return objectreference  the plugin object or null on failure
//
// Currently not doing any classes.
//
//////////////////////////////////////////////
function &plugin_load($type,$name){
  //we keep all loaded plugins available in global scope for reuse
  global $ONA_PLUGINS, $base;


  //plugin already loaded?
  if($ONA_PLUGINS[$type][$name] != null){
    return $ONA_PLUGINS[$type][$name];
  }
  // check local plugins, then builtin plugins
  $plugin_paths = array($base."/local/plugins/",$base."/plugins/");
  foreach ($plugin_paths as $plugin_path) {
    //try to load the wanted plugin file if it is not disabled
    if (!file_exists($plugin_path."$name/plugin_disabled")){
        if (file_exists($plugin_path."$name/$type.inc.php")){
            include_once($plugin_path."$name/$type.inc.php");
            break;
        }
    }
  }

  //construct class and instanciate
  $class = $type.'_plugin_'.$name;
  if (!class_exists($class)) return null;

  $ONA_PLUGINS[$type][$name] = new $class;
  return $ONA_PLUGINS[$type][$name];
}



/*
// Used in display_ pages to load a workspace plugin module and wrap it
// in a common looking div.
//
// Returns the html to render the ws plugin
//
*/
function workspace_plugin_loader($modulename, $record=array(), $extravars=array()) {
    global $conf, $self, $base, $images, $color, $style, $onadb;
    $modhtml = '';
    $modjs = '';
    $modbodyhtml = '';
    $ws_plugin_dir = "{$base}/workspace_plugins";

    $max_img = "{$images}/silk/bullet_arrow_down.png";
    $min_img = "{$images}/silk/bullet_arrow_up.png";

    //Default the module title
    $title_left_html = $modulename;
    $title_right_html = '';

    // Load the modules contents from the modules directory.
    // Check for an installed module first. if not then use a builtin one
    // First check the module name as a direct path to the workspace plugin.
    if (is_file($modulename)) { require($modulename); }
    else if (is_dir("{$ws_plugin_dir}/{$modulename}")) {
        $mod_conf="{$ws_plugin_dir}/{$modulename}/config.inc.php";
        if (file_exists($mod_conf)) { require($mod_conf); }
        $mod_main="{$ws_plugin_dir}/{$modulename}/main.inc.php";
        if (file_exists($mod_main)) { require($mod_main); }
    }
    else if (is_dir("{$ws_plugin_dir}/builtin/{$modulename}")) {
        $mod_conf="{$ws_plugin_dir}/builtin/{$modulename}/config.inc.php";
        if (file_exists($mod_conf)) { require($mod_conf); }
        $mod_main="{$ws_plugin_dir}/builtin/{$modulename}/main.inc.php";
        if (file_exists($mod_main)) { require($mod_main); }
    }
    else {
        $mod_main="{$base}/include/unknown_module_msg.inc.php";
        if (file_exists($mod_main)) { require($mod_main); }
    }


/*  one way to do the div containter..
                    <span id="mod_title_{$modulename}" style="float: left;">{$titlehtml}</span>
                    <span id="mod_buttons_{$modulename}" style="float: right;">
                    {$confightml}
                    <img  src="{$min_img}"
                          id="{$modulename}_dropdown"
                          title="Min/Max"
                          onclick="if (el('{$modulename}_content').style.display=='none') { el('{$modulename}_content').style.display=''; el('{$modulename}_dropdown').src='{$min_img}'; }
                                   else { el('{$modulename}_content').style.display='none'; el('{$modulename}_dropdown').src='{$max_img}';}"

                    /></span>&nbsp;
*/






    // Create a standard div container for the module
    $modhtml .= <<<EOL
            <!-- {$modulename} start -->
            <div id="{$modulename}_container" class="ws_plugin_content">
                <table  cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">
                <tr>
                    <td class="ws_plugin_title_right" title="{$title_description}">{$title_left_html}</td>
                    <td class="ws_plugin_title_left">
                    {$title_right_html}
                    <img  src="{$min_img}"
                          id="{$modulename}_dropdown"
                          title="Min/Max"
                          onclick="if (el('{$modulename}_content').style.display=='none') { el('{$modulename}_content').style.display=''; el('{$modulename}_dropdown').src='{$min_img}'; }
                                   else { el('{$modulename}_content').style.display='none'; el('{$modulename}_dropdown').src='{$max_img}';}"

                    /></td>
                </tr>
                <tr><td colspan="99" id="{$modulename}_content">
EOL;

    // FIXME: MP put some sort of loading icon here.

    // Dislay the modules contents from the modules directory.
    $modhtml .= $modbodyhtml;

    // Close out the modules div container
    $modhtml .= <<<EOL
                </td></tr>
                </table>
            </div>
            <!-- {$modulename} end -->
EOL;

    // If the module returns no body, then lets blank out what we have
    // and assume that the module determined it should not display itself
    if (!$modbodyhtml) {
        $modhtml = '';
        $modjs = '';
    }

    return(array($modhtml,$modjs));
}

















//////////////////////////////////////////////////////////////////////////////
// Calculates the percentage of a subnet that is in "use".
// Returns a three part list:
//    list($percentage_used, $number_used, $number_total)
//////////////////////////////////////////////////////////////////////////////
function get_subnet_usage($subnet_id) {
    global $conf, $self, $onadb;

    list($status, $rows, $subnet) = db_get_record($onadb, 'subnets', array('id' => $subnet_id));
    if ($status or !$rows) { return(0); }
    if (strlen($subnet['ip_addr']) > 11) {
        $sub = gmp_sub("340282366920938463463374607431768211455", $subnet['ip_mask']);
        $subnet['size'] = gmp_strval($sub) - 1;
    } else {
    	$subnet['size'] = (0xffffffff - ip_mangle($subnet['ip_mask'], 'numeric')) - 1;
    	if ($subnet['ip_mask'] == 4294967295) $subnet['size'] = 1;
    	if ($subnet['ip_mask'] == 4294967294) $subnet['size'] = 2;
    }

    // Calculate the percentage used (total size - allocated hosts - dhcp pool size)
    list($status, $hosts, $tmp) = db_get_records($onadb, 'interfaces', array('subnet_id' => $subnet['id']), "", 0);
    list($status, $rows, $pools) = db_get_records($onadb, 'dhcp_pools', array('subnet_id' => $subnet['id']));
    $pool_size = 0;
    foreach ($pools as $pool) {
        $pool_size += ($pool['ip_addr_end'] - $pool['ip_addr_start'] + 1);
    }
    $total_used = $hosts + $pool_size;
    $percentage = 100;
    if ($subnet['size']) $percentage = sprintf('%d', ($total_used / $subnet['size']) * 100);
    return(array($percentage, $total_used, $subnet['size']));
}





//////////////////////////////////////////////////////////////////////////////
// Returns the html for a "percentage of subnet used" bar graph
//////////////////////////////////////////////////////////////////////////////
function get_subnet_usage_html($subnet_id, $width=30, $height=8) {
    global $conf, $self, $mysql, $onadb;
    list($usage, $used, $total) = get_subnet_usage($subnet_id);
    $css='';
    if (strpos($_SERVER['HTTP_USER_AGENT'],'MSIE') != false)
        $css = "font-size: " . ($height - 2) . "px;";
    return(<<<EOL
    <div style="white-space: nowrap; width: 100%; text-align: left; padding-top: 2px; padding-bottom: 2px; vertical-align: middle; font-size: 8px;">
        <div title="{$usage}% used" style="{$css} float: left; width: {$width}px; height: {$height}px; text-align: left; vertical-align: middle; background-color: #ABFFBC; border: 1px solid #000;">
            <div style="{$css} width: {$usage}%; height: {$height}px; vertical-align: middle; background-color: #FF3939;"></div>
        </div>
        <span style="font-size: 8px;">&nbsp;{$used} / {$total}</span>
    </div>
EOL
);
}






function get_host_suggestions($q, $max_results=10) {
    global $self, $conf, $onadb;
    $results = array();

    // If we are not using views, limit to the default
    if (!$conf['dns_views']) $limit_to_view = 'dns_view_id = 0 AND ';

    // wildcard the query before searching
    $q = $q . '%';

    $table = 'dns';
    $field = 'name';
    $where  = "{$limit_to_view} {$field} LIKE " . $onadb->qstr($q);
    $order  = "{$field} ASC";

    // Search the db for results
    list ($status, $rows, $records) = db_get_records(
                                        $onadb,
                                        $table,
                                        $where,
                                        $order,
                                        $max_results
                                      );

    // If the query didn't work return the error message
    if ($status) { $results[] = "Internal Error: {$self['error']}"; }

    foreach ($records as $record) {
        // NS records dont need to show their domain.
        if ($record['type'] == 'NS') {
            $results[] = $record[$field];
        } else {
            list($status, $rows, $domain) = db_get_record($onadb, 'domains', array('id' => $record['domain_id']));
            if ($conf['dns_views']) {
                list($status, $rows, $view) = db_get_record($onadb, 'dns_views', array('id' => $record['dns_view_id']));
                $viewname = $view['name'].'/';
            }
            $results[] = $viewname.$record[$field].".".$domain['name'];
        }
    }

    // Return the records
    return($results);
}

function get_a_record_suggestions($q, $max_results=10) {
    global $self, $conf, $onadb;
    $results = array();

    // If we are not using views, limit to the default
    if (!$conf['dns_views']) $limit_to_view = 'dns_view_id = 0 AND ';

    // wildcard the query before searching
    $q = $q . '%';

    $table = 'dns';
    $field = 'name';
    $where  = "{$limit_to_view} type LIKE 'A' and {$field} LIKE " . $onadb->qstr($q);
    $order  = "{$field} ASC";

    // Search the db for results
    list ($status, $rows, $records) = db_get_records(
                                        $onadb,
                                        $table,
                                        $where,
                                        $order,
                                        $max_results
                                      );

    // If the query didn't work return the error message
    if ($status) { $results[] = "Internal Error: {$self['error']}"; }

    foreach ($records as $record) {
        list($status, $rows, $domain) = db_get_record($onadb, 'domains', array('id' => $record['domain_id']));
        $results[] = $record[$field].".".$domain['name'];
    }

    // Return the records
    return($results);
}


function get_host_notes_suggestions($q, $max_results=10) {
    return(get_text_suggestions($q . '%', 'hosts', 'notes', $max_results));
}

function get_domain_suggestions($q, $max_results=10) {
    global $self, $conf, $onadb;
    $results = array();

    // wildcard the query before searching
    $q = $q . '%';

    $table = 'domains';
    $field = 'name';
    $where  = "{$field} LIKE " . $onadb->qstr($q);
    $order  = "{$field} ASC";

    // Search the db for results
    list ($status, $rows, $records) = db_get_records(
                                        $onadb,
                                        $table,
                                        $where,
                                        $order,
                                        $max_results
                                      );

    // If the query didn't work return the error message
    if ($status) { $results[] = "Internal Error: {$self['error']}"; }

    foreach ($records as $record) {
        if ($record['parent_id']) {
            list($status, $rows, $domain) = db_get_record($onadb, 'domains', array('id' => $record['parent_id']));
            $results[] = $record[$field].".".ona_build_domain_name($domain['id']);
        } else {
            $results[] = $record[$field];
            // Also check if this record is a parent of others
            list ($status, $rows, $precords) = db_get_records($onadb, $table, "{$record['id']} = parent_id", $order, $max_results);
            foreach ($precords as $precord) {
                $results[] = $precord[$field].".".ona_build_domain_name($record['id']);
            }
        }
    }

    // Return the records
    return($results);
}

function get_vlan_campus_suggestions($q, $max_results=10) {
    $q = strtoupper($q);
    return(get_text_suggestions($q . '%', 'vlan_campuses', 'name', $max_results));
}

function get_block_suggestions($q, $max_results=10) {
    $q = strtoupper($q);
    return(get_text_suggestions($q . '%', 'blocks', 'name', $max_results));
}

function get_subnet_suggestions($q, $max_results=10) {
    $q = strtoupper($q);
    return(get_text_suggestions($q . '%', 'subnets', 'name', $max_results));
}

function get_location_suggestions($q, $max_results=10) {
    return(get_text_suggestions($q . '%', 'locations', 'reference', $max_results));
}

function get_mac_suggestions($q, $max_results=10) {
    $formatted = $results = array();

    $q = strtoupper($q);
    //if (preg_match('/[^\%\:\.\-0-9A-F]/', $q)) return(array()); // It's not a mac address ;)
    // MP: I added the %3A line because some searches were coming through with the : replaced.
    $q = preg_replace('/\%3A/', '', $q);  // Discard characters that aren't stored in the db
    $q = preg_replace('/[\:\.\-]/', '', $q);  // Discard characters that aren't stored in the db

    $results = get_text_suggestions($q . '%', 'interfaces', 'mac_addr', $max_results);
    foreach($results as $result) $formatted[] = mac_mangle($result, 2);
    return($formatted);
}



function get_ip_suggestions($q, $max_results=10) {
    global $onadb;
    $formatted = $results = array();

    // Complete the (potentially incomplete) ip address
    // MP: Need to figure out a good way to take a partial ipv6 and complete it out for start/end ips
    $ip = ip_complete($q, '0');
    $ip_end = ip_complete($q, '255');

    // Find out if $ip and $ip_end are valid
    $ip = ip_mangle($ip, 'numeric');
    $ip_end = ip_mangle($ip_end, 'numeric');
    if ($ip == -1 or $ip_end == -1) { return(array()); } // It's not valid ip addresses

    // Now use SQL to look for subnet ip records that match
    $table = 'subnets';
    $field = 'ip_addr';
    $where  = "{$field} >= " . $onadb->qstr($ip) . " AND {$field} <= " . $onadb->qstr($ip_end);
    $order  = "{$field} ASC";

    // Search the db for results and put results into $results
    list ($status, $rows, $records) = db_get_records($onadb, $table, $where, $order, $max_results);
    foreach ($records as $record) { $results[] = $record[$field]; }

    // If we need more suggestions, look in the host_subnets table
    $max_results -= count($results);
    if ($max_results) {
        $table = 'interfaces';
        list ($status, $rows, $records) = db_get_records($onadb, $table, $where, $order, $max_results);
        foreach ($records as $record) { $results[] = $record[$field]; }
    }

    // Format the ip's in dotted format
    sort($results);
    foreach($results as $result) { $formatted[] = ip_mangle($result, 'dotted'); }

    unset($results, $result, $records, $record);
    return($formatted);
}

function get_model_suggestions($q, $max_results=10) {
//     global $self, $conf, $onadb;
//     $results = array();
// 
//     // wildcard the query before searching
//     $q = $q . '%';
// 
//     $table = 'models';
//     $field = 'name';
//     $where  = "{$field} LIKE " . $onadb->qstr($q);
//     $order  = "{$field} ASC";
// 
//     // Search the db for results
//     list ($status, $rows, $records) = db_get_records(
//                                         $onadb,
//                                         $table,
//                                         $where,
//                                         $order,
//                                         $max_results
//                                       );
// 
//     // If the query didn't work return the error message
//     if ($status) { $results[] = "Internal Error: {$self['error']}"; }
// 
//     foreach ($records as $record) {
//         list($status, $rows, $manu) = db_get_record($onadb, 'manufacturers', array('id' => $record['manufacturer_id']));
//         $results[] = $manu['name'].", ".$record[$field];
//     }
//     sort($results);
//     // Return the records
//     return($results);


   return(get_text_suggestions($q . '%', 'models', 'name', $max_results));
}

function get_custom_attribute_type_suggestions($q, $max_results=10) {
    return(get_text_suggestions($q . '%', 'custom_attribute_types', 'name', $max_results));
}

function get_role_suggestions($q, $max_results=10) {
    return(get_text_suggestions($q . '%', 'roles', 'name', $max_results));
}

function get_manufacturer_suggestions($q, $max_results=10) {
    return(get_text_suggestions($q . '%', 'manufacturers', 'name', $max_results));
}

//////////////////////////////////////////////////////////////////////////////
// xajax server for suggest_qsearch()
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
function suggest_qsearch($q, $el_input, $el_suggest) {
    global $conf, $images;
    $results = array();

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$q or !$el_input or !$el_suggest) { return($response->getXML()); }
    $js = "";

    // Command intrepreter
    if (strpos($q, '/') === 0) {
        $js .= "suggestions = Array('Enter a command...');";
        $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
        $response->addScript($js);
        return($response->getXML());
    }

    // Search the DB for ip addressees
    if (count($results) < $conf['suggest_max_results']) {
        $array = get_ip_suggestions($q, $conf['suggest_max_results'] - count($results));
        foreach($array as $suggestion) { $results[] = $suggestion; }
        $results = array_unique($results);
    }

    // Search the DB for hosts
    if (count($results) < $conf['suggest_max_results']) {
        $array = get_host_suggestions($q, $conf['suggest_max_results'] - count($results));
        foreach($array as $suggestion) { $results[] = $suggestion; }
        $results = array_unique($results);
    }

    // Search the DB for subnets
    if (count($results) < $conf['suggest_max_results']) {
        $array = get_subnet_suggestions($q, $conf['suggest_max_results'] - count($results));
        foreach($array as $suggestion) { $results[] = $suggestion; }
        $results = array_unique($results);
    }

    // Search the DB for hosts (*)
    if (count($results) < $conf['suggest_max_results']) {
        $array = get_host_suggestions('%' . $q, $conf['suggest_max_results'] - count($results));
        foreach($array as $suggestion) { $results[] = $suggestion; }
        $results = array_unique($results);
    }

    // Search the DB for subnets (*)
    if (count($results) < $conf['suggest_max_results']) {
        $array = get_subnet_suggestions('%' . $q, $conf['suggest_max_results'] - count($results));
        foreach($array as $suggestion) { $results[] = $suggestion; }
        $results = array_unique($results);
    }

// FIXM: MP this does not come out correctly in the resulting record matches.. disabled for now.
//     // Search the DB for domains
//     if (count($results) < $conf['suggest_max_results']) {
//         $array = get_domain_suggestions($q, $conf['suggest_max_results'] - count($results));
//         foreach($array as $suggestion) { $results[] = $suggestion; }
//         $results = array_unique($results);
//     }

    // Search the DB for mac addressees
    if (count($results) < $conf['suggest_max_results']) {
        $array = get_mac_suggestions($q, $conf['suggest_max_results'] - count($results));
        foreach($array as $suggestion) { $results[] = $suggestion; }
        $results = array_unique($results);
    }

    // Search the DB for mac addressees (*)
    if (count($results) < $conf['suggest_max_results']) {
        $array = get_mac_suggestions('%' . $q, $conf['suggest_max_results'] - count($results));
        foreach($array as $suggestion) { $results[] = $suggestion; }
        $results = array_unique($results);
    }


    // Build the javascript to return
    $js .= "suggestions = Array(";
    $comma = "";
    foreach ($results as $suggestion) {
        $suggestion = str_replace("'", "\\'", $suggestion);
        $js .= $comma . "'{$suggestion}'";
        if (!$comma) { $comma = ", "; }
    }
    $js .= ");";

    // Tell the browser to execute the javascript in $js by sending an XML response
    $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
    $response->addScript($js);
    return($response->getXML());
}


//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
function suggest_a_record($q, $el_input, $el_suggest) {
    global $conf;

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$q or !$el_input or !$el_suggest) { return($response->getXML()); }
    $js = "";

    // Search the DB
    $results = get_a_record_suggestions($q);
    $results = array_merge($results, get_a_record_suggestions('%'.$q, $conf['suggest_max_results'] - count($results)));
    $results = array_unique($results);

    // Build the javascript to return
    $js .= "suggestions = Array(";
    $comma = "";
    foreach ($results as $suggestion) {
        $suggestion = str_replace("'", "\\'", $suggestion);
        $js .= $comma . "'{$suggestion}'";
        if (!$comma) { $comma = ", "; }
    }
    $js .= ");";

    // Tell the browser to execute the javascript in $js by sending an XML response
    $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
    $response->addScript($js);
    return($response->getXML());
}
function suggest_set_a_record_edit_record($q, $el_input, $el_suggest) {
    return(suggest_a_record($q, $el_input, $el_suggest));
}


//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
function suggest_hostname($q, $el_input, $el_suggest) {
    global $conf;

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$q or !$el_input or !$el_suggest) { return($response->getXML()); }
    $js = "";

    // Search the DB
    $results = get_host_suggestions($q);
    $results = array_merge($results, get_host_suggestions('%'.$q, $conf['suggest_max_results'] - count($results)));
    $results = array_unique($results);

    // Build the javascript to return
    $js .= "suggestions = Array(";
    $comma = "";
    foreach ($results as $suggestion) {
        $suggestion = str_replace("'", "\\'", $suggestion);
        $js .= $comma . "'{$suggestion}'";
        if (!$comma) { $comma = ", "; }
    }
    $js .= ");";

    // Tell the browser to execute the javascript in $js by sending an XML response
    $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
    $response->addScript($js);
    return($response->getXML());
}
function suggest_move_hostname($q, $el_input, $el_suggest) {
    return(suggest_hostname($q, $el_input, $el_suggest));
}
function suggest_share_hostname($q, $el_input, $el_suggest) {
    return(suggest_hostname($q, $el_input, $el_suggest));
}
function suggest_int_hostname($q, $el_input, $el_suggest) {
    return(suggest_hostname($q, $el_input, $el_suggest));
}
function suggest_device($q, $el_input, $el_suggest) {
    return(suggest_hostname($q, $el_input, $el_suggest));
}

//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
//FIXME: (PK) this function is redundant.  Can we replace it with a call directly
//       to suggest_hostname()?
function suggest_failover_pri_hostname($q, $el_input, $el_suggest) {
    return(suggest_hostname($q, $el_input, $el_suggest));
}

//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
//FIXME: (PK) this function is redundant.  Can we replace it with a call directly
//       to suggest_hostname()?
function suggest_failover_sec_hostname($q, $el_input, $el_suggest) {
    return(suggest_hostname($q, $el_input, $el_suggest));
}

//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
function suggest_server($q, $el_input, $el_suggest) {
    global $conf;

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$q or !$el_input or !$el_suggest) { return($response->getXML()); }
    $js = "";

    // Search the DB
    $results = get_host_suggestions($q);
    $results = array_merge($results, get_host_suggestions('%'.$q, $conf['suggest_max_results'] - count($results)));
    $results = array_unique($results);

    //TODO: potentialy add a search for get_domain_suggestions here

    // Build the javascript to return
    $js .= "suggestions = Array(";
    $comma = "";
    foreach ($results as $suggestion) {
        $suggestion = str_replace("'", "\\'", $suggestion);
        $js .= $comma . "'{$suggestion}'";
        if (!$comma) { $comma = ", "; }
    }
    $js .= ");";

    // Tell the browser to execute the javascript in $js by sending an XML response
    $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
    $response->addScript($js);
    return($response->getXML());
}

// FIXME: (PK) do we still use these functions anywhere?  Check & remove, if not.
// MP: I'm keeping them for now.. I've changed the function to just use host_suggestions.. server_suggestions could be removed??
function suggest_pool_server_qf($q, $el_input, $el_suggest) {
    return(suggest_server($q, $el_input, $el_suggest));
}
function suggest_domain_server_name($q, $el_input, $el_suggest) {
    return(suggest_server($q, $el_input, $el_suggest));
}
function suggest_dhcp_server_name($q, $el_input, $el_suggest) {
    return(suggest_server($q, $el_input, $el_suggest));
}



//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
function suggest_domain($q, $el_input, $el_suggest) {
    global $conf;

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$q or !$el_input or !$el_suggest) { return($response->getXML()); }
    $js = "";

    // Search the DB
    $results = get_domain_suggestions($q);
    $results = array_merge($results, get_domain_suggestions('%'.$q, $conf['suggest_max_results'] - count($results)));
    $results = array_unique($results);

    // Build the javascript to return
    $js .= "suggestions = Array(";
    $comma = "";
    foreach ($results as $suggestion) {
        $suggestion = str_replace("'", "\\'", $suggestion);
        $js .= $comma . "'{$suggestion}'";
        if (!$comma) { $comma = ", "; }
    }
    $js .= ");";

    // Tell the browser to execute the javascript in $js by sending an XML response
    $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
    $response->addScript($js);
    return($response->getXML());
}
function suggest_domain_alias_edit($q, $el_input, $el_suggest) {
    return(suggest_domain($q, $el_input, $el_suggest));
}
function suggest_domain_edit($q, $el_input, $el_suggest) {
    return(suggest_domain($q, $el_input, $el_suggest));
}
function suggest_set_domain_edit_host($q, $el_input, $el_suggest) {
    return(suggest_domain($q, $el_input, $el_suggest));
}
function suggest_domain_server_edit($q, $el_input, $el_suggest) {
    return(suggest_domain($q, $el_input, $el_suggest));
}
function suggest_set_domain_edit_record($q, $el_input, $el_suggest) {
    return(suggest_domain($q, $el_input, $el_suggest));
}
function suggest_dns_domain($q, $el_input, $el_suggest) {
    return(suggest_domain($q, $el_input, $el_suggest));
}



//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
function suggest_notes($q, $el_input, $el_suggest) {
    global $conf;

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$q or !$el_input or !$el_suggest) { return($response->getXML()); }
    $js = "";

    // Search the DB
    $results = get_host_notes_suggestions($q, $conf['suggest_max_results']);
    $results = array_merge($results, get_host_notes_suggestions('%'.$q, $conf['suggest_max_results'] - count($results)));
    $results = array_unique($results);

    // Build the javascript to return
    $js .= "suggestions = Array(";
    $comma = "";
    foreach ($results as $suggestion) {
        $suggestion = str_replace("'", "\\'", $suggestion);
        $js .= $comma . "'{$suggestion}'";
        if (!$comma) { $comma = ", "; }
    }
    $js .= ");";

    // Tell the browser to execute the javascript in $js by sending an XML response
    $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
    $response->addScript($js);
    return($response->getXML());
}






//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
function suggest_mac($q, $el_input, $el_suggest) {
    global $conf;

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$q or !$el_input or !$el_suggest) { return($response->getXML()); }
    $js = "";

    // Search the DB
    $results = get_mac_suggestions($q);
    $results = array_merge($results, get_mac_suggestions('%'.$q, $conf['suggest_max_results'] - count($results)));
    $results = array_unique($results);

    // Build the javascript to return
    $js .= "suggestions = Array(";
    $comma = "";
    foreach ($results as $suggestion) {
        $suggestion = str_replace("'", "\\'", $suggestion);
        $js .= $comma . "'{$suggestion}'";
        if (!$comma) { $comma = ", "; }
    }
    $js .= ");";

    // Tell the browser to execute the javascript in $js by sending an XML response
    $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
    $response->addScript($js);
    return($response->getXML());
}








//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function that is part of the
// xajax_suggest module.
//////////////////////////////////////////////////////////////////////////////
function suggest_ip($q, $el_input, $el_suggest) {
    global $conf;

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$q or !$el_input or !$el_suggest) { return($response->getXML()); }
    $js = "";

    // Search the DB
    $results = get_ip_suggestions($q);

    // Build the javascript to return
    $js .= "suggestions = Array(";
    $comma = "";
    foreach ($results as $suggestion) {
        $suggestion = str_replace("'", "\\'", $suggestion);
        $js .= $comma . "'{$suggestion}'";
        if (!$comma) { $comma = ", "; }
    }
    $js .= ");";

    // Tell the browser to execute the javascript in $js by sending an XML response
    $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
    $response->addScript($js);
    return($response->getXML());
}
// The following are just wrappers around suggest_ip();
function suggest_ip_thru($q, $el_input, $el_suggest) {
    return(suggest_ip($q, $el_input, $el_suggest));
}
function suggest_ip_subnet($q, $el_input, $el_suggest) {
    return(suggest_ip($q, $el_input, $el_suggest));
}
function suggest_ip_subnet_thru($q, $el_input, $el_suggest) {
    return(suggest_ip($q, $el_input, $el_suggest));
}
function suggest_ip_subnet_qf($q, $el_input, $el_suggest) {
    return(suggest_ip($q, $el_input, $el_suggest));
}
function suggest_ip_subnet_thru_qf($q, $el_input, $el_suggest) {
    return(suggest_ip($q, $el_input, $el_suggest));
}
function suggest_block_ip_subnet($q, $el_input, $el_suggest) {
    return(suggest_ip($q, $el_input, $el_suggest));
}
function suggest_block_ip_subnet_thru($q, $el_input, $el_suggest) {
    return(suggest_ip($q, $el_input, $el_suggest));
}
function suggest_set_ip_edit_interface($q, $el_input, $el_suggest) {
    return(suggest_ip($q, $el_input, $el_suggest));
}
function suggest_natip_qf($q, $el_input, $el_suggest) {
    return(suggest_ip($q, $el_input, $el_suggest));
}
function suggest_dns_ip($q, $el_input, $el_suggest) {
    return(suggest_ip($q, $el_input, $el_suggest));
}


//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
function suggest_vlan_campus($q, $el_input, $el_suggest) {
    global $conf;

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$q or !$el_input or !$el_suggest) { return($response->getXML()); }
    $js = "";

    // Search the DB
    $results = get_vlan_campus_suggestions($q);
    $results = array_merge($results, get_vlan_campus_suggestions('%'.$q, $conf['suggest_max_results'] - count($results)));
    $results = array_unique($results);

    // Build the javascript to return
    $js .= "suggestions = Array(";
    $comma = "";
    foreach ($results as $suggestion) {
        $suggestion = str_replace("'", "\\'", $suggestion);
        $js .= $comma . "'{$suggestion}'";
        if (!$comma) { $comma = ", "; }
    }
    $js .= ");";

    // Tell the browser to execute the javascript in $js by sending an XML response
    $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
    $response->addScript($js);
    return($response->getXML());
}
function suggest_vlan_edit($q, $el_input, $el_suggest) {
    return(suggest_vlan_campus($q, $el_input, $el_suggest));
}
function suggest_vlan_campus_qf($q, $el_input, $el_suggest) {
    return(suggest_vlan_campus($q, $el_input, $el_suggest));
}


//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
function suggest_custom_attribute_type($q, $el_input, $el_suggest) {
    global $conf;

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$q or !$el_input or !$el_suggest) { return($response->getXML()); }
    $js = "";

    // Search the DB
    $results = get_custom_attribute_type_suggestions($q);
    $results = array_merge($results, get_custom_attribute_type_suggestions('%'.$q, $conf['suggest_max_results'] - count($results)));
    $results = array_unique($results);

    // Build the javascript to return
    $js .= "suggestions = Array(";
    $comma = "";
    foreach ($results as $suggestion) {
        $suggestion = str_replace("'", "\\'", $suggestion);
        $js .= $comma . "'{$suggestion}'";
        if (!$comma) { $comma = ", "; }
    }
    $js .= ");";

    // Tell the browser to execute the javascript in $js by sending an XML response
    $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
    $response->addScript($js);
    return($response->getXML());
}

function suggest_custom_attribute_type_net($q, $el_input, $el_suggest) {
    return(suggest_custom_attribute_type($q, $el_input, $el_suggest));
}

//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
function suggest_manufacturer($q, $el_input, $el_suggest) {
    global $conf;

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$q or !$el_input or !$el_suggest) { return($response->getXML()); }
    $js = "";

    // Search the DB
    $results = get_manufacturer_suggestions($q);
    $results = array_merge($results, get_manufacturer_suggestions('%'.$q, $conf['suggest_max_results'] - count($results)));
    $results = array_unique($results);

    // Build the javascript to return
    $js .= "suggestions = Array(";
    $comma = "";
    foreach ($results as $suggestion) {
        $suggestion = str_replace("'", "\\'", $suggestion);
        $js .= $comma . "'{$suggestion}'";
        if (!$comma) { $comma = ", "; }
    }
    $js .= ");";

    // Tell the browser to execute the javascript in $js by sending an XML response
    $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
    $response->addScript($js);
    return($response->getXML());
}


//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
function suggest_model($q, $el_input, $el_suggest) {
    global $conf;

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$q or !$el_input or !$el_suggest) { return($response->getXML()); }
    $js = "";

    // Search the DB
    $results = get_model_suggestions($q);
    $results = array_merge($results, get_model_suggestions('%'.$q, $conf['suggest_max_results'] - count($results)));
    $results = array_unique($results);

    // Build the javascript to return
    $js .= "suggestions = Array(";
    $comma = "";
    foreach ($results as $suggestion) {
        $suggestion = str_replace("'", "\\'", $suggestion);
        $js .= $comma . "'{$suggestion}'";
        if (!$comma) { $comma = ", "; }
    }
    $js .= ");";

    // Tell the browser to execute the javascript in $js by sending an XML response
    $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
    $response->addScript($js);
    return($response->getXML());
}

//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
function suggest_role($q, $el_input, $el_suggest) {
    global $conf;

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$q or !$el_input or !$el_suggest) { return($response->getXML()); }
    $js = "";

    // Search the DB
    $results = get_role_suggestions($q);
    $results = array_merge($results, get_role_suggestions('%'.$q, $conf['suggest_max_results'] - count($results)));
    $results = array_unique($results);

    // Build the javascript to return
    $js .= "suggestions = Array(";
    $comma = "";
    foreach ($results as $suggestion) {
        $suggestion = str_replace("'", "\\'", $suggestion);
        $js .= $comma . "'{$suggestion}'";
        if (!$comma) { $comma = ", "; }
    }
    $js .= ");";

    // Tell the browser to execute the javascript in $js by sending an XML response
    $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
    $response->addScript($js);
    return($response->getXML());
}


//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
function suggest_block($q, $el_input, $el_suggest) {
    global $conf;

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$q or !$el_input or !$el_suggest) { return($response->getXML()); }
    $js = "";

    // Search the DB
    $results = get_block_suggestions($q);
    $results = array_merge($results, get_block_suggestions('%'.$q, $conf['suggest_max_results'] - count($results)));
    $results = array_unique($results);

    // Build the javascript to return
    $js .= "suggestions = Array(";
    $comma = "";
    foreach ($results as $suggestion) {
        $suggestion = str_replace("'", "\\'", $suggestion);
        $js .= $comma . "'{$suggestion}'";
        if (!$comma) { $comma = ", "; }
    }
    $js .= ");";

    // Tell the browser to execute the javascript in $js by sending an XML response
    $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
    $response->addScript($js);
    return($response->getXML());
}







//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
function suggest_subnet($q, $el_input, $el_suggest) {
    global $conf;

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$q or !$el_input or !$el_suggest) { return($response->getXML()); }
    $js = "";

    // Search the DB
    $results = get_subnet_suggestions($q);
    $results = array_merge($results, get_subnet_suggestions('%'.$q, $conf['suggest_max_results'] - count($results)));
    $results = array_unique($results);

    // Build the javascript to return
    $js .= "suggestions = Array(";
    $comma = "";
    foreach ($results as $suggestion) {
        $suggestion = str_replace("'", "\\'", $suggestion);
        $js .= $comma . "'{$suggestion}'";
        if (!$comma) { $comma = ", "; }
    }
    $js .= ");";

    // Tell the browser to execute the javascript in $js by sending an XML response
    $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
    $response->addScript($js);
    return($response->getXML());
}
function suggest_set_subnet_edit_interface($q, $el_input, $el_suggest) {
    return(suggest_subnet($q, $el_input, $el_suggest));
}
function suggest_set_subnet_edit_host($q, $el_input, $el_suggest) {
    return(suggest_subnet($q, $el_input, $el_suggest));
}
function suggest_subnet_qf($q, $el_input, $el_suggest) {
    return(suggest_subnet($q, $el_input, $el_suggest));
}
function suggest_dhcp_subnet_name($q, $el_input, $el_suggest) {
    return(suggest_subnet($q, $el_input, $el_suggest));
}




//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
function suggest_masks_edit_subnet($q, $el_input, $el_suggest) {
    global $conf;

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$q or !$el_input or !$el_suggest) { return($response->getXML()); }
    $js = "";

    $q = trim($q);

    // Build the array of dotted masks or cidr masks if there is a /
    $hasslash = strpos($q,'/');
    if ($hasslash === FALSE) {
        $results = array(
                        '255.255.255.255',
                        '255.255.255.254',
                        '255.255.255.252',
                        '255.255.255.248',
                        '255.255.255.240',
                        '255.255.255.224',
                        '255.255.255.192',
                        '255.255.255.128',
                        '255.255.255.0',
                        '255.255.254.0',
                        '255.255.252.0',
                        '255.255.248.0',
                        '255.255.240.0',
                        '255.255.224.0',
                        '255.255.192.0',
                        '255.255.128.0',
                        '255.255.0.0',
                        '255.254.0.0',
                        '255.252.0.0',
                        '255.248.0.0',
                        '255.240.0.0',
                        '255.224.0.0',
                        '255.192.0.0',
                        '255.128.0.0',
                        '255.0.0.0',
                        '254.0.0.0',
                        '252.0.0.0',
                        '248.0.0.0',
                        '240.0.0.0',
                        '224.0.0.0',
                        '192.0.0.0',
                        '128.0.0.0',
                        );
    }
    else {
        $results = array(
                        '/66',
                        '/65',
                        '/64',
                        '/48',
                        '/32',
                        '/31',
                        '/30',
                        '/29',
                        '/28',
                        '/27',
                        '/26',
                        '/25',
                        '/24',
                        '/23',
                        '/22',
                        '/21',
                        '/20',
                        '/19',
                        '/18',
                        '/17',
                        '/16',
                        '/15',
                        '/14',
                        '/13',
                        '/12',
                        '/11',
                        '/10',
                        '/9',
                        '/8',
                        '/7',
                        '/6',
                        '/5',
                        '/4',
                        '/3',
                        '/2',
                        '/1',
                        );
    }

    // grep for the query to slim down the array
    $results = preg_grep("%{$q}%",$results);

    // Build the javascript to return
    $js .= "suggestions = Array(";
    $comma = "";
    foreach ($results as $suggestion) {
        $suggestion = str_replace("'", "\\'", $suggestion);
        $js .= $comma . "'{$suggestion}'";
        if (!$comma) { $comma = ", "; }
    }
    $js .= ");";

    // Tell the browser to execute the javascript in $js by sending an XML response
    $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
    $response->addScript($js);
    return($response->getXML());
}




//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
function suggest_location($q, $el_input, $el_suggest) {
    global $conf;

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$q or !$el_input or !$el_suggest) { return($response->getXML()); }
    $js = "";

    // Search the DB
    $results = get_location_suggestions($q);
    $results = array_merge($results, get_location_suggestions('%'.$q, $conf['suggest_max_results'] - count($results)));
    $results = array_unique($results);

    // Build the javascript to return
    $js .= "suggestions = Array(";
    $comma = "";
    foreach ($results as $suggestion) {
        $suggestion = str_replace("'", "\\'", $suggestion);
        $js .= $comma . "'{$suggestion}'";
        if (!$comma) { $comma = ", "; }
    }
    $js .= ");";

    // Tell the browser to execute the javascript in $js by sending an XML response
    $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
    $response->addScript($js);
    return($response->getXML());
}
// Used in all QF (tool-tip based) search boxes
function suggest_location_qf($q, $el_input, $el_suggest) {
    return(suggest_location($q, $el_input, $el_suggest));
}
// Advanced search subnet tab
function suggest_location_number_subnet($q, $el_input, $el_suggest) {
    return(suggest_location($q, $el_input, $el_suggest));
}
// Used in all QF (tool-tip based) search boxes
function suggest_set_location_edit_host($q, $el_input, $el_suggest) {
    return(suggest_location($q, $el_input, $el_suggest));
}




?>
