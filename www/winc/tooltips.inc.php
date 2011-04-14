<?php

//////////////////////////////////////////////////////////////////////////////
// Function: ws_submit($input)
//
// Description:
//     Inserts dynamic content into a tool-tip popup.
//     $form is a string array that should look something like this:
//     "tooltip=>location,id=>element_id,location_id=>143324"
//////////////////////////////////////////////////////////////////////////////
function ws_tooltips_submit($window_name, $form='') {
    global $conf, $self, $onadb, $tip_style;
    global $font_family, $color, $style, $images;
    $html = $js = '';

    // If an array in a string was provided, build the array and store it in $form
    $form = parse_options_string($form);

    printmsg("DEBUG => Displaying tooltip: {$form['tooltip']}", 4);

    switch ($form['tooltip']) {
        case 'sys_alert':
           list ($html, $js) = get_sys_alert_html($form);
           break;

        case 'loginform':
           list ($html, $js) = get_loginform_html($form);
           break;

        case 'start_menu':
           list ($html, $js) = get_start_menu_html();
           break;

        case 'local_menu':
           list ($html, $js) = get_local_menu_html();
           break;

        case 'location':
            $record['location_id'] = $form['location_id'];
            $extravars['tipstyle'] = 'style="color: #FFFFFF;background-color: #4c4c4c;border: none;"';
            $wspl = workspace_plugin_loader('location_detail',$record,$extravars);
            $html .= $wspl[0]; $js .= $wspl[1];
           break;

        case 'subnet':
           list ($html, $js) = get_subnet_html($form['subnet_ip']);
           break;

        case 'qf_subnet':
           list ($html, $js) = quick_subnet_search($form);
           break;

        case 'qf_location':
           list ($html, $js) = quick_location_search($form);
           break;

        case 'qf_vlan':
           list ($html, $js) = quick_vlan_search($form);
           break;

        case 'qf_free_ip':
           list ($html, $js) = quick_free_ip_search($form);
           break;

        case 'qf_pool_server':
           list ($html, $js) = quick_pool_server_search($form);
           break;

        case 'quick_interface_menu':
           list ($html, $js) = quick_interface_menu($form);
           break;

        case 'quick_interface_move':
           list ($html, $js) = quick_interface_move($form);
           break;

        case 'quick_interface_nat':
           list ($html, $js) = quick_interface_nat($form);
           break;

        case 'quick_interface_share':
           list ($html, $js) = quick_interface_share($form);
           break;

        case 'host_interface_list':
           list ($html, $js) = get_host_interface_list_html($form);
           break;

        case 'cainfo':
           list ($html, $js) = get_custom_attribute_info_html($form);
           break;

        case 'interface_cluster_list':
           list ($html, $js) = get_interface_cluster_list_html($form);
           break;

        case 'switchport_template_select':
           list ($html, $js) = get_switchport_template_select($form);
           break;

    }


    // Okay here's what we do:
    //   1. Hide the tool-tip
    //   2. Update it's content
    //   3. Reposition it
    //   4. Unhide it
    $response = new xajaxResponse();
    $response->addScript("el('{$form['id']}').style.visibility = 'hidden';");
    $response->addAssign($form['id'], "innerHTML", $html);
    $response->addScript("wwTT_position('{$form['id']}'); el('{$form['id']}').style.visibility = 'visible';");
    if ($js) { $response->addScript($js); }
    return($response->getXML());
}








//////////////////////////////////////////////////////////////////////////////
// Function: get_message_lines_html($where)
//
// Description:
//     Builds HTML for messages
//     Returns a two part array ($html, $js)
//////////////////////////////////////////////////////////////////////////////
function get_message_lines_html($where) {
    global $conf, $self, $onadb, $tip_style;
    global $font_family, $color, $style, $images, $msgtype;

    $html = $js = '';
    $expire_count = 0;

    list($status, $rows, $messages) = db_get_records($onadb, 'messages', $where, 'priority,mtime', 15, 0);

    // If we don't find any rows, go ahead and return
    if (!$rows)
        return(array($html, $js));

    $html .= <<<EOL
        <div style="overflow: auto;max-height: 100px;">
        <table style="cursor: pointer;" width="100%" cellspacing="0" border="0" cellpadding="0">
        <tbody style="max-height: 100px;overflow: auto;overflow-x: hidden;">
EOL;

    foreach ($messages as $record) {
        // If the message has expired, dont print it.
        if (strtotime($record['expiration']) < time()) {
            $expire_count++;
            continue;
        }


        // Escape data for display in html
        foreach(array_keys((array)$record) as $key) {$record[$key] = htmlentities($record[$key], ENT_QUOTES, $conf['php_charset']);}

        // determine the priority and setup an image for it
        switch ($record['priority']) {
            case 0:
                $priorityimg = "<img src=\"{$images}/silk/bullet_blue.png\" border=\"0\" />";
                break;
            case 1:
                $priorityimg = "<img src=\"{$images}/silk/bullet_red.png\" border=\"0\" />";
                break;
            case 2:
                $priorityimg = "<img src=\"{$images}/silk/bullet_yellow.png\" border=\"0\" />";
                break;
            case 3:
                $priorityimg = "<img src=\"{$images}/silk/bullet_green.png\" border=\"0\" />";
                break;
            default:
                $priorityimg = "";
                break;
        }

        // re format the date to something more appropriate
        $cleandate = date("m/d-h:i a",strtotime($record['mtime']));
        $expire = strtotime($record['expiration']) . "/" . time() ."=". $test;

        $html .= <<<EOL
        <tr style="height: 10px;"
            onMouseOver="this.className='row-highlight';"
            onMouseOut="this.className='row-normal';"
        >
            <td nowrap="true" title="{$record['mtime']} - Priority level: {$record['priority']} - Expires: {$record['expiration']}" valign="top" style="font-size: 10px; pad
ding: 0px 3px;">{$cleandate} {$priorityimg}</td>
            <td nowrap="true" valign="top" align="right" style="font-size: 10px; padding: 0px 0px;">{$record['username']} =></td>
            <td width="200" style="font-size: 10px; padding: 0px 2px;padding-right: 20px;">{$record['message_text']}</td>
        </tr>
EOL;
    }

    if ($expire_count > 0 ) {
        $html .= <<<EOL
        <tr class="row-highlight" >
            <td colspan=3 align="center" nowrap="true" valign="top" style="font-size: 10px; padding: 0px 3px;">There are {$expire_count} expired records not displayed</td>
        </tr>
EOL;
    }

    $html .= <<<EOL
        </tbody></table></div>
EOL;

    return(array($html, $js));

}









//////////////////////////////////////////////////////////////////////////////
// Function: get_sys_alert_html($form)
//
// Description:
//     Builds HTML for changing tacacs enable passwd
//     Returns a two part array ($html, $js)
//////////////////////////////////////////////////////////////////////////////
function get_sys_alert_html($form) {
    global $conf, $self, $onadb, $tip_style;
    global $font_family, $color, $style, $images, $msgtype;

    $html = $js = '';


    $style['content_box'] = <<<EOL
        padding: 2px 4px;
        vertical-align: top;
EOL;

    // WARNING: this one's different than most of them!
    $style['label_box'] = <<<EOL
        font-weight: bold;
        cursor: move;
        color: #FFFFFF;
EOL;

    // Display system messages
    $html .= <<<EOL

    <!-- SYS MESSAGES -->
    <form id="sys_alert_form" onSubmit="return(false);">
    <input type="hidden" name="id" value="{$form['id']}">
    <input type="hidden" name="input_id" value="{$form['input_id']}">
    <input type="hidden" name="text_id" value="{$form['text_id']}">
    <table style="{$style['content_box']}" cellspacing="0" border="0" cellpadding="0">

    <tr><td colspan="2" align="center" class="qf-search-line" style="{$style['label_box']}; padding-top: 0px;" onMouseDown="dragStart(event, '{$form['id']}', 'savePosition'
, 0);">
        System Messages
    </td></tr>

    <tr>
        <td colspan="2" align="left" class="qf-search-line">
            <div
              id="sys_alert_items"
              style="
                background-color: #FFFFFF;
                overflow: auto;"
            >
EOL;
        // Get a list of messages that have a table name of SYS_*
        list($lineshtml, $linesjs) = get_message_lines_html("`table_name_ref` LIKE 'SYS_%'");
        $html .= $lineshtml;

        $html .= <<<EOL
            </div>
        </td>
    </tr>

    <tr>
        <td align="right" class="qf-search-line">
            &nbsp;
        </td>
        <td align="right" class="qf-search-line">
            <input class="button" type="button" name="close" value="Close" onClick="removeElement('{$form['id']}');">
        </td>
    </tr>

    </table>
    </form>
EOL;


    return(array($html, $js));
}



///////////////////////////////////////////////////////////////////////
//  Function: switch_context (string $context_name)
//
//   $context_name   = The name of the context to switch to
//
//   Switches the current context cookie and reloads the interface
//   This is meant to be called by the GUI only and not by DCM etc
//
///////////////////////////////////////////////////////////////////////
function ws_switch_context($window_name, $form='') {
    global $conf, $self, $ona_contexts, $baseURL;

    $html = $js = '';
    $form = parse_options_string($form);

    // If the context passed in is in our array, switch
    if (isset($ona_contexts[$form['context_select']])) {
        printmsg("INFO => Switching to context: {$form['context_select']}",0);
        setcookie("ona_context_name", $form['context_select']);
        $js = "window.location='{$baseURL}';";
    }
    // Otherwise, complain
    else {
        printmsg("DEBUG => There was an error switching to context: {$form['context_select']}",1);
        $js = "alert('The context \'{$form['context_select']}\' is not valid.');";
    }

    $response = new xajaxResponse();
    $response->addScript($js);
    return($response->getXML());
}




