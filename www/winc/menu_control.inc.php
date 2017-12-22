<?php

//////////////////////////////////////////////////////////////////////////////
// Function: ws_submit($input)
//
// Description:
//     Inserts dynamic content into a tool-tip popup.
//     $form is a string array that should look something like this:
//       "tooltip=>something,id=>element_id,something_id=>143324"
//////////////////////////////////////////////////////////////////////////////
function ws_menu_control_submit($window_name, $ws) {
    global $conf, $images;
    $html = $js = '';

    // If an array in a string was provided, build the array and store it in $form
    //$form = parse_options_string($form);

    printmsg("DEBUG => Displaying main menu:", 5);

    $html .= <<<EOL
    <div style="float:left;margin-top: 0px;" title="Click to close menu" onclick="ona_menu_closedown();">
        <img style="vertical-align: bottom;" src="{$images}/silk/bullet_delete.png" border="0" />
    </div>
EOL;

    // an array listing the name of each menu, used to build them later
    // they will be processed in order.
    $menulist = array('Edit','View','Plugins','Admin','ONA');

    // If we have a workspace value passed in lets add that menu option first
    if ("$ws" != 'FALSE') array_unshift($menulist, 'Workspace');

    foreach ($menulist as $item) {
        // A function must exist called $func
        $func = 'get_html_menu_button_'.$item;
        // run the function and test its output
        list ($tmp, $tmpjs) = $func(array('wsname' => $ws));
        if ($tmp) {
            // If it returned some HTML then build the menu item.
            $html .= <<<EOL
            <div id="menu_button_{$item}_name" class="menu-title-normal"  onMouseOut="this.className='menu-title-normal';">
                <span id="menu_button_{$item}" onmouseover="ona_menuTT('menu_button_{$item}','menu_{$item}_list');">{$item}</span>
            </div>
EOL;
            // First initialization of the menu.
            $js .= "ona_menuTT('menu_button_{$item}','menu_{$item}_list');";
        }
    }

    // this shows the menu bar and makes a few things look pretty
    $js .= <<<EOL
    el('trace_history').style.display = 'none';
    el('menu_bar_top').style.display = '';
    el('menu-apps-item').style.paddingBottom='5px';
EOL;




    $response = new xajaxResponse();
    $response->addAssign('menu_bar_top', "innerHTML", $html);
    $response->addScript($js);
    // used to let menus pass in javascript
    $response->addScript($tmpjs);
    return($response->getXML());
}




//////////////////////////////////////////////////////////////////////////////
// Function: ws_menu($input)
//
// Description:
//     Inserts dynamic content into a tool-tip popup.
//     $form is a string array that should look something like this:
//     "tooltip=>location,id=>element_id,location_id=>143324"
//////////////////////////////////////////////////////////////////////////////
function ws_menu($window_name, $form='') {
    global $conf, $images;
    $html = $js = '';

    // If an array in a string was provided, build the array and store it in $form
    $form = parse_options_string($form);

    printmsg("DEBUG => Displaying tooltip: {$form['tooltip']}", 4);

    $menuname = 'get_html_'.$form['menu_name'];

    list ($html, $js) = $menuname($form);

    // Okay here's what we do:
    //   1. Hide the tool-tip
    //   2. Update it's content
    //   3. Reposition it
    //   4. Unhide it
    $response = new xajaxResponse();
    if ($html) {
        $response->addScript("el('{$form['id']}').style.visibility = 'hidden';");
        $response->addAssign($form['id'], "innerHTML", $html);
        $response->addScript("wwTT_position('{$form['id']}'); el('{$form['id']}').style.visibility = 'visible';");
    }
    if ($js) { $response->addScript($js); }
    return($response->getXML());
}