//////////////////////////////////////////////////////////////////////////////
// Function: get_loginform_html($form)
//
// Description:
//     Builds HTML for logging in as a new user
//     Returns a two part array ($html, $js)
//////////////////////////////////////////////////////////////////////////////
function get_loginform_html($form) {
    global $conf, $self, $onadb, $tip_style;
    global $font_family, $color, $style, $images;

    $html = $js = '';


    $style['content_box'] = <<<EOL
        padding: 2px 4px;

EOL;

    // WARNING: this one's different than most of them!
    $style['label_box'] = <<<EOL
        font-weight: bold;
        cursor: move;
        color: #FFFFFF;
EOL;

    // Display system messages
    $html .= <<<EOL

    <!-- LOGIN PROMPT -->
    <form id="loginform_form" onSubmit="return false;">
    <input id="onapassword" type="hidden" name="onapassword">
    <table style="{$style['content_box']}" cellspacing="0" border="0">

    <tr >
        <td nowrap="true" align="right">
            <b>Username</b>&nbsp;
        </td>
        <td>
            <input id="onausername" class="edit" autocomplete="off" name="onausername" type="edit" size="12" onkeypress="if (event.keyCode == 13) { el('getpass').focus(); }">
        </td>
    </tr>
    <tr>
        <td nowrap="true" align="right">
            <b>Password</b>&nbsp;
        </td>
        <td>
            <input id="getpass" class="edit" autocomplete="off" name="getpass" type="password" size="12" onkeypress="if (event.keyCode == 13) { el('loginbutton').click(); }">
        </td>
    </tr>
    <tr><td></td>
        <td nowrap="true">
            <input  class="button"
                    type="button"
                    name="cancel"
                    value="Cancel"
                    onClick="removeElement('{$form['id']}');"
            >
            <input  class="button"
                    id="loginbutton"
                    type="button"
                    name="login"
                    value="Login"
                    onClick="el('onapassword').value = el('getpass').value;
                             xajax_window_submit('tooltips', xajax.getFormValues('loginform_form'), 'logingo');"
            >
        </td>
    </tr>
    <tr>
        <td colspan="2" align="center">
            <span id="loginmsg" style="color: red;font-size: x-small;"></span>
        </td>
    </tr>

    </table>
    </form>
EOL;

    $js = "el('onausername').focus();";


    return(array($html, $js));
}



//////////////////////////////////////////////////////////////////////////////
// Function: ws_logingo($window_name, $form)
//
// Description:
//      Runs the actual login switch after hitting the login button in the tooltip
//////////////////////////////////////////////////////////////////////////////
function ws_logingo($window_name, $form='') {
    global $conf, $self, $onadb, $baseURL;
    global $font_family, $color, $style, $images;

    $html = $js = '';
    $form = parse_options_string($form);
    $type='Desktop';

    if ($form['standalone']) $type='Standalone';

    printmsg("INFO => [{$type}] Attempting login as " . $form['onausername'], 4);

    list($status, $js) = get_authentication($form['onausername'],$form['onapassword']);

    if ($status==0) {
        get_perms($form['onausername']);
        if ($form['standalone'] == 'standalone') $js .= "window.location='{$http}{$baseURL}/';";
        $js .= "el('loggedin_user').innerHTML = '{$_SESSION['ona']['auth']['user']['username']}';";
        printmsg("INFO => [{$type}] {$_SESSION['ona']['auth']['user']['username']} has logged in via authtype: {$conf['authtype']}",0);
    }

    $response = new xajaxResponse();
    $response->addScript($js);
    return($response->getXML());

}







//////////////////////////////////////////////////////////////////////////////
// Function: get_subnet_html($subnet_ip)
//
// Description:
//     Builds HTML for displaying a little info about a subnet for a TT popup
//     Returns a two part array ($html, $js)
//     If $subnet_ip isn't a valid subnet, a "add subnet" link is displayed
//////////////////////////////////////////////////////////////////////////////
function get_subnet_html($subnet_ip) {
    global $conf, $self, $onadb;
    global $font_family, $color, $style, $images;

    $html = $js = '';

    $font_color = '#FFFFFF';

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

    // Load the subnet record
    list($status, $rows, $subnet) = ona_find_subnet($subnet_ip);

    // If we didn't get one, tell them to add a record here
    if ($rows == 0 or $status) {
        // Calculate what the end of this block is so we can reccomend the max subnet size

        // GD: add IPv6 functionnality by imposing GMP use when not ipv4 subnet
        
        list($status, $rows, $subnets) = db_get_records($onadb, "subnets", "ip_addr > " . ip_mangle($subnet_ip, 'numeric'), "ip_addr", 1);
	if (!is_ipv4($subnet_ip)) {
	    if ($rows >= 1) {
	        $subnet_ip_end = gmp_sub(gmp_init($subnets[0]['ip_addr']), 1);
	        $size = gmp_add(gmp_sub($subnet_ip_end, $subnet_ip), 1);
	        if (gmp_mod($size,2) == 1) gmp_sub($size,1);
	        // GD: very bad way to get the mask ... but gmp_log() does not exist !
	        for ($mask=65;$mask > 48;$mask--) {
		    if (gmp_cmp($size,gmp_pow("2",$mask)) > 0) {
		        $mask++;
		        break;
		    }
	        }
	        $subnet['ip_addr'] = ip_mangle(gmp_strval($subnet_ip), 'dotted');
	        $subnet['ip_addr_end'] = ip_mangle(gmp_strval($subnet_ip_end), 'dotted');
	        $str_subnet_ip = gmp_strval($subnet_ip);
	        $size=gmp_strval($size);
            }
            else {
                $subnet_ip_end = -1;
                $size=0;
            }
	}
	else {
            if ($rows >= 1) {
	        $subnet_ip_end = $subnets[0]['ip_addr'] - 1;
	        $size = $subnet_ip_end - $subnet_ip + 1;
	        if (($size % 2) == 1) $size--;
	        $mask = ceil(32 - (log($size) / log(2)));
	        $subnet['ip_addr'] = ip_mangle($subnet_ip, 'dotted');
	        $subnet['ip_addr_end'] = ip_mangle($subnet_ip_end, 'dotted');
	        $str_subnet_ip = $subnet_ip;
            }
            else {
                $subnet_ip_end=-1;
                $size=0;
            }
        }
        $html .= <<<EOL
        <!-- NO SUBNET -->
        <table cellspacing="0" border="0" cellpadding="0">

            <!-- LABEL -->
            <tr><td width=100% colspan="2" nowrap="true" style="{$style['label_box']}">
                <a title="Add a new subnet here"
                   class="act"
                   onClick="xajax_window_submit('edit_subnet', 'ip_addr=>{$str_subnet_ip}', 'editor');"
                >Add a subnet here</a>
            </td></tr>

            <tr>
                <td align="right" nowrap="true" style="color: {$font_color};"><b>IP Range</b>&nbsp;</td>
                <td class="padding" nowrap="true" align="left" style="color: {$font_color};">{$subnet['ip_addr']}&nbsp;-&nbsp;{$subnet['ip_addr_end']}&nbsp({$size} addresses)</td>
            </tr>

EOL;
        $largest_subnet = array(0, 0, 0);
        // -- IPv4
        if (is_ipv4($subnet_ip)) {
            $ip = $subnet_ip;
            while ($ip < $subnet_ip_end) {

                // find the largest mask for the specified ip
                $myip = ip_mangle($ip, 'dotted');
                $mymask = $mask;
                while ($mymask <= 30) {
                    $ip1 = ip_mangle($ip, 'binary');
                    $ip2 = str_pad(substr($ip1, 0, $mymask), 32, '0');
                    $mysize = pow(2, 32-$mymask);
                    $myhosts = $mysize - 2;
    
                    $ip1 = ip_mangle($ip1, 'dotted');
                    $ip2 = ip_mangle($ip2, 'dotted');
                    if ( $ip1 == $ip2 and (($ip + $mysize - 1) <= $subnet_ip_end) ) {
                        break;
                    }
                    $mymask++;
                }
                if ($mymask == 31) break;
                if ($mysize > $largest_subnet[2]) $largest_subnet = array(ip_mangle($ip, 'dotted'), $mymask, $mysize);
    
    
                $html .= <<<EOL
                <!--
                <tr>
                    <td align="right" nowrap="true" style="color: {$font_color};">&nbsp;</td>
                    <td class="padding" align="left" style="color: {$font_color};">{$myip} /{$mymask}&nbsp;({$myhosts} hosts)</td>
                </tr>
                -->
EOL;
    
                // Increment $ip
                $ip += $mysize;


            }
            // remove 2 for gateway and broadcast
            $largest_subnet[2] = $largest_subnet[2] - 2;
        }
        // -- IPV6
        else {
          
            $ip = gmp_init($subnet_ip);
	    // GD: trying to avoid falling into time/memory-trap
	    // Won't enter in the loop if difference between IP and next subnet IP is too big
	    // (more than 5 x /64)
	    if (gmp_cmp(gmp_sub($subnet_ip_end,$ip),gmp_mul("18446744073709551616","5")) > 0) {

		$html .= <<<EOL
	<tr>
	<td align="right" nowrap="true" style="color: {$font_color};">&nbsp;</td>
	<td align="right" nowrap="true" style="color: {$font_color};">Next Subnet too far away</td>
	<tr>
EOL;
		return(array($html, $js));
	    }
	
            while (gmp_cmp($ip, $subnet_ip_end) < 0) {

                // find the largest mask for the specified ip
                $myip = ip_mangle(gmp_strval($ip), 'dotted');
                $mymask = $mask;
                while ($mymask <= 64) {
                    $ip1 = ip_mangle(gmp_strval($ip), 'bin128');
                    $ip2 = str_pad(substr($ip1, 0, $mymask), 128, '0');
                    $mysize = gmp_pow("2", 128-$mymask);
                    $myhosts = gmp_strval(gmp_sub($mysize,2));

                    $ip1 = ip_mangle($ip1, 'dotted');
                    $ip2 = ip_mangle($ip2, 'dotted');
                    if ((strcmp($ip1, $ip2) == 0) and (gmp_cmp(gmp_sub(gmp_add($ip, $mysize),1), $subnet_ip_end) <= 0 )) {
                        break;
                    }
                    $mymask++;
                }
                if ($mymask == 90) break;
                if (gmp_cmp($mysize,$largest_subnet[2]) > 0) $largest_subnet = array(ip_mangle(gmp_strval($ip), 'dotted'), $mymask, $mysize);

                $html .= <<<EOL
                <!--
                <tr>
                    <td align="right" nowrap="true" style="color: {$font_color};">&nbsp;</td>
                    <td class="padding" align="left" style="color: {$font_color};">{$mask} {$myip} /{$mymask}&nbsp;({$myhosts} hosts)td>
                </tr>
                -->
EOL;

                // Increment $ip
                $ip = gmp_add($ip,$mysize);
            }
            // DON'T remove 2 for gateway and broadcast
            $largest_subnet[2] = gmp_strval($largest_subnet[2]);

	
        }
        $html .= <<<EOL

            <tr>
                <td align="right" nowrap="true" style="color: {$font_color};"><b>Largest block</b>&nbsp;</td>
                <td class="padding" nowrap="true" align="left" style="color: {$font_color};">{$largest_subnet[0]} /{$largest_subnet[1]}&nbsp;({$largest_subnet[2]} usable hosts)</td>
            </tr>

        </table>
EOL;
        return(array($html, $js));
    }

    // -- 
    // -- FOUND SUBNET IN DB
    // -- 

    // Convert IP and Netmask to a presentable format
    $subnet['ip_addr'] = ip_mangle($subnet['ip_addr'], 'dotted');
    $subnet['ip_mask'] = ip_mangle($subnet['ip_mask'], 'dotted');
    $subnet['ip_mask_cidr'] = ip_mangle($subnet['ip_mask'], 'cidr');

    list($status, $rows, $type) = ona_get_subnet_type_record(array('id' => $subnet['subnet_type_id']));
    $subnet['type'] = $type['display_name'];


    // Calculate the percentage of the subnet that's used (total size - allocated hosts - dhcp pool size)
    $usage_html = get_subnet_usage_html($subnet['id']);

    foreach(array_keys((array)$subnet) as $key) { $subnet[$key] = htmlentities($subnet[$key], ENT_QUOTES, $conf['php_charset']); }
    foreach(array_keys((array)$location) as $key) { $location[$key] = htmlentities($location[$key], ENT_QUOTES, $conf['php_charset']); }

    $html .= <<<EOL

        <!-- SUBNET INFORMATION -->
        <table cellspacing="0" border="0" cellpadding="0">

            <!-- LABEL -->
            <tr><td width=100% colspan="2" nowrap="true" style="{$style['label_box']}">
                <a title="View subnet. ID: {$subnet['id']}"
                   class="nav"
                   onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_subnet\', \'subnet_id=>{$subnet['id']}\', \'display\')');"
                >{$subnet['name']}</a>
            </td></tr>

            <tr>
                <td align="right" nowrap="true" style="color: {$font_color};"><b>IP Address</b>&nbsp;</td>
                <td class="padding" align="left" style="color: {$font_color};">{$subnet['ip_addr']}&nbsp;/{$subnet['ip_mask_cidr']}</td>
            </tr>

            <tr>
                <td align="right" nowrap="true" style="color: {$font_color};"><b>Usage</b>&nbsp;</td>
                <td class="padding" align="left" style="color: {$font_color};">{$usage_html}</td>
            </tr>

EOL;
    if ($subnet['type']) {
        $html .= <<<EOL
            <tr>
                <td align="right" nowrap="true" style="color: {$font_color};"><b>Type</b>&nbsp;</td>
                <td class="padding" align="left" style="color: {$font_color};">{$subnet['type']}&nbsp;</td>
            </tr>
EOL;
    }


    $html .= <<<EOL
        </table>
EOL;

    return(array($html, $js));
}









//////////////////////////////////////////////////////////////////////////////
// Function: quick_subnet_search($form)
//
// Description:
//     Builds HTML for displaying a quick search popup.
//     Returns a two part array ($html, $js)
//////////////////////////////////////////////////////////////////////////////
function quick_subnet_search($form) {
    global $conf, $self, $onadb;
    global $font_family, $color, $style, $images;
    $html = $js = '';
    $font_color = '#FFFFFF';

    // Build subnet type list
    list($status, $rows, $records) = db_get_records($onadb, 'subnet_types', 'id >= 1', 'name');
    $subnet_type_list = '<option value="">&nbsp;</option>\n';
    $record['name'] = htmlentities($record['name']);
    foreach ($records as $record) {
        $subnet_type_list .= "<option value=\"{$record['id']}\">{$record['name']}</option>\n";
    }

    $js .= <<<EOL
    suggest_setup('ip_subnet_qf',      'suggest_ip_subnet_qf');
    suggest_setup('ip_subnet_thru_qf', 'suggest_ip_subnet_thru_qf');
    suggest_setup('subnet_qf', 'suggest_subnet_qf');
EOL;

    $style['content_box'] = <<<EOL
        padding: 2px 4px;
        vertical-align: top;
EOL;

    // WARNING: this one's different than most of them!
    $style['label_box'] = <<<EOL
        font-weight: bold;
        cursor: move;
        color: #FFFFFF;
EOL;

    $html .= <<<EOL

    <!-- SUBNET QUICK SEARCH -->
    <form id="quick_subnet_search_form" onSubmit="return(false);">
    <input type="hidden" name="id" value="{$form['id']}">
    <input type="hidden" name="input_id" value="{$form['input_id']}">
    <input type="hidden" name="content_id" value="qf_subnet_results">
    <table id="subnet_search" style="{$style['content_box']}" cellspacing="0" border="0" cellpadding="0">

    <tr>
        <td colspan="2" align="center" class="qf-search-line" style="{$style['label_box']}; padding-top: 0px;" onMouseDown="dragStart(event, '{$form['id']}', 'savePosition', 0);">
            Subnet Quick Search
        </td>
    </tr>

    <tr>
        <td align="right" class="qf-search-line">
            Subnet <u>T</u>ype
        </td>
        <td align="left" class="qf-search-line">
            <select id="nettype" name="nettype" class="edit" accesskey="u" accesskey="t" >
                {$subnet_type_list}
            </select>
        </td>
    </tr>

    <tr>
        <td align="right" class="qf-search-line">
            Subnet <u>D</u>esc
        </td>
        <td align="left" class="qf-search-line">
            <input id="subnet_qf" name="netdesc" type="text" class="edit" size="32" accesskey="d" />
            <div id="suggest_subnet_qf" class="suggest"></div>
        </td>
    </tr>

    <tr>
        <td align="right" class="qf-search-line">
            <u>I</u>P Address
        </td>
        <td align="left" class="qf-search-line" nowrap="true">
            <input id="ip_subnet_qf" name="ip_subnet" class="edit" type="text" size="15" accesskey="i" />
            <div id="suggest_ip_subnet_qf" class="suggest"></div>
            thru
            <input id="ip_subnet_thru_qf" name="ip_subnet_thru" class="edit" type="text" size="15">
            <div id="suggest_ip_subnet_thru_qf" class="suggest"></div>
        </td>
    </tr>

    <tr>
        <td align="right" class="qf-search-line">
            &nbsp;
        </td>
        <td align="right" class="qf-search-line">
            <input class="button" type="button" name="cancel" value="Cancel" onClick="removeElement('{$form['id']}');">
            <input class="button" type="button" name="search" value="Search" accesskey="s" onClick="xajax_window_submit('search_results_qf', xajax.getFormValues('quick_subnet_search_form'), 'subnet');">
        </td>
    </tr>

    <tr>
        <td colspan="2" align="left" class="qf-search-line">
            <div
              id="qf_subnet_results"
              style="
                display: none;
                background-color: #FFFFFF;
                overflow: auto;
                height: 300px;"
            ></div>
        </td>
    </tr>

    </table>
    </form>
EOL;

    return(array($html, $js));
}