//////////////////////////////////////////////////////////////////////////////
// Function: get_html_menu_button_ona()
//
// Description:
//     Builds HTML for displaying the start menu
//     Returns a two part array ($html, $js)
//////////////////////////////////////////////////////////////////////////////
function get_html_menu_button_ona() {
    global $conf, $images, $menuitem;

    $html = $js = '';


    // If we are logged in or we are not guest, display a logout button
    if (!loggedIn() or $_SESSION['ona']['auth']['user']['username'] != 'guest') {

        $html .= <<<EOL
<div class="row"
     onMouseOver="this.className='hovered';"
     onMouseOut="this.className='row';"
     onClick="var doit=confirm('Are you sure you want to logout?'); ona_menu_closedown();if (doit == true) document.location = 'logout.php';"
     title="About"
 ><img style="vertical-align: middle;" src="{$images}/silk/door_out.png" border="0"
 />&nbsp;Logout</div>
EOL;
}

    $html .= <<<EOL
<div class="row"
     onMouseOver="this.className='hovered';"
     onMouseOut="this.className='row';"
     onClick="ona_menu_closedown(); toggle_window('app_user_info');"
     title="About"
 ><img style="vertical-align: middle;" src="{$images}/silk/user_gray.png" border="0"
 />&nbsp;User info/Change password</div>

<div class="row"
     onMouseOver="this.className='hovered';"
     onMouseOut="this.className='row';"
     onClick="ona_menu_closedown(); window.location.href = 'http://opennetadmin.com/docs/';"
     title="Documentation from the main website"
 ><img style="vertical-align: middle;" src="{$images}/silk/book_open.png" border="0"
 />&nbsp;Documentation</div>

<div class="row"
     onMouseOver="this.className='hovered';"
     onMouseOut="this.className='row';"
     onClick="ona_menu_closedown(); window.location.href = 'https://github.com/opennetadmin/ona/issues';"
     title="File a bug report or feature request"
 ><img style="vertical-align: middle;" src="{$images}/silk/bug.png" border="0"
 />&nbsp;Issues & Discussion</div>

<div class="row"
     onMouseOver="this.className='hovered';"
     onMouseOut="this.className='row';"
     onClick="ona_menu_closedown(); toggle_window('app_about');"
     title="About"
 ><img style="vertical-align: middle;" src="{$images}/silk/information.png" border="0"
 />&nbsp;About</div>

EOL;

    return(array($html, $js));
}