//////////////////////////////////////////////////////////////////////////////
// Function: quick_location_search($form)
//
// Description:
//     Builds HTML for displaying a quick search popup.
//     Returns a two part array ($html, $js)
//////////////////////////////////////////////////////////////////////////////
function quick_location_search($form) {
    global $conf, $self, $onadb;
    global $font_family, $color, $style, $images;
    $html = $js = '';
    $font_color = '#FFFFFF';


    $js .= <<<EOL
EOL;

    $style['content_box'] = <<<EOL
        padding: 2px 4px;
        vertical-align: top;
EOL;

    // WARNING: this one's different than most of them!
    $style['label_box'] = <<<EOL
        font-weight: bold;
        cursor: move;
        color: #FFFFFF;
EOL;

    $html .= <<<EOL

    <!-- LOCATION QUICK SEARCH -->
    <form id="quick_location_search_form" onSubmit="return(false);">
    <input type="hidden" name="id" value="{$form['id']}">
    <input type="hidden" name="input_id" value="{$form['input_id']}">
    <input type="hidden" name="content_id" value="qf_location_results">
    <table id="location_search" style="{$style['content_box']}" cellspacing="0" border="0" cellpadding="0">

    <tr>
        <td colspan="2" align="center" class="qf-search-line" style="{$style['label_box']}; padding-top: 0px;" onMouseDown="dragStart(event, '{$form['id']}', 'savePosition', 0);">
            Location Quick Search
        </td>
    </tr>

    <tr>
        <td align="right" class="qf-search-line">
            <u>R</u>eference
        </td>
        <td align="left" class="qf-search-line">
             <input name="reference" type="text" class="edit" size="32" accesskey="r" />
        </td>
    </tr>

    <tr>
        <td align="right" class="qf-search-line">
            <u>N</u>ame
        </td>
        <td align="left" class="qf-search-line">
             <input name="name" type="text" class="edit" size="32" accesskey="n" />
        </td>
    </tr>

    <tr>
        <td align="right" class="qf-search-line">
            <u>A</u>ddress
        </td>
        <td align="left" class="qf-search-line">
            <input name="address" type="text" class="edit" size="32" accesskey="a" />
        </td>
    </tr>

    <tr>
        <td align="right" class="qf-search-line">
            <u>C</u>ity
        </td>
        <td align="left" class="qf-search-line" nowrap="true">
            <input name="city" class="edit" type="text" size="20" accesskey="c" />&nbsp;
            State: <input name="state" class="edit" type="text" size="2" maxlength="2" />
        </td>
    </tr>

    <tr>
        <td align="right" class="qf-search-line">
            <u>Z</u>ip
        </td>
        <td align="left" class="qf-search-line" nowrap="true">
            <input name="zip" class="edit" type="text" size="10" maxlength="10" accesskey="z" />
        </td>
    </tr>

    <tr>
        <td align="right" class="qf-search-line">
            &nbsp;
        </td>
        <td align="right" class="qf-search-line">
            <input class="button" type="button" name="cancel" value="Cancel" onClick="removeElement('{$form['id']}');">
            <input class="button" type="button" name="search" value="Search" accesskey="s" onClick="xajax_window_submit('search_results_qf', xajax.getFormValues('quick_location_search_form'), 'location');">
        </td>
    </tr>

    <tr>
        <td colspan="2" align="left" class="qf-search-line">
            <div
              id="qf_location_results"
              style="
                display: none;
                background-color: #FFFFFF;
                overflow: auto;
                height: 300px;"
            ></div>
        </td>
    </tr>

    </table>
    </form>
EOL;

    return(array($html, $js));
}









//////////////////////////////////////////////////////////////////////////////
// Function: quick_vlan_search($form)
//
// Description:
//     Builds HTML for displaying a quick search popup.
//     Returns a two part array ($html, $js)
//////////////////////////////////////////////////////////////////////////////
function quick_vlan_search($form) {
    global $conf, $self, $onadb;
    global $font_family, $color, $style, $images;
    $html = $js = '';
    $font_color = '#FFFFFF';

    $style['content_box'] = <<<EOL
        padding: 2px 4px;
        vertical-align: top;
EOL;

    // WARNING: this one's different than most of them!
    $style['label_box'] = <<<EOL
        font-weight: bold;
        cursor: move;
        color: #FFFFFF;
EOL;

    $html .= <<<EOL

    <!-- VLAN QUICK SEARCH -->
    <form id="quick_vlan_search_form" onSubmit="return(false);">
    <input type="hidden" name="id" value="{$form['id']}">
    <input type="hidden" name="input_id" value="{$form['input_id']}">
    <input type="hidden" name="text_id" value="{$form['text_id']}">
    <input type="hidden" name="content_id" value="qf_vlan_results">
    <table style="{$style['content_box']}" cellspacing="0" border="0" cellpadding="0">

    <tr><td colspan="2" align="center" class="qf-search-line" style="{$style['label_box']}; padding-top: 0px;" onMouseDown="dragStart(event, '{$form['id']}', 'savePosition', 0);">
        VLAN Quick Select
    </td></tr>

    <tr>
        <td align="right" class="qf-search-line">
            <u>C</u>ampus
        </td>
        <td align="left" class="qf-search-line">
            <input id="vlan_campus_qf" name="campus" type="text" class="edit" size="24" accesskey="c" onClick="el('qf_vlan_results').style.display = 'none';" />
            <div id="suggest_vlan_campus_qf" class="suggest"></div>
        </td>
    </tr>

    <tr>
        <td align="right" class="qf-search-line">
            &nbsp;
        </td>
        <td align="right" class="qf-search-line">
            <input class="button" type="button" name="cancel" value="Cancel" onClick="removeElement('{$form['id']}');">
            <input class="button" type="button" name="search" value="Search" accesskey="s" onClick="xajax_window_submit('search_results_qf', xajax.getFormValues('quick_vlan_search_form'), 'vlan');">
        </td>
    </tr>

    <tr>
        <td colspan="2" align="left" class="qf-search-line">
            <div
              id="qf_vlan_results"
              style="
                display: none;
                background-color: #FFFFFF;
                overflow: auto;
                height: 300px;"
            ></div>
        </td>
    </tr>

    </table>
    </form>
EOL;

    // Javascript to run after the window is built
    $js = <<<EOL
        suggest_setup('vlan_campus_qf', 'suggest_vlan_campus_qf');
EOL;

    return(array($html, $js));
}









//////////////////////////////////////////////////////////////////////////////
// Function: quick_free_ip_search($form)
//
// Description:
//     Builds HTML for displaying a quick search popup.
//     Returns a two part array ($html, $js)
//////////////////////////////////////////////////////////////////////////////
function quick_free_ip_search($form) {
    global $conf, $self, $onadb;
    global $font_family, $color, $style, $images;
    $html = $js = '';
    $font_color = '#FFFFFF';

    // Build subnet type list
    list($status, $rows, $records) = db_get_records($onadb, 'subnet_types', 'id >= 1', 'display_name');
    $subnet_type_list = '<option value="">&nbsp;</option>\n';
    $record['display_name'] = htmlentities($record['display_name']);
    foreach ($records as $record) {
        $subnet_type_list .= "<option value=\"{$record['id']}\">{$record['display_name']}</option>\n";
    }

    $js .= <<<EOL
    suggest_setup('ip_subnet_qf',      'suggest_ip_subnet_qf');
    suggest_setup('ip_subnet_thru_qf', 'suggest_ip_subnet_thru_qf');
    suggest_setup('subnet_qf', 'suggest_subnet_qf');
EOL;

    $style['content_box'] = <<<EOL
        padding: 2px 4px;
        vertical-align: top;
EOL;

    // WARNING: this one's different than most of them!
    $style['label_box'] = <<<EOL
        font-weight: bold;
        cursor: move;
        color: #FFFFFF;
EOL;

    foreach(array_keys((array)$form) as $key) { $form[$key] = htmlentities($form[$key], ENT_QUOTES, $conf['php_charset']); }

    $html .= <<<EOL
    <!-- FREE IP QUICK SEARCH -->
    <form id="quick_free_ip_search_form" onSubmit="return(false);">
    <input type="hidden" name="id" value="{$form['id']}">
    <input type="hidden" name="input_id" value="{$form['input_id']}">
    <input type="hidden" name="text_id" value="{$form['text_id']}">
    <input type="hidden" name="content_id" value="qf_free_ip_results">
    <input type="hidden" name="next_action" value="free_ip">
    <input type="hidden" name="form_id" value="quick_free_ip_search_form">
    <!-- The subnet_id field gets filled by clicking on a subnet result later -->
    <input type="hidden" id="qf_free_ip_subnet_id" name="subnet_id" value="">
    <table style="{$style['content_box']}" cellspacing="0" border="0" cellpadding="0">

    <tr><td colspan="2" align="center" class="qf-search-line" style="{$style['label_box']}; padding-top: 0px;" onMouseDown="dragStart(event, '{$form['id']}', 'savePosition', 0);">
        Available IP Quick Search
    </td></tr>

    <tr>
        <td align="right" class="qf-search-line">
            Subnet <u>T</u>ype
        </td>
        <td align="left" class="qf-search-line">
            <select id="nettype" name="nettype" class="edit" accesskey="u" accesskey="t" >
                {$subnet_type_list}
            </select>
        </td>
    </tr>

    <tr>
        <td align="right" class="qf-search-line">
            Subnet <u>D</u>esc
        </td>
        <td align="left" class="qf-search-line">
            <input id="subnet_qf" name="netdesc" type="text" class="edit" size="32" accesskey="d" value="{$form['text_value']}" />
            <div id="suggest_subnet_qf" class="suggest"></div>
        </td>
    </tr>

    <tr>
        <td align="right" class="qf-search-line">
            <u>I</u>P Address
        </td>
        <td align="left" class="qf-search-line" nowrap="true">
            <input id="ip_subnet_qf" name="ip_subnet" class="edit" type="text" size="15" accesskey="i" />
            <div id="suggest_ip_subnet_qf" class="suggest"></div>
            thru
            <input id="ip_subnet_thru_qf" name="ip_subnet_thru" class="edit" type="text" size="15">
            <div id="suggest_ip_subnet_thru_qf" class="suggest"></div>
        </td>
    </tr>

    <tr>
        <td align="right" class="qf-search-line">
            &nbsp;
        </td>
        <td align="right" class="qf-search-line">
            <input class="button" type="reset" name="reset" value="Reset">
            <input class="button" type="button" name="cancel" value="Cancel" onClick="removeElement('{$form['id']}');">
            <input class="button" type="button" name="search" value="Search" accesskey="s" onClick="xajax_window_submit('search_results_qf', xajax.getFormValues('quick_free_ip_search_form'), 'subnet');">
        </td>
    </tr>

    <tr>
        <td colspan="2" align="left" class="qf-search-line">
            <div
              id="qf_free_ip_results"
              style="
                display: none;
                background-color: #FFFFFF;
                overflow: auto;
                height: 300px;"
            ></div>
        </td>
    </tr>

    </table>
    </form>
EOL;

    if ($form['text_value'] != "") {
        $js .= "xajax_window_submit('search_results_qf', xajax.getFormValues('quick_free_ip_search_form'), 'subnet');";
    }
    return(array($html, $js));
}








//////////////////////////////////////////////////////////////////////////////
// Function: quick_pool_server_search($form)
//
// Description:
//     Builds HTML for displaying a quick search popup.
//     Returns a two part array ($html, $js)
//////////////////////////////////////////////////////////////////////////////
function quick_pool_server_search($form) {
    global $conf, $self, $onadb;
    global $font_family, $color, $style, $images;
    $html = $js = '';
    $font_color = '#FFFFFF';


    // Build failover group list
    list($status, $rows, $fg) = db_get_records($onadb, 'dhcp_failover_groups', 'id >= 1', 'id');
    $fg_list = '<option value="0">None</option>\n';

    foreach ($fg as $record) {
        list($status, $rows, $fail_host1) = ona_find_host($record['primary_server_id']);
        list($status, $rows, $fail_host2) = ona_find_host($record['secondary_server_id']);

        $selected = "";
        if ($record['id'] == $form['failover_group_id']) { $selected = "SELECTED=\"selected\""; }
        if ($record['id']) {
            $fg_list .= "<option {$selected} value=\"{$record['id']}\">{$fail_host1['fqdn']}/{$fail_host2['fqdn']}</option>\n";
        }
    }

    $js .= <<<EOL
    suggest_setup('pool_server_qf', 'suggest_pool_server_qf');

EOL;

    $style['content_box'] = <<<EOL
        padding: 2px 4px;
        vertical-align: top;
EOL;

    // WARNING: this one's different than most of them!
    $style['label_box'] = <<<EOL
        font-weight: bold;
        cursor: move;
        color: #FFFFFF;
EOL;

    $html .= <<<EOL

    <!-- POOL SERVER QUICK SEARCH -->
    <form id="quick_pool_server_search_form" onSubmit="return(false);">
    <input type="hidden" name="id" value="{$form['id']}">
    <input type="hidden" name="input_id" value="{$form['input_id']}">
    <input type="hidden" name="content_id" value="qf_pool_server_results">
    <table id="pool_server_search" style="{$style['content_box']}" cellspacing="0" border="0" cellpadding="0">

    <tr>
        <td colspan="2" align="center" class="qf-search-line" style="{$style['label_box']}; padding-top: 0px;" onMouseDown="dragStart(event, '{$form['id']}', 'savePosition', 0);">
            DHCP Pool Failover Server Quick Select
        </td>
    </tr>

    <tr>
        <td align="right" class="qf-search-line" nowrap="true">
            Server <u>g</u>roup
        </td>
        <td align="left" class="qf-search-line">
            <select id="failover_group_qf" name="failover_group" class="edit" accesskey="f" onClick="el('pool_server_qf').value = '';">
                {$fg_list}
            </select>
        </td>
    </tr>

    <tr>
        <td align="right" class="qf-search-line">
            &nbsp;
        </td>
        <td align="right" class="qf-search-line">
            <input class="button" type="button" name="cancel" value="Cancel" onClick="removeElement('{$form['id']}');">
            <input class="button" type="button" name="select" value="Select" accesskey="s" onClick="el('{$form['failover_group']}').value = failover_group_qf.options[failover_group_qf.selectedIndex].value; if (failover_group_qf.options[failover_group_qf.selectedIndex].value) el('{$form['text_id']}').innerHTML = failover_group_qf.options[failover_group_qf.selectedIndex].innerHTML; removeElement('{$form['id']}');">
        </td>
    </tr>


    </table>
    </form>
EOL;

    return(array($html, $js));
}







//////////////////////////////////////////////////////////////////////////////
// Function: get_host_interface_list_html($form)
//
// Description:
//     Builds HTML for displaying info about multiple host interfaces
//     Returns a two part array ($html, $js)
//////////////////////////////////////////////////////////////////////////////
function get_host_interface_list_html($form) {
    global $conf, $self, $onadb;
    global $font_family, $color, $style, $images;

    $html = $js = '';

    // Interface Record
    list($status, $introws, $interfaces) = db_get_records($onadb, 'interfaces', "host_id = {$form['host_id']} or id in (select interface_id from interface_clusters where host_id ={$form['host_id']})", 'ip_addr ASC');

    if ($introws == 0 or $status) return(array('', ''));


    $style['label_box'] = <<<EOL
        font-weight: bold;
        padding: 2px 4px;
        text-align: center;
        border: solid 1px {$color['border']};
        background-color: {$color['window_content_bg']};
EOL;

    $html .= <<<EOL
        <!-- INTERFACE INFORMATION -->
        <table cellspacing="0" border="0" cellpadding="0">

            <!-- LABEL -->
            <tr>
                <td colspan=5 style="{$style['label_box']}">{$introws} interface(s)</td>
            </tr>
EOL;

    $i = 0;

    foreach($interfaces as $interface) {
        list($status, $rows, $subnet) = ona_get_subnet_record(array('id'=>$interface['subnet_id']));
        foreach(array_keys((array)$interface) as $key) { $interface[$key] = htmlentities($interface[$key], ENT_QUOTES, $conf['php_charset']); }
        foreach(array_keys((array)$subnet) as $key) { $subnet[$key] = htmlentities($subnet[$key], ENT_QUOTES, $conf['php_charset']); }
        $ip = ip_mangle($interface['ip_addr'],'dotted');

        $clusticon = '';
        list($status, $clustrows, $clustrecord) = ona_get_record(array('interface_id' => $interface['id']), 'interface_clusters');
        if ($interface['host_id'] != $form['host_id'] or $clustrows>0) $clusticon = "<img title=\"This interface is shared with another host.\" src=\"{$images}/silk/sitemap.png\" border=\"0\">";

        $html .= <<<EOL
            <tr>
                <td align="left" style="color: #FFFFFF;" nowrap="true">{$clusticon}&nbsp;</td>
                <td align="left" class="padding" style="color: #FFFFFF;" nowrap="true">{$ip}</td>
                <td align="left" class="padding" style="color: #FFFFFF;" nowrap="true">
                    <a title="View subnet. ID: {$subnet['id']}"
                         style="color: #6CB3FF;"
                         class="nav"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_subnet\', \'subnet_id=>{$subnet['id']}\', \'display\')'); removeElement('{$form['id']}');"
                    >{$subnet['name']}</a>&nbsp;</td>
            </tr>

EOL;

        // increment counter
        $i++;

        if ($i == 15) {
            $html .= <<<EOL
            <tr>
                <td align="center" class="padding" style="color: #FFFFFF;" nowrap="true" colspan=2>
                    Only displaying first 15 interfaces on host.&nbsp;&nbsp;
                    <a title="View host. ID: {$interface['host_id']}"
                         style="color: #6CB3FF;"
                         class="nav"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_host\', \'host_id=>{$interface['host_id']}\', \'display\')'); removeElement('{$form['id']}');"
                    >View Host</a>&nbsp;</td>
            </tr>
EOL;
        break;
        }

    }
    $html .= <<<EOL
        </table>
EOL;

    return(array($html, $js));
}









//////////////////////////////////////////////////////////////////////////////
// Function: get_interface_cluster_list_html($form)
//
// Description:
//     Builds HTML for displaying info about interface cluster hosts
//     Returns a two part array ($html, $js)
//////////////////////////////////////////////////////////////////////////////
function get_interface_cluster_list_html($form) {
    global $conf, $self, $onadb;
    global $font_family, $color, $style, $images;

    $html = $js = '';

    // Interface Record
    list($status, $introws, $interfaces) = db_get_records($onadb, 'interface_clusters', "interface_id = {$form['interface_id']}");
    if ($introws == 0 or $status) return(array('', ''));

    // Get primary host info
    list($status, $rows, $priint) = ona_get_interface_record(array('id'=>$form['interface_id']));
    list($status, $rows, $prihost) = ona_get_host_record(array('id'=>$priint['host_id']));

    $priip = ip_mangle($priint['ip_addr'], dotted);

    // add one for primary host
    $introws=$introws+1;

    $style['label_box'] = <<<EOL
        font-weight: bold;
        padding: 2px 4px;
        text-align: center;
        border: solid 1px {$color['border']};
        background-color: {$color['window_content_bg']};
EOL;

    $html .= <<<EOL
        <!-- INTERFACE CLUSTER INFORMATION -->
        <table cellspacing="0" border="0" cellpadding="0">

            <!-- LABEL -->
            <tr>
                <td colspan=3 style="{$style['label_box']}">{$introws} hosts share IP:<br>{$priip}</td>
            </tr>
            <tr>
                <td align="left" class="padding" style="color: #FFFFFF;" nowrap="true">
                    <a title="View host. ID: {$prihost['id']}"
                         style="color: #6CB3FF;"
                         class="nav"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_host\', \'host_id=>{$prihost['id']}\', \'display\')'); removeElement('{$form['id']}');"
                    >{$prihost['fqdn']}</a>&nbsp;</td>
                <td align="left" class="padding" style="color: #FFFFFF;" nowrap="true">{$priint['name']}</td>
            </tr>