//////////////////////////////////////////////////////////////////////////////
// Function: get_html_menu_button_admin()
//
// Description:
//     Builds HTML for displaying the start menu
//     Returns a two part array ($html, $js)
//////////////////////////////////////////////////////////////////////////////
function get_html_menu_button_admin() {
    global $conf, $images, $menuitem;

    $html = $js = '';



    if (auth('advanced',3)) {
        $html .= <<<EOL

    <div class="row"
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="ona_menu_closedown();toggle_window('app_user_list');"
         title="Manage users"
    ><img style="vertical-align: middle;" src="{$images}/silk/user.png" border="0"
     />&nbsp;Manage users</div>

    <div class="row"
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="ona_menu_closedown();toggle_window('app_group_list');"
         title="Manage groups"
     ><img style="vertical-align: middle;" src="{$images}/silk/group.png" border="0"
     />&nbsp;Manage groups</div>

    <div class="row"
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="ona_menu_closedown();toggle_window('app_location_list');"
         title="Manage locations"
    ><img style="vertical-align: middle;" src="{$images}/silk/map.png" border="0"
     />&nbsp;Manage locations</div>

    <div class="row"
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="ona_menu_closedown();toggle_window('app_sysconf_list');"
         title="Manage system config"
    ><img style="vertical-align: middle;" src="{$images}/silk/page_edit.png" border="0"
     />&nbsp;Manage system config</div>

    <div class="row"
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="ona_menu_closedown();toggle_window('app_custom_attribute_type_list');"
         title="Manage custom attribute types"
    ><img style="vertical-align: middle;" src="{$images}/silk/tag_blue_edit.png" border="0"
     />&nbsp;Manage custom attribute types</div>

    <div class="row"
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="ona_menu_closedown();toggle_window('app_config_type_list');"
         title="Manage config types"
    ><img style="vertical-align: middle;" src="{$images}/silk/cog_edit.png" border="0"
     />&nbsp;Manage config types</div>

    <div class="row"
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="ona_menu_closedown();toggle_window('app_manufacturer_list');"
         title="Manage manufacturers"
    ><img style="vertical-align: middle;" src="{$images}/silk/lorry.png" border="0"
     />&nbsp;Manage manufacturers</div>

    <div class="row"
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="ona_menu_closedown();toggle_window('app_device_role_list');"
         title="Manage device roles"
    ><img style="vertical-align: middle;" src="{$images}/silk/drive_edit.png" border="0"
     />&nbsp;Manage device roles</div>

    <div class="row"
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="ona_menu_closedown();toggle_window('app_device_model_list');"
         title="Manage device models"
    ><img style="vertical-align: middle;" src="{$images}/silk/drive_edit.png" border="0"
     />&nbsp;Manage device models</div>

    <div class="row"
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="ona_menu_closedown();toggle_window('app_device_type_list');"
         title="Manage device types"
    ><img style="vertical-align: middle;" src="{$images}/silk/drive_edit.png" border="0"
     />&nbsp;Manage device types</div>

    <div class="row"
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="ona_menu_closedown();toggle_window('app_dhcp_option_list');"
         title="Manage DHCP options"
    ><img style="vertical-align: middle;" src="{$images}/silk/table_edit.png" border="0"
     />&nbsp;Manage DHCP options</div>

    <div class="row"
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="ona_menu_closedown();toggle_window('app_subnet_type_list');"
         title="Manage subnet types"
    ><img style="vertical-align: middle;" src="{$images}/silk/transmit_blue.png" border="0"
     />&nbsp;Manage subnet types</div>

    <div class="row"
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="ona_menu_closedown();toggle_window('app_domain_list');"
         title="Manage DNS domains"
    ><img style="vertical-align: middle;" src="{$images}/silk/world_edit.png" border="0"
     />&nbsp;Manage DNS domains</div>
EOL;

    if ($conf['dns_views']) {
        $html .= <<<EOL
    <div class="row"
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="ona_menu_closedown();toggle_window('app_dns_view_list');"
         title="Manage DNS views"
    ><img style="vertical-align: middle;" src="{$images}/silk/world_edit.png" border="0"
     />&nbsp;Manage DNS views</div>
EOL;
    }

    $html .= <<<EOL
    <div class="row"
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="ona_menu_closedown();toggle_window('app_dhcp_failover_list');"
         title="Manage DHCP failover groups"
    ><img style="vertical-align: middle;" src="{$images}/silk/world_edit.png" border="0"
     />&nbsp;Manage DHCP failover groups</div>


    <div class="row"
        onMouseOver="this.className='hovered';"
        onMouseOut="this.className='row';"
        onClick="ona_menu_closedown(); toggle_window('app_plugin_list');"
        title="List Plugins"
    ><img style="vertical-align: middle;" src="{$images}/silk/plugin_edit.png" border="0"
    />&nbsp;Manage Plugins</div>

<div class="row"
     onMouseOver="this.className='hovered';"
     onMouseOut="this.className='row';"
     onClick="ona_menu_closedown(); xajax_window_submit('work_space', 'xajax_window_submit(\'display_ona_db_logs\', \'form=>fake\', \'display\')');"
     title="Display DB logs"
 ><img style="vertical-align: middle;" src="{$images}/silk/page_add.png" border="0"
 />&nbsp;Display DB logs</div>

EOL;
    }




    return(array($html, $js));
}




//////////////////////////////////////////////////////////////////////////////
// Function: get_html_menu_button_edit()
//
// Description:
//     Builds HTML for displaying the start menu
//     Returns a two part array ($html, $js)
//////////////////////////////////////////////////////////////////////////////
function get_html_menu_button_edit() {
    global $conf, $images, $menuitem;

    $html = $js = '';

    if (auth('subnet_add',3)) {
        $html .= <<<EOL
<div class="row"
     onMouseOver="this.className='hovered';"
     onMouseOut="this.className='row';"
     onClick="ona_menu_closedown(); xajax_window_submit('edit_subnet', ' ', 'editor');"
     title="Add a new subnet"
 ><img style="vertical-align: middle;" src="{$images}/silk/page_add.png" border="0"
 />&nbsp;Add Subnet</div>
EOL;
    }

    if (auth('host_modify',3) and auth('host_add',3)) {
        $html .= <<<EOL
<div class="row"
     onMouseOver="this.className='hovered';"
     onMouseOut="this.className='row';"
     onClick="ona_menu_closedown(); xajax_window_submit('edit_host', ' ', 'editor');"
     title="Add a new host"
 ><img style="vertical-align: middle;" src="{$images}/silk/page_add.png" border="0"
 />&nbsp;Add Host</div>
EOL;
    }

    if (auth('host_modify',3) and auth('host_add',3)) {
        $html .= <<<EOL
<div class="row"
     onMouseOver="this.className='hovered';"
     onMouseOut="this.className='row';"
     onClick="ona_menu_closedown(); xajax_window_submit('edit_record', 'blank=>nope', 'editor');"
     title="Add a new DNS record"
 ><img style="vertical-align: middle;" src="{$images}/silk/page_add.png" border="0"
 />&nbsp;Add DNS record</div>
EOL;
    }

    if (auth('dns_record_del',3)) {
        $html .= <<<EOL
<div class="row"
     onMouseOver="this.className='hovered';"
     onMouseOut="this.className='row';"
     onClick="ona_menu_closedown(); xajax_window_submit('edit_domain', 'fake=>fake', 'editor');"
     title="Add a new DNS domain"
 ><img style="vertical-align: middle;" src="{$images}/silk/page_add.png" border="0"
 />&nbsp;Add DNS domain</div>
EOL;
    }

    if (auth('vlan_add',3)) {
        $html .= <<<EOL
<div class="row"
     onMouseOver="this.className='hovered';"
     onMouseOut="this.className='row';"
     onClick="ona_menu_closedown(); xajax_window_submit('edit_vlan_campus', ' ', 'editor');"
     title="Add a new VLAN campus"
 ><img style="vertical-align: middle;" src="{$images}/silk/page_add.png" border="0"
 />&nbsp;Add VLAN campus</div>
EOL;
    }

    if (auth('vlan_add',3)) {
        $html .= <<<EOL
<div class="row"
     onMouseOver="this.className='hovered';"
     onMouseOut="this.className='row';"
     onClick="ona_menu_closedown(); xajax_window_submit('edit_vlan', ' ', 'editor');"
     title="Add a new VLAN"
 ><img style="vertical-align: middle;" src="{$images}/silk/page_add.png" border="0"
 />&nbsp;Add VLAN</div>
EOL;
    }

    if (auth('subnet_add',3)) {
        $html .= <<<EOL
<div class="row"
     onMouseOver="this.className='hovered';"
     onMouseOut="this.className='row';"
     onClick="ona_menu_closedown(); xajax_window_submit('edit_block', ' ', 'editor');"
     title="Add a new block"
 ><img style="vertical-align: middle;" src="{$images}/silk/page_add.png" border="0"
 />&nbsp;Add Block</div>
EOL;
    }

    if (auth('location_add',3)) {
        $html .= <<<EOL
<div class="row"
     onMouseOver="this.className='hovered';"
     onMouseOut="this.className='row';"
     onClick="ona_menu_closedown(); xajax_window_submit('edit_location', ' ', 'editor');"
     title="Add a new location"
 ><img style="vertical-align: middle;" src="{$images}/silk/page_add.png" border="0"
 />&nbsp;Add Location</div>

EOL;
    }


    return(array($html, $js));
}