EOL;

    $i = 0;

    foreach($interfaces as $interface) {
        list($status, $rows, $host) = ona_get_host_record(array('id'=>$interface['host_id']));
        foreach(array_keys((array)$interface) as $key) { $interface[$key] = htmlentities($interface[$key], ENT_QUOTES, $conf['php_charset']); }
        foreach(array_keys((array)$host) as $key) { $host[$key] = htmlentities($host[$key], ENT_QUOTES, $conf['php_charset']); }

        // If there is no cluster name then use the name from the primary interface
        if (!$interface['name'])
            $interface['name'] = $priint['name'];

        $html .= <<<EOL
            <tr>
                <td align="left" class="padding" style="color: #FFFFFF;" nowrap="true">
                    <form id="quick_interface_share_del_form" onSubmit="return(false);">
                    <input type="hidden" name="ip" value="{$interface['interface_id']}">
                    <input type="hidden" name="host" value="{$host['id']}">
                    </form>
                    <a title="View host. ID: {$host['id']}"
                         style="color: #6CB3FF;"
                         class="nav"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_host\', \'host_id=>{$host['id']}\', \'display\')'); removeElement('{$form['id']}');"
                    >{$host['fqdn']}</a>&nbsp;</td>
                <td align="left" class="padding" style="color: #FFFFFF;" nowrap="true">{$interface['name']}</td>
                <td align="left" class="padding" style="color: #FFFFFF;" nowrap="true">
                    <img src="{$images}/silk/delete.png"
                         title="Remove interface share with {$host['fqdn']}"
                         border="0"
                         onClick="xajax_window_submit('tooltips', xajax.getFormValues('quick_interface_share_del_form'), 'interface_share_del');removeElement('{$form['id']}');"
                    />
                </td>
            </tr>

EOL;

        // increment counter
        $i++;

        if ($i == 15) {
            $html .= <<<EOL
            <tr>
                <td align="center" class="padding" style="color: #FFFFFF;" nowrap="true" colspan=2>
                    Only displaying first 15 hosts in cluster.&nbsp;&nbsp;
                    <a title="View host. ID: {$interface['host_id']}"
                         style="color: #6CB3FF;"
                         class="nav"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_host\', \'host_id=>{$interface['host_id']}\', \'display\')'); removeElement('{$form['id']}');"
                    >View Primary Host</a>&nbsp;</td>
            </tr>
EOL;
        break;
        }

    }
    $html .= <<<EOL
        </table>
EOL;

    return(array($html, $js));
}










//////////////////////////////////////////////////////////////////////////////
// Function: quick_interface_share($form)
//
// Description:
//     Builds HTML for displaying a quick search popup.
//     Returns a two part array ($html, $js)
//////////////////////////////////////////////////////////////////////////////
function quick_interface_share($form) {
    global $conf, $self, $onadb;
    global $font_family, $color, $style, $images;
    $html = $js = '';
    $font_color = '#FFFFFF';

    $style['content_box'] = <<<EOL
        padding: 2px 4px;
        vertical-align: top;
EOL;

    // WARNING: this one's different than most of them!
    $style['label_box'] = <<<EOL
        font-weight: bold;
        cursor: move;
        color: #FFFFFF;
EOL;

    $refresh = "xajax_window_submit('list_interfaces', xajax.getFormValues('list_interfaces_filter_form'), 'display_list');";

    $html .= <<<EOL

    <!-- QUICK INTERFACE SHARE -->
    <form id="quick_interface_share_form" onSubmit="return(false);">
    <input type="hidden" name="id" value="{$form['id']}">
    <input type="hidden" name="js" value="{$form['js']}">
    <input type="hidden" name="ip" value="{$form['interface_id']}">
    <table style="{$style['content_box']}" cellspacing="0" border="0" cellpadding="0">

    <tr><td colspan="2" align="center" class="qf-search-line" style="{$style['label_box']}; padding-top: 0px;" onMouseDown="dragStart(event, '{$form['id']}', 'savePosition', 0);">
        Quick interface share
    </td></tr>

    <tr>
        <td align="right" class="qf-search-line">
            Share IP
        </td>
        <td align="left" class="qf-search-line">
            {$form['ip_addr']}
        </td>
    </tr>


    <tr>
        <td align="right" class="qf-search-line">
            to <u>H</u>ost
        </td>
        <td align="left" class="qf-search-line">
            <input id="share_hostname" name="host" type="text" class="edit" size="24" accesskey="h" autocomplete="off"/>
            <div id="suggest_share_hostname" class="suggest"></div>
        </td>
    </tr>

    <tr>
        <td align="right" class="qf-search-line">
            Int <u>N</u>ame
        </td>
        <td align="left" class="qf-search-line">
            <input name="name" type="text" class="edit" size="24" accesskey="n" />
        </td>
    </tr>

    <tr>
        <td align="right" class="qf-search-line">
            &nbsp;
        </td>
        <td align="right" class="qf-search-line">
            <input class="button" type="button" name="cancel" value="Cancel" onClick="removeElement('{$form['id']}');">
            <input class="button" type="button" name="share" value="Share" accesskey="s" onClick="xajax_window_submit('tooltips', xajax.getFormValues('quick_interface_share_form'), 'interface_share_save');removeElement('{$form['id']}');{$refresh};">
        </td>
    </tr>

    </table>
    </form>
EOL;

    // Javascript to run after the window is built
    $js .= <<<EOL
        el('share_hostname').focus();
        suggest_setup('share_hostname', 'suggest_share_hostname');
EOL;

    return(array($html, $js));
}





//////////////////////////////////////////////////////////////////////////////
// Function:
//     Save Form
//
// Description:
//     Creates/updates a interface cluster record.
//////////////////////////////////////////////////////////////////////////////
function ws_interface_share_save($window_name, $form='') {
    global $base, $include, $conf, $self, $onadb;

    // Check permissions
    if (! (auth('interface_del')) ) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = '';

    // Validate input
    if (!$form['host'] and !$form['ip']) {
        $response->addScript("alert('Please complete all fields to continue!');");
        return($response->getXML());
    }

    // Decide if we're editing or adding
    $module = 'interface_share';

    // Run the module
    list($status, $output) = run_module($module, $form);

    // If the module returned an error code display a popup warning
    if ($status)
        $js .= "alert('Save failed. ". preg_replace('/[\s\']+/', ' ', $self['error']) . "');";
    else {
        $js .= "removeElement('{$window_name}');";
        if ($form['js']) $js .= $form['js'];
    }

    // Insert the new table into the window
    $response->addScript($js);
    return($response->getXML());
}






//////////////////////////////////////////////////////////////////////////////
// Function:
//     Save Form
//
// Description:
//     Deletes a interface cluster record.
//////////////////////////////////////////////////////////////////////////////
function ws_interface_share_del($window_name, $form='') {
    global $base, $include, $conf, $self, $onadb;

    // Check permissions
    if (! (auth('advanced')) ) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = '';

    // Validate input
    if (!$form['host'] and !$form['ip']) {
        $response->addScript("alert('Please complete all fields to continue!');");
        return($response->getXML());
    }

    // Decide if we're editing or adding
    $module = 'interface_share_del';

    // Run the module
    list($status, $output) = run_module($module, $form);

    // If the module returned an error code display a popup warning
    if ($status)
        $js .= "alert('Delete failed. ". preg_replace('/[\s\']+/', ' ', $self['error']) . "');";
    else {
        $js .= "removeElement('{$window_name}');";
        if ($form['js']) $js .= $form['js'];
    }

    // Insert the new table into the window
    $response->addScript($js);
    return($response->getXML());
}









//////////////////////////////////////////////////////////////////////////////
// Function: quick_interface_menu($form)
//
// Description:
//     Builds HTML for displaying a quick menu popup.
//     Returns a two part array ($html, $js)
//////////////////////////////////////////////////////////////////////////////
function quick_interface_menu($form) {
    global $conf, $self, $onadb;
    global $font_family, $color, $style, $images;
    $html = $js = '';

    $html .= "<div style=\"text-align: center;color: black;padding-bottom:4px;font-weight: bold;\">Interface Actions<br/> [{$form['ip_addr']}]</div>";


    if (auth('interface_modify') and !$form['natip']) {
        $html .= <<<EOL
            <div class="row"
                onMouseOver="this.className='hovered';"
                onMouseOut="this.className='row';"
                title="Add an external NAT IP"
                class="act"
                onClick="wwTT(this, event,
                                        'id', 'tt_quick_interface_nat_{$form['interface_id']}',
                                        'type', 'static',
                                        'delay', 0,
                                        'styleClass', 'wwTT_qf',
                                        'direction', 'southwest',
                                        'javascript', 'xajax_window_submit(\'tooltips\', \'tooltip=>quick_interface_nat,id=>tt_quick_interface_nat_{$form['interface_id']},interface_id=>{$form['interface_id']},ip_addr=>{$form['ip_addr']}\');'
                                        );"
                ><img src="{$images}/silk/world_link.png" border="0">&nbsp; Add NAT IP</div>
EOL;
    }

    if (auth('interface_modify') and $form['natip']) {
        $html .= <<<EOL
            <div class="row"
                onMouseOver="this.className='hovered';"
                onMouseOut="this.className='row';"
                title="Delete the external NAT IP"
                class="act"
                onClick="var doit=confirm('Are you sure you want to delete this NAT address?\\nIt will remove any DNS names associated with the external IP.');
                            if (doit == true)
                                xajax_window_submit('tooltips', 'ip=>{$form['interface_id']},natip=>{$form['natip']},nataction=>delete,commit=>yes', 'interface_nat_save');"
                ><img src="{$images}/silk/world_delete.png" border="0">&nbsp; Delete NAT IP</div>
EOL;
    }


    if (auth('interface_modify')) {
        $html .= <<<EOL
            <div class="row"
                onMouseOver="this.className='hovered';"
                onMouseOut="this.className='row';"
                title="Move IP to another host"
                class="act"
                onClick="wwTT(this, event,
                                        'id', 'tt_quick_interface_move_{$form['interface_id']}',
                                        'type', 'static',
                                        'delay', 0,
                                        'styleClass', 'wwTT_qf',
                                        'direction', 'southwest',
                                        'javascript', 'xajax_window_submit(\'tooltips\', \'tooltip=>quick_interface_move,id=>tt_quick_interface_move_{$form['interface_id']},interface_id=>{$form['interface_id']},ip_addr=>{$form['ip_addr']},orig_host=>{$form['orig_host']}\');'
                                        );"
                ><img src="{$images}/silk/lorry_flatbed.png" border="0">&nbsp; Move IP</div>