//////////////////////////////////////////////////////////////////////////////
// Function: get_plugin_menu_html()
//
// Description:
//     Builds HTML for displaying the start menu
//     Returns a two part array ($html, $js)
//////////////////////////////////////////////////////////////////////////////
function get_html_menu_button_plugins() {
    global $conf, $images, $menuitem, $base, $baseURL;

    $html = $js = '';

    // Get all the plugin menuitems
    $pluginlist = plugin_list('menu_item');

    // Load all the plugin menuitems and build a menu entry
    foreach ($pluginlist as $p) {
        plugin_load('menu_item',$p['name']);

        // based on the menu cmd type, build the right command
        switch ($menuitem['type']) {
            case 'work_space':
                $menu_type_cmd = "xajax_window_submit('work_space', 'xajax_window_submit(\'{$p['name']}\', \'form=>fake\', \'display\')')";
                break;
            case 'window':
                $menu_type_cmd = "toggle_window('{$p['name']}')";
                break;
        }

        // Use a default image if we cant find the one specified.
       if (!file_exists($base.$menuitem['image'])){
           $menuitem['image'] = "/images/silk/plugin.png";
       }

        // Check the authorization and print the menuitem if the are authorized
        if (auth($menuitem['authname'],3) || !$menuitem['authname']) {
        $html .= <<<EOL

<div class="row"
     onMouseOver="this.className='hovered';"
     onMouseOut="this.className='row';"
     onClick="ona_menu_closedown(); {$menu_type_cmd};"
     title="{$menuitem['title']}"
 ><img style="vertical-align: middle;" src="{$baseURL}{$menuitem['image']}" border="0"
 />&nbsp;{$menuitem['title']}</div>

EOL;
        }
    }

    if (auth('advanced',3)) {
    $html .= <<<EOL
    <div class="row"
        onMouseOver="this.className='hovered';"
        onMouseOut="this.className='row';"
        onClick="ona_menu_closedown(); toggle_window('app_plugin_list');"
        title="Manage Plugins"
    ><img style="vertical-align: middle;" src="{$images}/silk/plugin_edit.png" border="0"
    />&nbsp;Manage Plugins</div>
EOL;
}


    return(array($html, $js));
}











//////////////////////////////////////////////////////////////////////////////
// Function: get_start_menu_html()
//
// Description:
//     Builds HTML for displaying the start menu
//     Returns a two part array ($html, $js)
//////////////////////////////////////////////////////////////////////////////
function get_html_menu_button_view() {
    global $conf, $images, $menuitem;

    $html = $js = '';


    if (auth('host_del',3)) {
        $html .= <<<EOL
<div class="row"
     onMouseOver="this.className='hovered';"
     onMouseOut="this.className='row';"
     onClick="ona_menu_closedown(); toggle_window('app_report_list');"
     title="List Reports"
 ><img style="vertical-align: middle;" src="{$images}/silk/application.png" border="0"
 />&nbsp;List Reports</div>

EOL;
    }

    if (auth('dns_record_add',3)) {
        $html .= <<<EOL
<div class="row"
     onMouseOver="this.className='hovered';"
     onMouseOut="this.className='row';"
     onClick="ona_menu_closedown(); toggle_window('app_domain_list');"
     title="List DNS Domains"
 ><img style="vertical-align: middle;" src="{$images}/silk/application.png" border="0"
 />&nbsp;List DNS Domains</div>

EOL;
    }

    if (auth('dns_record_add',3)) {
        $html .= <<<EOL
<div class="row"
     onMouseOver="this.className='hovered';"
     onMouseOut="this.className='row';"
     onClick="ona_menu_closedown(); toggle_window('app_domain_servers_list');"
     title="List DNS Domain Servers"
 ><img style="vertical-align: middle;" src="{$images}/silk/application.png" border="0"
 />&nbsp;List DNS Domain Servers</div>

EOL;
    }

    if (auth('advanced',3)) {
        $html .= <<<EOL
<div class="row"
     onMouseOver="this.className='hovered';"
     onMouseOut="this.className='row';"
     onClick="ona_menu_closedown(); toggle_window('app_dhcp_servers_list');"
     title="List DHCP Servers"
 ><img style="vertical-align: middle;" src="{$images}/silk/application.png" border="0"
 />&nbsp;List DHCP Servers</div>

EOL;
    }


    return(array($html, $js));
}






//////////////////////////////////////////////////////////////////////////////
// Function: get_plugin_menu_button_workspace()
//
// Description:
//     Builds HTML for displaying the workspace menu
//     Will copy contents of the wsmenu built on the workspace itself
//     Returns a two part array ($html, $js)
//////////////////////////////////////////////////////////////////////////////
function get_html_menu_button_workspace($form='') {

    $html = $js = '';

    // Create a div section to place any workspace menu items 
    $html = "<div id='wsmenudiv'><div class='row'>No Available Actions</div></div>";

    // Copy our hidden div content created within the workspace itself to this menu
    $js = "el('wsmenudiv').innerHTML = el('wsmenu').innerHTML;";


    return(array($html, $js));
}


?>