EOL;
    }

    if (auth('interface_modify')) {
        $html .= <<<EOL
            <div class="row"
                onMouseOver="this.className='hovered';"
                onMouseOut="this.className='row';"
                title="Share IP with another host (hsrp,carp,vrrp)"
                class="act"
                onClick="wwTT(this, event,
                                        'id', 'tt_quick_interface_share_{$form['interface_id']}',
                                        'type', 'static',
                                        'delay', 0,
                                        'styleClass', 'wwTT_qf',
                                        'direction', 'southwest',
                                        'javascript', 'xajax_window_submit(\'tooltips\', \'tooltip=>quick_interface_share,id=>tt_quick_interface_share_{$form['interface_id']},interface_id=>{$form['interface_id']},ip_addr=>{$form['ip_addr']}\');'
                                        );"
                ><img src="{$images}/silk/sitemap.png" border="0">&nbsp; Share IP</div>
EOL;
    }

    if (auth('interface_modify')) {
        $html .= <<<EOL
            <div class="row"
                onMouseOver="this.className='hovered';"
                onMouseOut="this.className='row';"
                title="Add DNS record to this interface"
                class="act"
                onClick="xajax_window_submit('edit_record', xajax.getFormValues('{$form['form_id']}'), 'editor');setTimeout('toggleBox(\'edit_record\');',500);"
                ><img src="{$images}/silk/font_add.png" border="0">&nbsp; Add DNS</div>
EOL;
    }



    return(array($html, $js));
}





//////////////////////////////////////////////////////////////////////////////
// Function: quick_interface_nat($form)
//
// Description:
//     Builds HTML for displaying a quick search popup.
//     Returns a two part array ($html, $js)
//////////////////////////////////////////////////////////////////////////////
function quick_interface_nat($form) {
    global $conf, $self, $onadb;
    global $font_family, $color, $style, $images;
    $html = $js = '';
    $font_color = '#FFFFFF';

    $style['content_box'] = <<<EOL
        padding: 2px 4px;
        vertical-align: top;
EOL;

    // WARNING: this one's different than most of them!
    $style['label_box'] = <<<EOL
        font-weight: bold;
        cursor: move;
        color: #FFFFFF;
EOL;



    $html .= <<<EOL

    <!-- QUICK INTERFACE NAT -->
    <form id="quick_interface_nat_form" onSubmit="return(false);">
    <input type="hidden" name="id" value="{$form['id']}">
    <input type="hidden" name="js" value="{$form['js']}">
    <input type="hidden" name="nataction" value="add">
    <input type="hidden" name="ip" value="{$form['interface_id']}">
    <table style="{$style['content_box']}" cellspacing="0" border="0" cellpadding="0">

    <tr><td colspan="2" align="center" class="qf-search-line" style="{$style['label_box']}; padding-top: 0px;" onMouseDown="dragStart(event, '{$form['id']}', 'savePosition', 0);">
        Quick interface NAT
    </td></tr>

    <tr>
        <td align="right" class="qf-search-line">
            Internal IP
        </td>
        <td align="left" class="qf-search-line">
            {$form['ip_addr']}
        </td>
    </tr>


    <tr>
        <td align="right" class="qf-search-line">
            <u>E</u>xternal NAT IP
        </td>
        <td align="left" class="qf-search-line">
            <input id="natip" name="natip" type="text" class="edit" size="24" accesskey="e" value="{$form['natip']}" />
        </td>
    </tr>

    <tr>
        <td align="right" class="qf-search-line">
            &nbsp;
        </td>
        <td align="right" class="qf-search-line">
            <input class="button" type="button" name="cancel" value="Cancel" onClick="removeElement('{$form['id']}');">
            <input class="button" type="button" name="save" value="Save" accesskey="m" onClick="xajax_window_submit('tooltips', xajax.getFormValues('quick_interface_nat_form'), 'interface_nat_save');removeElement('{$form['id']}');">
        </td>
    </tr>

    </table>
    </form>
EOL;

    // Javascript to run after the window is built
    $js .= <<<EOL
        el('natip').focus();
EOL;

    return(array($html, $js));
}





//////////////////////////////////////////////////////////////////////////////
// Function:
//     Save Form
//
// Description:
//     Creates/updates an interface record.
//////////////////////////////////////////////////////////////////////////////
function ws_interface_nat_save($window_name, $form='') {
    global $base, $include, $conf, $self, $onadb;

    // Check permissions
    if (! (auth('interface_modify')) ) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }

    $form = parse_options_string($form);

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = '';

    $refresh = "xajax_window_submit('list_interfaces', xajax.getFormValues('list_interfaces_filter_form'), 'display_list');";

    // Validate input
    if (!$form['ip'] and !$form['natip']) {
        $response->addScript("alert('Please complete all fields to continue!');");
        return($response->getXML());
    }

    // Decide if we're deleting or adding
    $module = 'nat_add';
    if ($form['nataction'] == "delete") { $module = 'nat_del'; }

    // Do a pre check of the ptr domain so we can prompt the user properly
    if ($module == 'nat_add') {
        $ipflip = ip_mangle($form['natip'],'flip');
        $octets = explode(".",$ipflip);
        list($status, $rows, $ptrdomain) = ona_find_domain($ipflip.".in-addr.arpa");
        if (!$ptrdomain['id']) {
            printmsg("ERROR => This operation tried to create a PTR record that is the first in the {$octets[3]}.0.0.0 class A range.  You must first create at least the following DNS domain: {$octets[3]}.in-addr.arpa",3);
            $self['error'] = "ERROR => This operation tried to create a PTR record that is the first in the {$octets[3]}.0.0.0 class A range.  You must first create at least the following DNS domain: {$octets[3]}.in-addr.arpa.  You could also create domains for class B or class C level reverse zones.  Click OK to open add domain dialog";
            $response->addScript("alert('{$self['error']}');xajax_window_submit('edit_domain', 'newptrdomainname=>{$octets[3]}.in-addr.arpa', 'editor');");
            return($response->getXML());
        }
    }

    // Run the module
    list($status, $output) = run_module($module, $form);

    // If the module returned an error code display a popup warning
    if ($status)
        $js .= "alert('Save failed. ". preg_replace('/[\s\']+/', ' ', $self['error']) . "');";
    else {
        $js .= "removeElement('{$window_name}');{$refresh}";
        if ($form['js']) $js .= $form['js'];
    }


    // Insert the new table into the window
    $response->addScript($js);
    return($response->getXML());
}





//////////////////////////////////////////////////////////////////////////////
// Function: quick_interface_move($form)
//
// Description:
//     Builds HTML for displaying a quick search popup.
//     Returns a two part array ($html, $js)
//////////////////////////////////////////////////////////////////////////////
function quick_interface_move($form) {
    global $conf, $self, $onadb;
    global $font_family, $color, $style, $images;
    $html = $js = '';
    $font_color = '#FFFFFF';

    $style['content_box'] = <<<EOL
        padding: 2px 4px;
        vertical-align: top;
EOL;

    // WARNING: this one's different than most of them!
    $style['label_box'] = <<<EOL
        font-weight: bold;
        cursor: move;
        color: #FFFFFF;
EOL;


    // If this is the last interface, inform the user that the host is to be deleted
    list($status, $total_interfaces, $ints) = db_get_records($onadb, 'interfaces', array('host_id' => $form['orig_host']), '', 0);
    $lastint_js = '';
    if ($total_interfaces == 1) {
        $lastint_js = "var doit=confirm('This is the last interface on this host, the host will also be deleted once the interface is moved?'); if (doit == true) ";
    }


    $html .= <<<EOL

    <!-- QUICK INTERFACE MOVE -->
    <form id="quick_interface_move_form" onSubmit="return(false);">
    <input type="hidden" name="id" value="{$form['id']}">
    <input type="hidden" name="js" value="{$form['js']}">
    <input type="hidden" name="ip" value="{$form['interface_id']}">
    <input type="hidden" name="orig_host" value="{$form['orig_host']}">
    <table style="{$style['content_box']}" cellspacing="0" border="0" cellpadding="0">

    <tr><td colspan="2" align="center" class="qf-search-line" style="{$style['label_box']}; padding-top: 0px;" onMouseDown="dragStart(event, '{$form['id']}', 'savePosition', 0);">
        Quick interface move
    </td></tr>

    <tr>
        <td align="right" class="qf-search-line">
            Move IP
        </td>
        <td align="left" class="qf-search-line">
            {$form['ip_addr']}
        </td>
    </tr>


    <tr>
        <td align="right" class="qf-search-line">
            to <u>H</u>ost
        </td>
        <td align="left" class="qf-search-line">
            <input id="move_hostname" name="host" type="text" class="edit" size="24" accesskey="h" autocomplete="off" />
            <div id="suggest_move_hostname" class="suggest"></div>
        </td>
    </tr>

    <tr>
        <td align="right" class="qf-search-line">
            &nbsp;
        </td>
        <td align="right" class="qf-search-line">
            <input class="button" type="button" name="cancel" value="Cancel" onClick="removeElement('{$form['id']}');">
            <input class="button" type="button" name="move" value="Move" accesskey="m" onClick="{$lastint_js} xajax_window_submit('tooltips', xajax.getFormValues('quick_interface_move_form'), 'interface_move_save');removeElement('{$form['id']}');">
        </td>
    </tr>

    </table>
    </form>
EOL;

    // Javascript to run after the window is built
    $js .= <<<EOL
        el('move_hostname').focus();
        suggest_setup('move_hostname', 'suggest_move_hostname');
EOL;

    return(array($html, $js));
}





//////////////////////////////////////////////////////////////////////////////
// Function:
//     Save Form
//
// Description:
//     Creates/updates an interface record.
//////////////////////////////////////////////////////////////////////////////
function ws_interface_move_save($window_name, $form='') {
    global $base, $include, $conf, $self, $onadb;

    // Check permissions
    if (! (auth('advanced')) ) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = '';

    $refresh = "xajax_window_submit('list_interfaces', xajax.getFormValues('list_interfaces_filter_form'), 'display_list');";

    // Validate input
    if (!$form['host'] and !$form['ip']) {
        $response->addScript("alert('Please complete all fields to continue!');");
        return($response->getXML());
    }

    list($status, $total_interfaces, $ints) = db_get_records($onadb, 'interfaces', array('host_id' => $form['orig_host']), '', 0);

    // Decide if we're editing or adding
    $module = 'interface_move_host';

    // Run the module
    list($status, $output) = run_module($module, $form);

    // If the module returned an error code display a popup warning
    if ($status)
        $js .= "alert('Save failed. ". preg_replace('/[\s\']+/', ' ', $self['error']) . "');";
    else {
        // Check if this is the last interface, if it is, delete the host too.
        if ($total_interfaces == 0) {
            // Run the host del module
            list($status, $output) = run_module('host_del', array('host' => $form['orig_host'], 'commit' => 'y'));
            if ($status) {
                // If the host del failed, move the interface back to the original host to clean things up
                list($status, $output) = run_module('interface_move_host', array('host' => $form['orig_host'], 'ip' => $form['ip']));
                $js .= "alert('Host delete failed. ". preg_replace('/[\s\']+/', ' ', $self['error']) . "');";
            }
            else {
                $js .= "removeElement('{$window_name}');{$refresh}";
                if ($form['js']) $js .= $form['js'];
            }
        }
        else {
            $js .= "removeElement('{$window_name}');{$refresh}";
            if ($form['js']) $js .= $form['js'];
        }
    }


    // Insert the new table into the window
    $response->addScript($js);
    return($response->getXML());
}






//////////////////////////////////////////////////////////////////////////////
// Function: get_custom_attribute_info_html($form)
//
// Description:
//     Builds HTML for displaying info about custom attributes hosts
//     Returns a two part array ($html, $js)
//////////////////////////////////////////////////////////////////////////////
function get_custom_attribute_info_html($form) {
    global $conf, $self, $onadb;
    global $font_family, $color, $style, $images;

    $html = $js = '';

    list($status, $rows, $ca) = ona_get_custom_attribute_record(array('id' => $form['ca_id']));
    if ($rows == 0 or $status) return(array('', ''));


    $html .= <<<EOL
        <!-- Custom Attribute Info -->
        <table cellspacing="0" border="0" cellpadding="0">

        <tr>
            <td align="left" nowrap="true" colspan="99"><b><u>Custom Attribute Info</u></b>&nbsp;</td>
        </tr>

        <tr>
            <td align="right" nowrap="true" style="font-weight: bold;">
                {$window['edit_type']}
            </td>
            <td class="padding" align="left" width="100%">
                {$window['edit_type_value']}
            </td>
        </tr>

        <tr>
            <td align="right" nowrap="true" style="font-weight: bold;">
                Type
            </td>
            <td class="padding" align="left" width="100%">
                    {$ca['name']}
            </td>
        </tr>

        <tr>
            <td align="right" nowrap="true" style="font-weight: bold;">
                Value
            </td>
            <td class="padding" align="left" width="100%">
                <pre
                    name="value"
                    alt="Value"
                    style="font-family: inherit;background-color: white;"
                    rows="5"
                    cols="25"
                >{$ca['value']}</pre>
            </td>
        </tr>

        <tr>
            <td align="right" nowrap="true" style="font-weight: bold;">
                Notes
            </td>
            <td class="padding" align="left" width="100%">
                <pre
                    name="value"
                    alt="Value"
                    style="font-family: inherit;background-color: white;"
                    rows="5"
                    cols="25"
                >{$ca['notes']}</pre>
            </td>
        </tr>

EOL;

    $html .= <<<EOL
        </table>
EOL;

    return(array($html, $js));
}






//////////////////////////////////////////////////////////////////////////////
// Function: get_switchport_template_select($)
//
// Description:
//
//     Returns a two part array ($html, $js)
//////////////////////////////////////////////////////////////////////////////
function get_switchport_template_select($form) {
    global $conf, $self, $onadb;
    global $font_family, $color, $style, $images;

    $html = $js = '';


/*    $style['content_box'] = <<<EOL
        margin: 10px 20px;
        padding: 2px 4px;
        background-color: #FFFFFF;
        vertical-align: top;
EOL;

    $style['label_box'] = <<<EOL
        font-weight: bold;
        padding: 2px 4px;
        text-align: center;
        border: solid 1px {$color['border']};
        background-color: {$color['window_content_bg']};
EOL; */
    $style['content_box'] = <<<EOL
        padding: 2px 4px;
        vertical-align: top;
EOL;

    // WARNING: this one's different than most of them!
    $style['label_box'] = <<<EOL
        font-weight: bold;
        cursor: move;
        color: #FFFFFF;
EOL;

   $html .= <<<EOL
    <!-- SWITCHPORT TEMPLATE SELECT -->
    <form id="switchport_template_select_form" onSubmit="return(false);">
    <input type="hidden" name="host" value="{$form['host']}">
    <input type="hidden" name="input_id" value="{$form['input_id']}">
    <table id="switchport_template_select" style="{$style['content_box']}" cellspacing="0" border="0" cellpadding="0">
     <tr>
        <td colspan="2" align="center" class="qf-search-line" style="{$style['label_box']}; padding-top: 0px;" onMouseDown="dragStart(event, '{$form['id']}', 'savePosition', 0);">
            Switchport Template Select
        </td>
    </tr>
    <tr>
        <td align="right" class="qf-search-line">Switch:&nbsp;</td>
        <td align="left" class="qf-search-line">{$form['host']}</td>
    </tr>
    <tr>
        <td align="right" class="qf-search-line">Interface:&nbsp;</td>
        <td align="left" class="qf-search-line">{$form['if_name']}</td>
    </tr>
    <tr>
        <td align="right" class="qf-search-line">Current:&nbsp;</td>
        <td align="left" class="qf-search-line">{$form['original_value']}</td>
    </tr>
    <tr>
        <td align="right" class="qf-search-line">Last Changed:&nbsp;</td>
        <td align="left" class="qf-search-line">{$form['time']} by {$form['user']}</td>
    </tr>
    <tr>
        <td colspan="2" class="qf-search-line" align="left">
            <div id="qf_template_list" style="overflow: auto; display: block; background-color: rgb(255, 255, 255); height: 150px;">
            <table style="cursor: pointer;" border="0" cellpadding="0" cellspacing="0" width="100%"><tbody>
EOL;
    $templates=explode("|",$form['template_list']);
    foreach($templates as $template) {
        $orig_template = "orig_".$template;

        $html .= <<<EOL
            <tr onmouseover="this.className='row-highlight';"
                onmouseout="this.className='row-normal';"
                onclick="var _el1 = el('{$form['if_name']}');
                var _el2 = '{$form['original_value']}';
                _el1.value = '$template';
                _el1.style.backgroundColor = '#8ABBFF';
                if(_el1.value == _el2) { _el1.style.backgroundColor = '#FFFFFF'; }
                removeElement('{$form['id']}');">
                <td style="padding: 0px 2px; font-size: 10px;">{$template}</td>
            </tr>
EOL;
    }
    $html .= <<<EOL
            </div></tbody></table>
            </td></tr>
            <td colspan="2"align="center" class="qf-search-line">
                <input class="button" type="button" name="cancel" value="Cancel" onClick="removeElement('{$form['id']}');">
            </td></tr>
        </table>
    </form>
EOL;

    return(array($html, $js));
}


// Simple ping function that takes an IP in and pings it.. then shows the output in a module results window
function ws_ping($window_name, $form='') {

    // If an array in a string was provided, build the array and store it in $form
    $form = parse_options_string($form);

    $output = shell_exec("ping -n -w 3 -c 3 {$form['ip']}");

    $window['title'] = 'Ping Results';
    $build_commit_html = 0;
    $commit_function = '';
    include(window_find_include('module_results'));
    return(window_open("{$window_name}_results", $window));
}

?>
